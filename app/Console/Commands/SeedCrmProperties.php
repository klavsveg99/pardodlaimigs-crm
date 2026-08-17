<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ReconcileAllProperties;
use App\Models\Attachment;
use App\Models\CrmProperty;
use App\Models\PropertyCache;
use App\Services\Wp\PropertyNormalizer;
use App\Services\Wp\WpSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SeedCrmProperties extends Command
{
    protected $signature = 'pdc:seed-crm-properties
        {--skip-refresh : Use the existing WordPress property cache instead of refreshing it first}
        {--include-hidden : Also import hidden and expired cached properties}';

    protected $description = 'Seed CRM-owned properties from the current WordPress property feed';

    public function handle(WpSource $source, PropertyNormalizer $normalizer): int
    {
        if (! $this->option('skip-refresh')) {
            $this->info('Refreshing the WordPress property cache...');
            (new ReconcileAllProperties)->handle($source, $normalizer);
        }

        $query = PropertyCache::query()->orderBy('id');
        if (! $this->option('include-hidden')) {
            $query->whereNotIn('status', ['hidden', 'expired']);
        }

        $created = 0;
        $updated = 0;

        foreach ($query->cursor() as $property) {
            $crm = CrmProperty::updateOrCreate(
                ['wp_post_id' => $property->id],
                [
                    'title' => $property->title,
                    'slug' => $property->slug,
                    'description' => null,
                    'image_urls' => array_values(array_filter(array_merge(
                        [$property->thumbnail_url],
                        $property->gallery_urls ?? [],
                    ))),
                    'price_cents' => $property->price_cents ?? 0,
                    'price_eur' => round(($property->price_cents ?? 0) / 100, 2),
                    'currency' => $property->currency ?: 'EUR',
                    'category' => $property->category,
                    'status' => $this->crmStatus($property),
                    'beds' => $property->beds,
                    'baths' => $property->baths,
                    'size_m2' => $property->size_m2,
                    'land_m2' => $property->land_m2,
                    'kadastra_nr' => $property->kadastra_nr,
                    'city' => $property->city,
                    'address' => $property->address,
                    'lat' => $property->lat,
                    'lng' => $property->lng,
                ],
            );

            $crm->wasRecentlyCreated ? $created++ : $updated++;
            $this->importPhotos($crm, $crm->image_urls ?? []);
        }

        $this->info("CRM properties seeded: {$created} created, {$updated} updated.");

        return self::SUCCESS;
    }

    /**
     * Copy WordPress-hosted images into CRM storage so the CRM owns its seed data.
     * Existing CRM attachments are kept; deterministic paths prevent duplicates.
     *
     * @param  array<int, string>  $urls
     */
    private function importPhotos(CrmProperty $property, array $urls): void
    {
        foreach (array_values(array_filter($urls)) as $index => $url) {
            $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg';
            $path = "attachments/crm-properties/{$property->id}/".sha1($url).'.'.strtolower($extension);

            if (! Storage::disk('public')->exists($path)) {
                $response = Http::timeout(30)->get($url);
                if ($response->failed()) {
                    $this->warn("Photo download failed for CRM property #{$property->id}: {$url}");

                    continue;
                }

                Storage::disk('public')->put($path, $response->body());
            }

            if (! Attachment::query()->where('attachable_type', CrmProperty::class)->where('attachable_id', $property->id)->where('path', $path)->exists()) {
                $property->attachments()->create([
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => basename(parse_url($url, PHP_URL_PATH) ?: "photo-{$index}.jpg"),
                    'mime_type' => Storage::disk('public')->mimeType($path),
                    'size' => Storage::disk('public')->size($path),
                    'sort_order' => $index,
                ]);
            }
        }
    }

    private function crmStatus(PropertyCache $property): string
    {
        $status = mb_strtolower((string) $property->status);
        $category = mb_strtolower((string) $property->category);

        if ($status === 'sold' || str_contains($category, 'sold') || str_contains($category, 'pārdot') || str_contains($category, 'pardot')) {
            return 'sold';
        }

        return match ($status) {
            'publish' => 'published',
            'expired' => 'expired',
            'hidden' => 'hidden',
            default => 'draft',
        };
    }
}
