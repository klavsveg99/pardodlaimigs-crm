<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PropertyCache;
use App\Services\Wp\PropertyNormalizer;
use App\Services\Wp\WpSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReconcileAllProperties implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function handle(WpSource $source, PropertyNormalizer $normalizer): void
    {
        $perPage = (int) config('wp-bridge.feed.per_page', 100);
        $seen = [];

        foreach ($source->eachProperty($perPage) as $wp) {
            $id = (int) $wp->id;
            $seen[$id] = true;

            $data = $normalizer->normalize($wp);
            $existing = PropertyCache::find($id);

            if (! $existing) {
                PropertyCache::create($data);

                continue;
            }

            // Re-activate when the feed returns a property we marked as hidden or expired,
            // or refresh whenever any synced field differs from the cache so
            // that e.g. bedrooms/bathrooms/price populate as soon as the WP
            // endpoint starts returning them (not only when updated_at changes).
            if (in_array($existing->status, ['hidden', 'expired']) || $this->differs($existing, $data)) {
                $existing->update($data);
            }
        }

        // Properties no longer returned by the feed (unpublished/hidden/removed)
        // are marked as hidden instead of deleted, keeping CRM references
        // (client relations, deals, viewings) intact.
        //
        // Only run when the feed was actually fetched: an empty/errored
        // response must not wipe the whole cache to hidden.
        if ($seen) {
            PropertyCache::query()
                ->whereNotIn('id', array_keys($seen))
                ->where('status', '!=', 'hidden')
                ->update([
                    'status' => 'hidden',
                    'cached_at' => now(),
                ]);
        }
    }

    /**
     * Whether any of the normalised feed columns differ from the stored row.
     *
     * @param  array<string, mixed>  $data
     */
    private function differs(PropertyCache $existing, array $data): bool
    {
        foreach ($data as $column => $value) {
            if ($column === 'cached_at') {
                continue;
            }

            $stored = $existing->getAttribute($column);
            if (is_array($stored) || is_array($value)) {
                if ($stored != $value) {
                    return true;
                }

                continue;
            }

            if ((string) $stored !== (string) $value) {
                return true;
            }
        }

        return false;
    }
}
