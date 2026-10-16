<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CrmProperty;
use App\Models\User;
use App\Services\Site\SiteContent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class PublicSiteController extends Controller
{
    public function __construct(private readonly SiteContent $content) {}

    public function home(): Response|View
    {
        if ($wpPostId = request()->integer('p')) {
            $property = CrmProperty::query()->where('wp_post_id', $wpPostId)->first();
            if ($property?->slug) {
                return redirect($this->content->canonicalUrl('lv', '/ipasums/'.$property->slug), 301);
            }
        }

        return $this->homeView('lv');
    }

    public function localizedHome(string $locale): View
    {
        return $this->homeView($this->content->locale($locale));
    }

    public function properties(): View
    {
        return $this->propertiesView('lv');
    }

    public function localizedProperties(string $locale): View
    {
        return $this->propertiesView($this->content->locale($locale));
    }

    public function property(string $slug): View
    {
        return $this->propertyView('lv', $slug);
    }

    public function localizedProperty(string $locale, string $slug): View
    {
        return $this->propertyView($this->content->locale($locale), $slug);
    }

    public function post(string $slug): View
    {
        return $this->postView('lv', $slug);
    }

    public function localizedPost(string $locale, string $slug): View
    {
        return $this->postView($this->content->locale($locale), $slug);
    }

    public function page(string $slug): Response|View
    {
        return $this->pageView('lv', $slug);
    }

    public function localizedPage(string $locale, string $slug): Response|View
    {
        return $this->pageView($this->content->locale($locale), $slug);
    }

    public function agent(string $slug): View
    {
        return $this->agentView('lv', $slug);
    }

    public function localizedAgent(string $locale, string $slug): View
    {
        return $this->agentView($this->content->locale($locale), $slug);
    }

    public function sitemap(): Response
    {
        $locales = config('site.locales', ['lv', 'en', 'ru', 'de']);
        $paths = ['/', '/properties/', '/privatuma-politika/', '/paldies/'];

        foreach ($this->propertyList() as $property) {
            $paths[] = '/ipasums/'.$property['slug'].'/';
        }
        foreach ($this->content->posts('lv') as $post) {
            $paths[] = '/'.$post['slug'].'/';
        }
        foreach (['roberts-evarsons', 'inga-dobuma'] as $agent) {
            $paths[] = '/agent/'.$agent.'/';
        }

        $urls = [];
        foreach (array_unique($paths) as $path) {
            foreach ($locales as $locale) {
                if ($path === '/paldies/' && $locale !== 'lv') {
                    continue;
                }
                $urls[] = $this->content->canonicalUrl($locale, $path);
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            $xml .= '<url><loc>'.e($url).'</loc></url>';
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * The next batch of blog cards, as HTML ready to append. The homepage shows
     * the first batch server side; this is what its load more button asks for.
     */
    public function blogMore(Request $request): JsonResponse
    {
        $locale = $this->content->locale((string) $request->query('locale', 'lv'));
        $batch = max(1, (int) config('site.blog_batch', 3));
        $page = max(2, (int) $request->query('page', 2));
        $offset = ($page - 1) * $batch;
        $posts = $this->content->posts($locale);

        return response()->json([
            'html' => view('site.partials.article-cards', [
                'posts' => array_slice($posts, $offset, $batch),
                'copy' => $this->content->strings($locale),
                'locale' => $locale,
                'offset' => $offset,
            ])->render(),
            'hasMore' => ($offset + $batch) < count($posts),
        ]);
    }

    private function homeView(string $locale): View
    {
        $properties = $this->propertyList();
        $hero = array_values(array_filter($properties, static fn (array $property): bool => filled($property['hero_image'] ?? null)));
        $batch = (int) config('site.blog_batch', 3);
        $posts = $this->content->posts($locale);
        $copy = $this->content->strings($locale);
        $path = '/';

        return view('site.home', $this->pageData($locale, $path) + [
            'properties' => $properties,
            'heroProperties' => $hero,
            'posts' => array_slice($posts, 0, $batch),
            'hasMorePosts' => count($posts) > $batch,
            'useful' => $this->content->useful($locale),
            'agents' => $this->agents($locale),
            'googleMapsKey' => (string) config('services.google_maps.key'),
            'categories' => config('site.property_categories', []),
        ]);
    }

    private function propertiesView(string $locale): View
    {
        return view('site.properties', $this->pageData($locale, '/properties/') + [
            'properties' => $this->propertyList(),
            'categories' => config('site.property_categories', []),
            'googleMapsKey' => (string) config('services.google_maps.key'),
        ]);
    }

    private function propertyView(string $locale, string $slug): View
    {
        $property = collect($this->propertyList())->firstWhere('slug', $slug);
        abort_unless($property, 404);

        return view('site.property', $this->pageData($locale, '/ipasums/'.$slug.'/') + [
            'property' => $property,
            'googleMapsKey' => (string) config('services.google_maps.key'),
        ]);
    }

    private function postView(string $locale, string $slug): View
    {
        $post = $this->content->post($slug, $locale);
        abort_unless($post, 404);

        return view('site.post', $this->pageData($locale, '/'.$slug.'/') + ['post' => $post]);
    }

    private function pageView(string $locale, string $slug): Response|View
    {
        if (in_array($slug, ['home', 'sakums'], true)) {
            return redirect($this->content->canonicalUrl($locale), 301);
        }
        if ($slug === 'properties') {
            return redirect($this->content->canonicalUrl($locale, '/properties/'), 301);
        }

        $page = $this->content->page($slug, $locale);
        if ($page) {
            return view('site.content-page', $this->pageData($locale, '/'.$slug.'/') + ['page' => $page]);
        }

        $post = $this->content->post($slug, $locale);
        abort_unless($post, 404);

        return view('site.post', $this->pageData($locale, '/'.$slug.'/') + ['post' => $post]);
    }

    private function agentView(string $locale, string $slug): View
    {
        $agent = collect($this->agents($locale))->firstWhere('slug', $slug);
        abort_unless($agent, 404);

        $agent['properties'] = collect($this->propertyList())
            ->filter(fn (array $property): bool => ($property['agent'] ?? '') === $agent['name'])
            ->values()
            ->all();

        return view('site.agent', $this->pageData($locale, '/agent/'.$slug.'/') + ['agent' => $agent]);
    }

    private function pageData(string $locale, string $path): array
    {
        $urls = [];
        foreach (config('site.locales', ['lv', 'en', 'ru', 'de']) as $supportedLocale) {
            $urls[$supportedLocale] = $this->content->canonicalUrl($supportedLocale, $path);
        }

        return [
            'locale' => $locale,
            'copy' => $this->content->strings($locale),
            'canonical' => $this->content->canonicalUrl($locale, $path),
            'languageUrls' => $urls,
            'siteUrl' => rtrim((string) config('wp-bridge.public_url'), '/'),
            'languages' => config('site.language_names', []),
            'flags' => config('site.language_flags', []),
            'showCookieBanner' => true,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function propertyList(): array
    {
        if (config('site.preview_snapshot')) {
            return $this->readPreviewProperties();
        }

        return CrmProperty::query()
            ->where('status', 'published')
            ->with(['owner', 'attachments'])
            ->orderByRaw('COALESCE(sort_order, id)')
            ->get()
            ->map(fn (CrmProperty $property): array => $this->propertyArray($property))
            ->all();
    }

    private function readPreviewProperties(): array
    {
        $path = (string) config('site.preview_properties');
        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) File::get($path), true);

        return is_array($data) ? array_values(array_filter($data, static fn (array $item): bool => ($item['status'] ?? 'published') === 'published')) : [];
    }

    private function propertyArray(CrmProperty $property): array
    {
        $gallery = $property->attachments
            ->filter(static fn ($attachment): bool => $attachment->isImage())
            ->values();
        $first = $gallery->first();
        $landHa = (float) $property->land_m2 / 10000;
        $facts = [];

        if ((int) $property->size_m2 > 0) {
            $facts[] = ['value' => number_format((int) $property->size_m2, 0, '.', ' ').' m²', 'label' => 'Telpu platība'];
        }
        if ($landHa > 0) {
            $facts[] = ['value' => rtrim(rtrim(number_format($landHa, 2, '.', ''), '0'), '.').' ha', 'label' => 'Zemes platība'];
        }
        if ((int) $property->beds > 0) {
            $facts[] = ['value' => (string) $property->beds, 'label' => 'Istabas'];
        }
        if ((int) $property->baths > 0) {
            $facts[] = ['value' => (string) $property->baths, 'label' => 'Vannasistabas'];
        }

        return [
            'title' => (string) $property->title,
            'slug' => (string) $property->slug,
            'price' => (float) $property->price_eur > 0 ? '€'.number_format((float) $property->price_eur, 0, '.', ',') : '',
            'price_value' => (float) $property->price_eur,
            'address' => (string) ($property->address ?: $property->city),
            'city' => (string) $property->city,
            'category' => (string) $property->category,
            'description' => (string) $property->description,
            'agent' => (string) ($property->owner?->name ?? ''),
            'agent_phone' => (string) ($property->owner?->phone ?? ''),
            'facts' => $facts,
            'gallery' => $gallery->map(static fn ($attachment): string => $attachment->url)->all(),
            'image' => $first?->thumbUrl() ?: asset('images/no-photo.svg'),
            'hero_image' => $first?->url,
            'lat' => $property->lat !== null ? (float) $property->lat : null,
            'lng' => $property->lng !== null ? (float) $property->lng : null,
            'status' => (string) $property->status,
        ];
    }

    private function agents(string $locale = 'lv'): array
    {
        $preview = [
            ['slug' => 'roberts-evarsons', 'name' => 'Roberts Evarsons', 'phone' => '+371 2492 2942', 'position' => 'Nekustamo īpašumu aģents', 'avatar_url' => asset('site-assets/images/team/roberts.jpg')],
            ['slug' => 'inga-dobuma', 'name' => 'Inga Dobuma', 'phone' => '+371 2822 8208', 'position' => 'Nekustamo īpašumu aģente', 'portrait' => true, 'avatar_url' => asset('site-assets/images/team/inga.jpg')],
        ];

        foreach ($preview as $index => $agent) {
            $preview[$index]['description'] = $this->content->agentBio($locale, $agent['slug']);
        }

        if (config('site.preview_snapshot')) {
            return $preview;
        }

        $agents = User::query()->whereIn('slug', ['roberts-evarsons', 'inga-dobuma'])->get()->keyBy('slug');

        $bios = collect($preview)->mapWithKeys(fn (array $agent): array => [
            $agent['slug'] => $this->content->agentBio($locale, $agent['slug']),
        ]);

        return collect($preview)->map(function (array $fallback) use ($agents, $bios): array {
            $agent = $agents->get($fallback['slug']);
            if (! $agent) {
                return $fallback;
            }

            return [
                'slug' => $agent->slug,
                'name' => $agent->name,
                'phone' => $agent->phone ?: $fallback['phone'],
                'position' => $agent->position ?: $fallback['position'],
                'description' => $agent->description ?: ($bios[$fallback['slug']] ?? ''),
                'avatar_url' => $agent->avatar_url ?: $fallback['avatar_url'],
                'portrait' => $fallback['portrait'] ?? false,
            ];
        })->all();
    }
}
