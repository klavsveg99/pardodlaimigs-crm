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
 * aizliegts izdomāt faktus. Atbalsta Gemini un OpenAI;
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
        // Modelis reizēm nogriež garu atbildi vidū (domu žetoni apēd
        // izvades budžetu) — tad decodeJson met izņēmumu; mēģinām vēlreiz.
        try {
            $payload = $this->callModel($this->buildPrompt($context, $notes, $mode, $currentDescription));
        } catch (RuntimeException $e) {
            if (! str_contains($e->getMessage(), 'nav derīgs JSON')) {
                throw $e;
            }
            $payload = $this->callModel($this->buildPrompt($context, $notes, $mode, $currentDescription));
        }

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
            'gemini' => $this->callGemini($prompt),
            'openai' => $this->callOpenai($prompt),
            default => throw new RuntimeException('Nav konfigurēta AI atslēga (GEMINI_API_KEY vai OPENAI_API_KEY).'),
        };
    }

    private function callGemini(string $prompt): array
    {
        $model = (string) config('services.gemini.model', 'gemini-3.5-flash');
        $key = (string) config('services.gemini.key');

        $response = Http::timeout(90)
            ->retry([1000, 2000, 4000], function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\RequestException
                    && $exception->response
                    && $exception->response->status() === 429;
            })
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'responseMimeType' => 'application/json',
                    // "Thinking" (domu) žetoni rēķinās kopā ar atbildes tekstiem
                    // maxOutputTokens limitā — ar zemu limitu strukturēti
                    // WEB+ss.com teksti tika nogriezti vidū un JSON nebija
                    // derīgs. Modelis atbalsta līdz 65536.
                    'maxOutputTokens' => (int) config('services.gemini.max_output_tokens', 65536),
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
            ->retry([1000, 2000, 4000], function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\RequestException
                    && $exception->response
                    && $exception->response->status() === 429;
            })
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
            \Illuminate\Support\Facades\Log::warning('AI non-JSON response', [
                'text' => mb_substr($text, 0, 600),
                'text_len' => mb_strlen($text),
                'json_error' => json_last_error_msg(),
            ]);
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
            self::MODE_SHORTEN => 'REŽĪMS: SAĪSINĀT. Paņem esošo aprakstu ( zemāk "esošais_apraksts" ) un saīsini to KVANTITATĪVI: gala apraksta vārdu skaitam JĀBŪT aptuveni 60% no esošā apraksta vārdu skaita (skaiti pirms raksti). Saglabā visus faktus un NEIZDOMĀ neko jaunu. Pārģenerē arī citos formātos (ss.lv, Facebook, Instagram, virsrakstu) no saīsinātā apraksta — arī tie īsāki.',
            self::MODE_PERSUASIVE => 'REŽĪMS: PADARĪT PĀRLIECINOŠĀKU. Paņem esošo aprakstu un padari to pārliecinošāku pārdošanai (dzīvīgāks tonis, uzsvars uz ieguvumiem, aicinoša noslēguma frāze), bet NEDRĪKST pievienot jaunus faktus, ko dati neapstiprina, un garums drīkst atšķirties no esošā ne vairāk kā ±15%. Pārģenerē arī citos formātos.',
            default => $currentDescription === null
                ? 'REŽĪMS: ĢENERĒT NO DATIEM.'
                : 'REŽĪMS: ĢENERĒT VĒLREIZ (variēja formulējumus, neaiztiek faktus).',
        };

        return <<<PROMPT
Tu esi nekustamo īpašumu aģentūras "Pārdod Laimīgs" tekstu autors. Raksti latviešu valodā.
NEVIS vienkārši "bliez" visu informāciju teikumu pēc teikuma — veido profesionālu sludinājumu ar skaidru struktūru, virsrakstiem, rindkopām, aizzīmēm un pārdošanas loģiku.
WEB un ss.com teksti balstās uz tiem pašiem CRM datiem, bet noformējums pielāgots konkrētajai platformai.

{$instruction}

