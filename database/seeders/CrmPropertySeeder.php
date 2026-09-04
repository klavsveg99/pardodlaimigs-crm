<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CrmProperty;
use App\Models\User;
use Illuminate\Database\Seeder;

class CrmPropertySeeder extends Seeder
{
    public function run(): void
    {
        $agent = User::where('email', 'info@pardodlaimigs.lv')->first();

        $properties = [
            [
                'title' => '"Līdaciņas", atpūtas komplekss pie ūdens Saldus novadā',
                'slug' => 'lidacinas-atputas-komplekss-pie-udens-saldus-novada',
                'price_eur' => 290000,
                'category' => 'Zeme',
                'status' => 'published',
                'beds' => 2,
                'baths' => 1,
                'size_m2' => 85,
                'land_m2' => 16200,
                'city' => 'Saldus novads',
                'address' => '"Līdaciņas", Novadnieku pagasts, Saldus novads, LV-3801',
                'image_urls' => ['https://pardodlaimigs.lv/wp-content/webp-express/webp-images/uploads/2026/08/440d62df4195454ce55f4d1eabd079cbba99ae1c-370x220.jpg.webp'],
                'lead_source' => 'internal',
                'lead_owner' => 'Roberts Evarsons',
                'sort_order' => 1,
            ],
            [
                'title' => '"Siguldas", foreļu dīķi lauku īpašums, Saldus nov.',
                'slug' => 'siguldas-forelu-diki-lauku-ipasums-saldus-nov',
                'price_eur' => 165000,
                'category' => 'Zeme',
                'status' => 'published',
                'beds' => 4,
                'baths' => 1,
                'size_m2' => 121,
                'land_m2' => 153000,
                'city' => 'Saldus novads',
                'address' => '"Siguldas", Sātiņi, Novadnieku pag., Saldus nov.',
                'image_urls' => ['https://pardodlaimigs.lv/wp-content/webp-express/webp-images/uploads/2026/08/d0628f9fc6b22c2426db5a305a31b093ce783dc7-370x220.jpg.webp'],
                'lead_source' => 'internal',
                'lead_owner' => 'Roberts Evarsons',
                'sort_order' => 2,
            ],
            [
                'title' => 'Zemesgabals Mērsragā',
                'slug' => 'zemesgabals-mersraga',
                'price_eur' => 55000,
                'category' => 'Zeme',
                'status' => 'published',
                'beds' => null,
                'baths' => null,
                'size_m2' => null,
                'land_m2' => 31700,
                'city' => 'Mērsrags',
                'address' => '"Sīpoliņi", Mērsraga pag., Talsu nov.',
                'image_urls' => ['https://pardodlaimigs.lv/wp-content/webp-express/webp-images/uploads/2026/08/a2a299c9ab6dfa2dd00fe68f566b0d4dc7c8c95e-370x220.jpg.webp'],
                'lead_source' => 'internal',
                'lead_owner' => 'Roberts Evarsons',
                'sort_order' => 3,
            ],
            [
                'title' => 'Zeme un maza mājiņa pie jūras Mērsragā',
                'slug' => 'zeme-un-maza-majina-pie-juras-mersraga',
                'price_eur' => 220000,
                'category' => 'Zeme',
                'status' => 'published',
                'beds' => 2,
                'baths' => 1,
                'size_m2' => 50,
                'land_m2' => 11000,
                'city' => 'Mērsrags',
                'address' => 'Bākas iela 49, Mērsrags, Mērsraga pagasts, Talsu novads, LV-3284',
                'image_urls' => ['https://pardodlaimigs.lv/wp-content/webp-express/webp-images/uploads/2026/08/49937c37cc7ea950fca4f6edaa210eb4e1c8df61-370x220.jpg.webp'],
                'lead_source' => 'internal',
                'lead_owner' => 'Roberts Evarsons',
                'sort_order' => 4,
            ],
            [
                'title' => 'Investīciju attīstībai 1ha Saldū',
                'slug' => 'investiciju-attistibai-1ha-saldu',
                'price_eur' => 535000,
                'category' => 'Zeme',
                'status' => 'published',
                'beds' => null,
                'baths' => null,
                'size_m2' => null,
                'land_m2' => 10400,
                'city' => 'Saldus',
                'address' => 'Kuldīgas iela 55A, Saldus, Saldus pilsēta, Saldus novads, LV-3801',
                'image_urls' => ['https://pardodlaimigs.lv/wp-content/webp-express/webp-images/uploads/2026/08/b0c9bd2514e2cad10f9f64356eab7abe3c9a3f3f-370x220.jpg.webp'],
                'lead_source' => 'internal',
                'lead_owner' => 'Roberts Evarsons',
                'sort_order' => 5,
            ],
            [
                'title' => 'Komerctelpas Saldus pilsētā, Avotu iela 4ab',
                'slug' => 'komerctelpas-saldus-pilseta-avotu-iela-4ab',
                'price_eur' => 90000,
                'category' => 'Komerciāls',
                'status' => 'published',
                'beds' => null,
                'baths' => null,
                'size_m2' => 634,
                'land_m2' => 1000,
                'city' => 'Saldus',
                'address' => 'Avotu iela 4B, Saldus, Saldus pilsēta, Saldus novads, LV-3801',
                'image_urls' => ['https://pardodlaimigs.lv/wp-content/webp-express/webp-images/uploads/2026/08/874d37318d2f475c58bcf5768c81f324cc1c7636-370x220.jpg.webp'],
                'lead_source' => 'internal',
                'lead_owner' => 'Roberts Evarsons',
                'sort_order' => 6,
            ],
            [
                'title' => 'Komerckomplekss Saldus',
                'slug' => 'komerckomplekss-saldus',
                'price_eur' => 420000,
                'category' => 'Komerciāls',
                'status' => 'published',
                'beds' => null,
                'baths' => null,
                'size_m2' => 2000,
                'land_m2' => 15000,
                'city' => 'Saldus',
                'address' => 'Mazā iela, Saldus, Saldus pilsēta, Saldus novads, LV-3801',
                'image_urls' => ['https://pardodlaimigs.lv/wp-content/webp-express/webp-images/uploads/2026/08/a1b05ebd005b0a9875f893bd573f7733e0256a78-370x220.jpg.webp'],
                'lead_source' => 'internal',
                'lead_owner' => 'Roberts Evarsons',
                'sort_order' => 7,
            ],
            [
                'title' => '40 zemes vienību projekts Saldū',
                'slug' => '40-zemes-vienibu-projekts-saldu',
                'price_eur' => 150000,
                'category' => 'Zeme',
                'status' => 'published',
                'beds' => null,
                'baths' => null,
                'size_m2' => null,
                'land_m2' => 45900,
                'city' => 'Saldus',
                'address' => 'J. Rozentāla iela 31, Saldus, Saldus pilsēta, Saldus novads, LV-3801',
                'image_urls' => ['https://pardodlaimigs.lv/wp-content/webp-express/webp-images/uploads/2026/08/1cf499acbfca6475f3b8156c1812de0944124dec-370x220.jpg.webp'],
                'lead_source' => 'internal',
                'lead_owner' => 'Roberts Evarsons',
                'sort_order' => 8,
            ],
            [
                'title' => '74ha LIZ + Meži Saldus nov.',
                'slug' => '74ha-liz-mezi-saldus-nov',
                'price_eur' => 276000,
                'category' => 'Zeme',
                'status' => 'published',
                'beds' => null,
                'baths' => null,
                'size_m2' => null,
                'land_m2' => 740000,
                'city' => 'Saldus novads',
                'address' => 'Kursīšu pagasts, Saldus novads',
                'image_urls' => ['https://pardodlaimigs.lv/wp-content/webp-express/webp-images/uploads/2026/08/4a54508ed573410cfa930b62ac3705554b9584be-370x220.jpg.webp'],
                'lead_source' => 'internal',
                'lead_owner' => 'Roberts Evarsons',
                'sort_order' => 9,
            ],
            [
                'title' => 'Veikala telpas Saldū, Dzirnavu ielā 1A',
                'slug' => 'veikala-telpas-saldu-dzirnavu-iela-1a',
                'price_eur' => 140000,
                'category' => 'Komerciāls',
                'status' => 'published',
                'beds' => null,
                'baths' => null,
                'size_m2' => 545,
                'land_m2' => 1000,
                'city' => 'Saldus',
                'address' => 'Dzirnavu iela 1A, Saldus',
                'image_urls' => ['https://pardodlaimigs.lv/wp-content/webp-express/webp-images/uploads/2026/08/91ea247a931b01cc932627bd0087e8deaf468ebf-370x220.jpg.webp'],
                'lead_source' => 'internal',
                'lead_owner' => 'Roberts Evarsons',
                'sort_order' => 10,
            ],
            [
                'title' => '"Pakalniņi", lauku īpašums Saldus nov.',
                'slug' => 'pakalnini-lauku-ipasums-saldus-nov',
                'price_eur' => 150000,
                'category' => 'Zeme',
                'status' => 'published',
                'beds' => 5,
                'baths' => null,
                'size_m2' => 175,
                'land_m2' => 15700,
                'city' => 'Saldus novads',
                'address' => 'Lutriņi, Pakalniņi',
                'image_urls' => ['https://pardodlaimigs.lv/wp-content/webp-express/webp-images/uploads/2026/08/d80bb2f6abb97453ea01671284d2bd3ad4272e88-370x220.jpg.webp'],
                'lead_source' => 'internal',
                'lead_owner' => 'Roberts Evarsons',
                'sort_order' => 11,
            ],
        ];

        $galleries = $this->loadGalleries();

        foreach ($properties as $data) {
            $existing = CrmProperty::where('slug', $data['slug'])->first();
            if ($existing) {
                $existing->update([
                    'owner_user_id' => $agent?->id,
                    'lead_owner' => $data['lead_owner'],
                    'image_urls' => $galleries[$data['slug']] ?? $existing->image_urls,
                ]);
                continue;
            }

            $data['price_cents'] = (int) ($data['price_eur'] * 100);
            $data['owner_user_id'] = $agent?->id;
            $data['image_urls'] = $galleries[$data['slug']] ?? ($data['image_urls'] ?? []);
            CrmProperty::create($data);
        }

        $this->command->info("Seeded " . count($properties) . " CRM properties.");
    }

    private function loadGalleries(): array
    {
        $path = database_path('seeders/data/property-galleries.json');
        if (! file_exists($path)) {
            return [];
        }

        $data = json_decode(file_get_contents($path), true);
        $galleries = [];

        foreach ($data['properties'] ?? [] as $property) {
            if (isset($property['slug'], $property['images'])) {
                $galleries[$property['slug']] = $property['images'];
            }
        }

        return $galleries;
    }
}
