<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\CrmProperty;
use App\Support\PhoneFormat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * "PDF mārketings" — viegla A4 lapas ģeneratora lapa, kas automātiski
 * aizpilda īpašuma datus un attēlus un ļauj tos lejupielādēt kā PDF
 * (html2pdf pēc pieprasījuma) vai izdrukāt.
 *
 * Ārējais generatora rīks neatbalsta datu ievadi, tāpēc šī lapa ir
 * īstā integrācija: visi dati nāk no CRM.
 */
class PropertyMarketingController extends Controller
{
    public function show(Request $request, string $propertySlug): View
    {
        $property = CrmProperty::query()
            ->where('slug', $propertySlug)
            ->firstOrFail();

        $user = $request->user();
        abort_unless(
            $user->can('manage') || $property->owner_user_id === $user->id || $user->isPhoto(),
            403,
        );

        // Tikai attēli — galerijā var būt arī cita veida faili.
        $gallery = $property->attachments
            ->where('collection', 'gallery')
            ->filter(fn (Attachment $attachment): bool => str_starts_with((string) $attachment->mime_type, 'image/'))
            ->sortBy('sort_order')
            ->values();

        $images = $gallery
            ->map(fn (Attachment $attachment): ?string => $this->attachmentUrl($attachment))
            ->filter()
            ->values();

        if ($images->isEmpty()) {
            $images = collect($property->image_urls ?? [])
                ->filter(fn ($url): bool => is_string($url) && $url !== '')
                ->map(fn (string $url): string => $this->proxiedUrl($url))
                ->values();
        }

        $agent = $property->owner;

        return view('marketing.property-flyer', [
            'property' => $property,
            'images' => $images,
            'mapUrl' => $this->staticMapDataUri($property),
            'agent' => $agent,
            'agentName' => $agent?->name,
            'agentRole' => $agent?->position ?: 'Nekustamo īpašumu konsultants',
            'agentPhone' => PhoneFormat::display($agent?->phone) ?: null,
            'agentEmail' => $agent?->email,
            'agentAvatar' => $agent?->avatar_url,
            'listingId' => $property->wp_post_id ? 'ID-'.$property->wp_post_id : 'ID-'.$property->id,
            'listingDate' => ($property->sale_started_at ?? $property->updated_at)
                ?->locale('lv')
                ->translatedFormat('F Y'),
            'pricePerSqm' => ($property->price_eur > 0 && $property->size_m2 > 0)
                ? (int) round((float) $property->price_eur / (int) $property->size_m2)
                : null,
            'ctaUrl' => $property->public_url,
            'descriptionText' => $this->flyerDescription($property),
        ]);
    }

    /**
     * PDF mārketinga apraksts: galvenokārt no AI ģenerētā Facebook teksta
     * (`ai_result.facebook`), bez emocijzīmēm; ja tā nav, izmanto parasto
     * īpašuma aprakstu. HTML tiek pārvērsts vienkāršā tekstā ar rindkopām.
     */
    private function flyerDescription(CrmProperty $property): string
    {
        $ai = is_array($property->ai_result) ? $property->ai_result : [];
        $source = trim((string) ($ai['facebook'] ?? ''));
        if ($source === '') {
            $source = (string) $property->description;
        }

        $text = preg_replace(['/<br\s*\/?>/i', '/<\/(p|div|li|h[1-6])>/i'], "\n", $source) ?? $source;
        $text = html_entity_decode(strip_tags($text));
        $text = $this->stripEmojis($text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/ ?\n ?/', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /** Noņem emocijzīmes, nemainot parasto tekstu (€, domuzīmes, pēdiņas paliek). */
    private function stripEmojis(string $text): string
    {
        $pattern = '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}]/u';

        return preg_replace($pattern, '', $text) ?? $text;
    }

    /** Attēla URL, kas html2canvas var ielādēt bez CORS problēmām (same-origin). */
    private function attachmentUrl(Attachment $attachment): ?string
    {
        if (! $attachment->path) {
            return null;
        }

        if (str_starts_with($attachment->path, 'http://') || str_starts_with($attachment->path, 'https://')) {
            return $this->proxiedUrl($attachment->url);
        }

        return $attachment->cacheBustedUrl();
    }

    /**
     * Ārējos (WordPress) attēlus plūst caur CRM image-proxy, lai tie būtu
     * same-origin un html2canvas tos varētu uzzīmēt PDF.
     */
    private function proxiedUrl(string $url): string
    {
        return route('filament.admin.property.image-proxy', ['url' => $url]);
    }

    /**
     * Google Static Maps attēls kā data URI — tā PDF vienmēr satur karti.
     */
    private function staticMapDataUri(CrmProperty $property): ?string
    {
        $key = (string) config('services.google_maps.key');
        if ($key === '' || blank($property->lat) || blank($property->lng)) {
            return null;
        }

        $center = $property->lat.','.$property->lng;
        $url = 'https://maps.googleapis.com/maps/api/staticmap?'
            .http_build_query([
                'center' => $center,
                'zoom' => 15,
                'size' => '600x340',
                'scale' => 2,
                'maptype' => 'roadmap',
                'markers' => 'color:0x285854|'.$center,
                'key' => $key,
            ]);

        try {
            $response = Http::timeout(8)->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
            return null;
        }

        return 'data:'.strtok((string) $response->header('Content-Type'), ';').';base64,'.base64_encode($response->body());
    }
}
