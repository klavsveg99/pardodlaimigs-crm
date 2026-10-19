<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WpformEntry;
use App\Services\Site\SiteContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class PublicSiteLeadController extends Controller
{
    public function __construct(private readonly SiteContent $content) {}

    public function store(Request $request): RedirectResponse
    {
        return $this->storeForLocale($request, 'lv');
    }

    public function localizedStore(Request $request, string $locale): RedirectResponse
    {
        return $this->storeForLocale($request, $this->content->locale($locale));
    }

    /**
     * JSON intake for the static frontend on /new/. It posts cross-origin to
     * the CRM, so the response is JSON (validation failures come back as 422).
     */
    public function storeApi(Request $request): JsonResponse
    {
        $locale = in_array($request->input('locale'), (array) config('site.locales'), true)
            ? (string) $request->input('locale')
            : 'lv';

        $this->createEntry($request, $locale);

        return response()->json(['ok' => true], 201);
    }

    private function storeForLocale(Request $request, string $locale): RedirectResponse
    {
        $this->createEntry($request, $locale);

        return redirect($this->content->localizedPath($locale, '/paldies/'));
    }

    private function createEntry(Request $request, string $locale): void
    {
        // Honeypot: a filled "website" field is a bot. Pretend it worked so the
        // bot does not learn it was rejected.
        if ($request->filled('website')) {
            return;
        }

        $copy = $this->content->strings($locale);
        $prefixes = (array) config('site.phone_prefixes');

        $types = [
            'apartment' => 'Dzīvoklis',
            'house' => 'Māja',
            'land' => 'Zeme',
            'forest' => 'Mežs',
        ];

        $data = $request->validate([
            'property_type' => ['required', 'string', Rule::in(array_keys($types))],
            'area' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'cadastre' => ['required', 'string', 'max:100'],
            'desired_price' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'urgent' => ['nullable', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
            'phone_prefix' => ['required', 'string', Rule::in(array_keys($prefixes))],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'string', 'max:0'],
        ]);

        // The national number has to be as long as the chosen country's numbers
        // are, otherwise the country code is what is wrong, not the number.
        $rule = $prefixes[$data['phone_prefix']];
        $digits = (string) preg_replace('/\D/', '', $data['phone']);
        if (strlen($digits) < $rule['min'] || strlen($digits) > $rule['max']) {
            throw ValidationException::withMessages(['phone' => $copy['formPhoneInvalid'] ?? 'Enter a valid phone number.']);
        }

        if (! $this->recaptchaPasses($request)) {
            throw ValidationException::withMessages(['recaptcha_token' => $copy['formCaptchaFailed'] ?? 'The security check failed.']);
        }

        $phone = '+'.$rule['dial'].' '.$digits;

        $fields = [
            ['id' => 1, 'name' => 'Īpašuma veids', 'type' => 'select', 'value' => $types[$data['property_type']]],
            ['id' => 2, 'name' => 'Platība', 'type' => 'text', 'value' => (string) $data['area']],
            ['id' => 3, 'name' => 'Atrašanās vieta', 'type' => 'text', 'value' => $data['location']],
            ['id' => 4, 'name' => 'Īpašuma apraksts', 'type' => 'textarea', 'value' => $data['description']],
            ['id' => 5, 'name' => 'Kadastra numurs', 'type' => 'text', 'value' => $data['cadastre']],
            ['id' => 6, 'name' => 'Vēlamā cena par īpašumu', 'type' => 'text', 'value' => $data['desired_price'] ?? ''],
            ['id' => 7, 'name' => 'Vēlos pārdot steidzami', 'type' => 'checkbox', 'value' => ($data['urgent'] ?? false) ? 'Jā' : 'Nē'],
            ['id' => 8, 'name' => 'Jūsu vārds', 'type' => 'text', 'value' => $data['name']],
            ['id' => 9, 'name' => 'Telefona numurs', 'type' => 'tel', 'value' => $phone],
            ['id' => 10, 'name' => 'E-pasts', 'type' => 'email', 'value' => $data['email']],
            ['id' => 11, 'name' => 'Papildu piezīmes', 'type' => 'textarea', 'value' => $data['comment'] ?? ''],
            ['id' => 12, 'name' => 'Privātuma politika', 'type' => 'checkbox', 'value' => 'Piekritu'],
        ];

        WpformEntry::query()->create([
            'external_id' => 'site:'.Str::uuid(),
            'entry_id' => (int) floor(microtime(true) * 1000),
            'form_id' => 0,
            'form_name' => 'Vietnes īpašuma vērtēšanas pieteikums',
            'status' => 'new',
            'viewed' => false,
            'starred' => false,
            'ip_address' => $request->ip(),
            'fields' => $fields,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * reCAPTCHA v3. Without a configured secret the check is skipped rather than
     * failed, so environments that do not carry the keys keep a working form.
     */
    private function recaptchaPasses(Request $request): bool
    {
        $secret = (string) config('services.recaptcha.secret');
        if ($secret === '') {
            return true;
        }

        $token = (string) $request->input('recaptcha_token');
        if ($token === '') {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(6)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
        } catch (Throwable) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $result = $response->json();
        $action = (string) config('services.recaptcha.action', 'valuation');

        return ($result['success'] ?? false) === true
            && ($result['action'] ?? $action) === $action
            && (float) ($result['score'] ?? 0) >= (float) config('services.recaptcha.threshold', 0.5);
    }
}
