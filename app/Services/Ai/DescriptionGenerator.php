<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * AI teksta ģenerators īpašumu aprakstiem.
 *
 * Balstās strikti uz CRM datos un aģenta ievadītajām piezīmēm — modelim ir
 * aizliegts izdomāt faktus. Atbalsta Gemini (bezmaksas līmenis) un OpenAI;
 * pietiek vienas konfigurētas atslēgas.
 */
class DescriptionGenerator
{
    public const MODE_FULL = 'full';

    public const MODE_SHORTEN = 'shorten';

    public const MODE_PERSUASIVE = 'persuasive';

    /**
     * @param  array<string, mixed>  $context  CRM form datos (title, category, ...)
     * @param  array<string, string|null>  $notes  ai_notes (advantages, technical, investment, extra)
     * @return array{description: string, description_ss: string, title: string, facebook: string, instagram: string}
     */
    public function generate(array $context, array $notes, string $mode = self::MODE_FULL, ?string $currentDescription = null): array
    {
        $payload = $this->callModel($this->buildPrompt($context, $notes, $mode, $currentDescription));

        foreach (['description', 'description_ss', 'title', 'facebook', 'instagram'] as $key) {
            if (! isset($payload[$key]) || ! is_string($payload[$key])) {
                throw new RuntimeException("AI atbildei trūkst lauka: {$key}");
            }
        }

        return [
            'description' => (string) $payload['description'],
            'description_ss' => (string) $payload['description_ss'],
            'title' => (string) $payload['title'],
            'facebook' => (string) $payload['facebook'],
            'instagram' => (string) $payload['instagram'],
        ];
    }

    public static function provider(): ?string
    {
        if (filled(config('services.openrouter.key'))) {
            return 'openrouter';
        }

        if (filled(config('services.gemini.key'))) {
            return 'gemini';
        }

        if (filled(config('services.openai.key'))) {
            return 'openai';
        }

        return null;
    }

    private function callModel(string $prompt): array
    {
        return match (self::provider()) {
            'openrouter' => $this->callOpenrouter($prompt),
            'gemini' => $this->callGemini($prompt),
            'openai' => $this->callOpenai($prompt),
            default => throw new RuntimeException('Nav konfigurēta AI atslēga (OPENROUTER_API_KEY, GEMINI_API_KEY vai OPENAI_API_KEY).'),
        };
    }

    /**
     * OpenRouter (OpenAI-saderīgs API). Bezmaksas modeļi neatbalsta
     * response_format — JSON dekodēšana notiek ar fence-stripping.
     */
    private function callOpenrouter(string $prompt): array
    {
        $model = (string) config('services.openrouter.model', 'google/gemma-4-31b-it:free');
        $key = (string) config('services.openrouter.key');

        $response = Http::withToken($key)
            ->withHeaders([
                'HTTP-Referer' => rtrim((string) config('app.url', ''), '/') ?: 'https://crm.pardodlaimigs.lv',
                'X-Title' => 'Pardod Laimigs CRM',
            ])
            ->timeout(120)
            ->retry(1, 300)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model,
                'temperature' => 0.7,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('OpenRouter API kļūda: HTTP '.$response->status().': '.(string) $response->json('error.message', ''));
        }

        $text = $response->json('choices.0.message.content');
        if (! is_string($text) || $text === '') {
            throw new RuntimeException('OpenRouter atgrieza tukšu atbildi.');
        }

