<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WpformEntry;
use App\Services\Site\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    private function storeForLocale(Request $request, string $locale): RedirectResponse
    {
        if ($request->filled('website')) {
            return redirect($this->content->localizedPath($locale, '/paldies/'));
        }

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
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'string', 'max:0'],
        ]);

        $fields = [
            ['id' => 1, 'name' => 'Īpašuma veids', 'type' => 'select', 'value' => $types[$data['property_type']]],
            ['id' => 2, 'name' => 'Platība', 'type' => 'text', 'value' => (string) $data['area']],
            ['id' => 3, 'name' => 'Atrašanās vieta', 'type' => 'text', 'value' => $data['location']],
            ['id' => 4, 'name' => 'Īpašuma apraksts', 'type' => 'textarea', 'value' => $data['description']],
            ['id' => 5, 'name' => 'Kadastra numurs', 'type' => 'text', 'value' => $data['cadastre']],
            ['id' => 6, 'name' => 'Vēlamā cena par īpašumu', 'type' => 'text', 'value' => $data['desired_price'] ?? ''],
            ['id' => 7, 'name' => 'Vēlos pārdot steidzami', 'type' => 'checkbox', 'value' => ($data['urgent'] ?? false) ? 'Jā' : 'Nē'],
            ['id' => 8, 'name' => 'Jūsu vārds', 'type' => 'text', 'value' => $data['name']],
            ['id' => 9, 'name' => 'Telefona numurs', 'type' => 'tel', 'value' => $data['phone']],
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

        return redirect($this->content->localizedPath($locale, '/paldies/'));
    }
}
