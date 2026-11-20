<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Plain text for public pages: blank lines become paragraphs and every line
 * break inside a paragraph collapses to a space, so user input never carries
 * arbitrary HTML or line-break formatting into the site.
 */
class LightText
{
    public static function paragraphs(?string $text): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        $paragraphs = preg_split('/\R\s*\R/u', $text) ?: [];

        $html = '';
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim((string) preg_replace('/\s*\R\s*/u', ' ', $paragraph));

            if ($paragraph !== '') {
                $html .= '<p>'.e($paragraph).'</p>';
            }
        }

        return $html;
    }
}