        return $this->decodeJson($text);
    }

    private function callGemini(string $prompt): array
    {
        $model = (string) config('services.gemini.model', 'gemini-3.5-flash');
        $key = (string) config('services.gemini.key');

        $response = Http::timeout(90)
            ->retry(1, 300)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'responseMimeType' => 'application/json',
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini API kļūda: HTTP '.$response->status());
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        if (! is_string($text) || $text === '') {
            throw new RuntimeException('Gemini atgrieza tukšu atbildi.');
        }

        return $this->decodeJson($text);
    }

    private function callOpenai(string $prompt): array
    {
        $model = (string) config('services.openai.model', 'gpt-4o-mini');
        $key = (string) config('services.openai.key');

        $response = Http::withToken($key)
            ->timeout(90)
            ->retry(1, 300)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'temperature' => 0.7,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI API kļūda: HTTP '.$response->status());
        }

        $text = $response->json('choices.0.message.content');
        if (! is_string($text) || $text === '') {
            throw new RuntimeException('OpenAI atgrieza tukšu atbildi.');
        }

        return $this->decodeJson($text);
    }

    private function decodeJson(string $text): array
    {
        $text = trim($text);
        // Models sometimes wraps JSON in markdown fences despite response format.
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text) ?? $text;
            $text = trim($text);
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            // Models may prepend a sentence or append text around the JSON
            // object — extract the outermost {...} and retry.
            $start = mb_strpos($text, '{');
            $end = mb_strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $decoded = json_decode(mb_substr($text, $start, $end - $start + 1), true);
            }
        }

        if (! is_array($decoded)) {
            \Illuminate\Support\Facades\Log::warning('AI non-JSON response', ['text' => mb_substr($text, 0, 600)]);
            throw new RuntimeException('AI atbilde nav derīgs JSON.');
        }

        return $decoded;
    }

    private function buildPrompt(array $context, array $notes, string $mode, ?string $currentDescription): string
    {
        $data = [
            'nosaukums' => (string) ($context['title'] ?? ''),
            'kategorija' => (string) ($context['category'] ?? ''),
            'statuss' => (string) ($context['status'] ?? ''),
            'cena_eur' => (string) ($context['price_eur'] ?? ''),
            'istabas' => (string) ($context['beds'] ?? ''),
            'vannas_istabas' => (string) ($context['baths'] ?? ''),
            'platiba_m2' => (string) ($context['size_m2'] ?? ''),
            'zemes_platiba_m2' => (string) ($context['land_m2'] ?? ''),
            'kadastra_nr' => (string) ($context['kadastra_nr'] ?? ''),
            'pilseta' => (string) ($context['city'] ?? ''),
            'adrese' => (string) ($context['address'] ?? ''),
        ];

        $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $notesJson = json_encode(array_map(strval(...), $notes), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $currentJson = $currentDescription !== null
            ? json_encode($currentDescription, JSON_UNESCAPED_UNICODE)
            : 'null';

        $instruction = match ($mode) {
            self::MODE_SHORTEN => 'REŽĪMS: SAĪSINĀT. Paņem esošo aprakstu ( zemāk "esošais_apraksts" ) un saīsini to par aptuveni 40%, saglabājot visus faktus un bez izdomāšanas. Pārģenerē arī citos formātos no saīsinātā apraksta.',
            self::MODE_PERSUASIVE => 'REŽĪMS: PADARĪT PĀRLIECINOŠĀKU. Paņem esošo aprakstu un padari to pārliecinošāku pārdošanai (dzīvīgāks tonis, uzsvars uz ieguvumiem), bet NEDRĪKST pievienot jaunus faktus, ko dati neapstiprina. Pārģenerē arī citos formātos.',
            default => $currentDescription === null
                ? 'REŽĪMS: ĢENERĒT NO DATIEM.'
                : 'REŽĪMS: ĢENERĒT VĒLREIZ (variēja formulējumus, neaiztiek faktus).',
        };

        return <<<PROMPT
Tu esi nekustamo īpašumu aģentūras "Pārdod Laimīgs" tekstu autors. Raksti latviešu valodā.

{$instruction}

STRIPTA NOTEIKUMI (ļoti svarīgi):
- NEDRĪKST izdomāt nevienu faktu, kas nav dotajos datos vai aģenta piezīmēs.
- Ja kāda informācija nav pieejama (piem., nav istabu skaita, nav apkures veida), to nepiemin un neuzmin.
- Neizdomā attālumus, iedzīvotājus, transportu, skolām, veikaliem utt., ja tie nav piezīmēs.
- Cenu piemin tikai, ja tā norādīta datos.

DATI (CRM formas lauki):
{$dataJson}

AĢENTA PIEZĪMES (var būt tukšas):
{$notesJson}

ESOŠAIS APRAKSTS (tikai saīsināšanas/pārliecinošāka režīmiem):
{$currentJson}

ATGRIEZT TIKAI JSON ar šādām atslēgām:
- "description": HTML pilnais sludinājuma apraksts mājaslapai/portāliem. Struktūra: <p><strong>[LV]</strong><br>latviešu teksts (vairāki teikumi: iesākums, priekšrocības no piezīmēm, aicinājums apskatīt)</p><p><strong>[EN]</strong><br>angļu tulkojums, balstīts TIKAI uz tiem pašiem faktiem</p>. Bez Markdown, tikai <p>/<br>/<strong> tagi.
- "description_ss": ss.lv stila vienkāršs teksta variants (bez HTML, atdalīts ar rindkopām), 2-4 rindkopas.
- "title": īss sludinājuma virsraksta variants (maks. ~60 rakstzīmes, bez cenas, latviski).
- "facebook": īss Facebook ieraksts (2-4 teikumi, draudzīgs tonis, emoji atļauti tikai ja der, bez izdomātiem faktiem).
- "instagram": īss uzrunājošs Instagram/Reels apraksts (1-3 teikumi, 3-6 atbilstoši hashtag, piem. #nekustamieipasumi #pardodlaimigs).
PROMPT;
    }
}