4.2. AI ĢENERĒŠANAS NOTEIKUMI:
- Vispirms izvērtē ievadītos datus un nosaki spēcīgākos pārdošanas argumentus — tos izcel pirmos.
- Virsrakstam jābūt uzmanību piesaistošam, profesionālam un balstītam faktos.
- Tekstu veido īsās, pārskatāmās rindkopās (2-4 teikumi katrā), nevis vienā garā blokā.
- Būtiskos parametrus izdali atsevišķā blokā.
- Telpu, aprīkojuma un priekšrocību uzskaitījumos izmanto aizzīmes.
- Īpaši izdevīgus iegādes nosacījumus (no piezīmes "investment": Altum, hipotekārais kredīts u.c.) izcel atsevišķā sadaļā, JA tādi ievadīti.
- Noslēgumā vienmēr skaidrs aicinājums sazināties un pieteikt apskati.
- Ja informācija nav ievadīta, to NEDRĪKST izdomāt un NEDRĪKST veidot tukšu sadaļu — sadaļu vienkārši izlaid.
- NEDRĪKST izdomāt nevienu faktu, kas nav datos vai aģenta piezīmēs: ne attālumus, ne infrastruktūru (skolas, veikali, transports), ne apkures veidu, ne priekšrocības.
- Neizmanto tukšas vai pārspīlētas frāzes bez seguma datos.
- Cenu piemini tikai, ja tā norādīta datos.

DATI (CRM formas lauki):
{$dataJson}

AĢENTA PIEZĪMES (var būt tukšas; advantages=atrašanās/piekļuve/skats, technical=apkure/ūdens/kanalizācija/stāvoklis/gads, investment=iegādes nosacījumi/attīstība, extra=cits svarīgais):
{$notesJson}

ESOŠAIS APRAKSTS (tikai saīsināšanas/pārliecinošāka režīmiem):
{$currentJson}

4.3. WEB SLUDINĀJUMA STRUKTŪRA ("description" — HTML, tikai <p>/<br>/<strong>/<ul>/<li> tagi, bez Markdown):
Secība: Virsraksts (atsevišķā "title" laukā) → īss pārliecinošs ievads → Galvenie parametri (atsevišķs bloks) → Detalizēts apraksts → Telpas/aprīkojums ar aizzīmēm (<ul><li>) → Apkure un izmaksas (ja zināmas) → Konstrukcija/tehniskais stāvoklis (ja zināms) → Atrašanās vieta un apkārtne → Īpaši izdevīgi iegādes nosacījumi (TIKAI ja ievadīti) → Noslēguma pārdošanas arguments → Aicinājums sazināties/pieteikt apskati. Pēc LV daļas tāds pats EN tulkojums, balstīts TIKAI uz tiem pašiem faktiem: <p><strong>[LV]</strong><br>...</p> tad <p><strong>[EN]</strong><br>...</p>.

4.4. SS.COM STRUKTŪRA ("description_ss" — vienkāršs teksts bez HTML, kompakts bet ar skaidriem blokiem):
Īss uzmanību piesaistošs virsraksts (pirmā rinda) → 2–4 teikumu galvenais apraksts → Svarīgākie parametri katrs savā rindā → Galvenās priekšrocības ar aizzīmēm ("- " katras rindas sākumā) → Svarīgākie iegādes nosacījumi (ja attiecināms) → Atrašanās vietas priekšrocības → Īss aicinājums sazināties.

ATGRIEZT TIKAI JSON ar šādām atslēgām:
- "description": WEB variants pēc 4.3. struktūras (HTML).
- "description_ss": ss.com variants pēc 4.4. struktūras (tīrs teksts).
- "title": īss sludinājuma virsraksta variants (maks. ~60 rakstzīmes, bez cenas, latviski).
- "facebook": īss Facebook ieraksts (2-4 teikumi, draudzīgs tonis, emoji tikai ja der, bez izdomātiem faktiem).
- "instagram": īss uzrunājošs Instagram/Reels apraksts (1-3 teikumi, 3-6 atbilstoši hashtag, piem. #nekustamieipasumi #pardodlaimigs).
PROMPT;
    }
}
