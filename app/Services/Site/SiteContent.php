<?php

declare(strict_types=1);

namespace App\Services\Site;

class SiteContent
{
    private ?array $content = null;

    private ?array $translations = null;

    public function strings(string $locale): array
    {
        $locale = $this->locale($locale);
        $language = $this->translations()['languages'][$locale] ?? [];
        $extended = $this->translations()['extendedTranslations'][$locale] ?? [];

        return array_merge($language, $extended, [
            'categoryLabels' => $this->translations()['translatedCategories'][$locale] ?? [],
        ]);
    }

    public function posts(string $locale): array
    {
        $locale = $this->locale($locale);
        $posts = $this->content()['posts'] ?? [];

        usort($posts, static fn (array $left, array $right): int => strcmp($right['date'] ?? '', $left['date'] ?? ''));

        return array_map(static function (array $post) use ($locale): array {
            $translation = $post['translations'][$locale] ?? $post['translations']['lv'] ?? [];

            return $post + $translation;
        }, $posts);
    }

    public function post(string $slug, string $locale): ?array
    {
        foreach ($this->posts($locale) as $post) {
            if (($post['slug'] ?? null) === $slug) {
                return $post;
            }
        }

        return null;
    }

    public function page(string $slug, string $locale): ?array
    {
        $locale = $this->locale($locale);
        $page = $this->content()['pages'][$slug] ?? null;
        if (! $page) {
            return null;
        }

        return $page + ($page['translations'][$locale] ?? $page['translations']['lv'] ?? []);
    }

    public function locale(string $locale): string
    {
        return in_array($locale, config('site.locales', ['lv', 'en', 'ru', 'de']), true) ? $locale : 'lv';
    }

    public function localizedPath(string $locale, string $path = '/'): string
    {
        $locale = $this->locale($locale);
        $path = '/'.ltrim($path, '/');
        $prefix = $locale === 'lv' ? '' : '/'.$locale;

        return rtrim($prefix.$path, '/').'/';
    }

    public function canonicalUrl(string $locale, string $path = '/'): string
    {
        return rtrim((string) config('wp-bridge.public_url'), '/').$this->localizedPath($locale, $path);
    }

    private function content(): array
    {
        return $this->content ??= $this->readJson((string) config('site.content_file'));
    }

    private function translations(): array
    {
        return $this->translations ??= $this->readJson((string) config('site.translations_file'));
    }

    private function readJson(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }
}
