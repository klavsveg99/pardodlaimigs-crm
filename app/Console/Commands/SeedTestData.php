<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\Deal;
use App\Models\PropertyCache;
use App\Models\Task;
use App\Models\User;
use App\Models\Viewing;
use App\Models\WpformEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeedTestData extends Command
{
    protected $signature = 'pdc:seed-test-data
        {--fresh : Delete existing TEST data before seeding}
        {--clients=12 : Number of test clients}
        {--properties=12 : Number of test properties}
        {--tasks=15 : Number of test tasks}
        {--viewings=12 : Number of test viewings}
        {--users=3 : Number of test agents}';

    protected $description = 'Seed bunch of TEST data for all CRM types (local + prod) – prefixed with [TEST] for easy cleanup';

    public function handle(): int
    {
        $isFresh = (bool) $this->option('fresh');

        if ($isFresh) {
            $this->warn('Deleting existing [TEST] data...');
            // Must delete in FK order
            Task::where('title', 'like', '[TEST]%')->delete();
            Viewing::where('notes_md', 'like', '[TEST]%')->orWhere('notes_md', 'like', '%[TEST]%')->delete();
            // Deals with [TEST] title or attached to a [TEST] client
            $dealIds = Deal::where('title', 'like', '[TEST]%')
                ->orWhereIn('client_id', Client::withTrashed()->where('name', 'like', '[TEST]%')->pluck('id'))
                ->pluck('id');
            if ($dealIds->isNotEmpty()) {
                Deal::whereIn('id', $dealIds)->delete();
            }
            // WpformEntry with [TEST] in form_name
            WpformEntry::where('form_name', 'like', '[TEST]%')->delete();
            // PropertyCache fallback with [TEST]
            PropertyCache::where('title', 'like', '[TEST]%')->delete();
            // CrmProperty with [TEST] title
            $props = CrmProperty::where('title', 'like', '[TEST]%')->get();
            foreach ($props as $p) {
                $p->attachments()->delete();
                // also delete stored files for those attachments already deleted above, but keep storage cleanup
                $p->delete();
            }
            // Clients with [TEST] — including already-soft-deleted (trashed) ones, so reruns
            // after a CleanupTestData don't leave hidden junk rows behind.
            $clients = Client::withTrashed()->where('name', 'like', '[TEST]%')->get();
            foreach ($clients as $c) {
                $c->attachments()->delete();
                $c->forceDelete();
            }
            // Users with test.agent
            User::where('email', 'like', 'test.agent%')->delete();
            User::where('email', 'like', 'headless_%')->delete();
            // Orphan activities left behind by previous runs: the [TEST] clients they reference were
            // force-deleted above (e.g. viewing_booked events from viewings that no longer exist).
            // A dangling client_id is unambiguous — never touch real activities that reference a live client.
            $liveClientIds = Client::withTrashed()->pluck('id');
            Activity::where(function ($q) use ($liveClientIds) {
                $q->whereNotNull('client_id')
                    ->whereNotIn('client_id', $liveClientIds);
            })->delete();
            // Clean test attachments files left
            Attachment::where('path', 'like', 'attachments/test/%')->delete();
            $this->info('Existing TEST data deleted.');
        }

        $faker = fake('lv_LV');
        // Fallback to en if lv not available
        if (! $faker) {
            $faker = fake();
        }

        // Ensure we have at least one real owner (admin) for FKs
        $owner = User::first() ?? User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
        $agents = collect();

        $usersCount = (int) $this->option('users');
        $this->info("Seeding {$usersCount} test agents...");
        for ($i = 1; $i <= $usersCount; $i++) {
            $email = "test.agent{$i}.".Str::random(4).'@example.com';
            $user = User::create([
                'name' => "[TEST] Aģents {$i} ".$faker->firstName().' '.$faker->lastName(),
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => $i === 1 ? 'admin' : 'aģents',
                'phone' => '+371 2'.$faker->numerify('#######'),
                'position' => $faker->randomElement(['Aģents', 'Vecākais aģents', 'Mārketings']),
                'description' => '[TEST] '.$faker->sentence(12),
            ]);
            $agents->push($user);
        }
        $allUsers = User::all();
        $this->info("Agents seeded: {$agents->count()} (total users: {$allUsers->count()})");

        // Clients
        $clientsCount = (int) $this->option('clients');
        $this->info("Seeding {$clientsCount} test clients...");
        $clients = collect();
        $sources = ['Tīmekļa vietne', 'Facebook', 'Instagram', 'Google', 'Draugu ieteikums', 'Cits'];
        for ($i = 0; $i < $clientsCount; $i++) {
            $c = Client::create([
                'name' => '[TEST] '.$faker->firstName().' '.$faker->lastName(),
                'phone' => '+371 2'.$faker->numerify('#######'),
                'email' => $faker->unique()->safeEmail(),
                'personas_kods' => $faker->numerify('######-#####'),
                'source' => $faker->randomElement($sources),
                'marketing_consent' => $faker->boolean(70),
                'notes_md' => "[TEST] Klients izveidots testam\n\n".$faker->paragraph(3),
                'owner_user_id' => $faker->randomElement($allUsers)->id,
            ]);
            // Attach 0-2 dummy files
            if ($faker->boolean(30)) {
                $this->createPlaceholderAttachment($c, $faker);
            }
            $clients->push($c);
        }
        $this->info("Clients seeded: {$clients->count()}");

        // CrmProperties
        $propsCount = (int) $this->option('properties');
        $this->info("Seeding {$propsCount} test properties...");
        $categories = array_keys(CrmProperty::CATEGORIES);
        $statuses = ['draft', 'published', 'published', 'published']; // bias to published
        $cities = ['Rīga', 'Jūrmala', 'Saldus', 'Liepāja', 'Ventspils', 'Jelgava'];
        $properties = collect();
        for ($i = 0; $i < $propsCount; $i++) {
            $cat = $faker->randomElement($categories);
            $city = $faker->randomElement($cities);
            $p = CrmProperty::create([
                'title' => '[TEST] '.$cat.' '.$city.' - '.$faker->streetAddress(),
                'slug' => 'test-'.Str::slug($faker->words(3, true)).'-'.Str::random(6),
                'description' => '[TEST] '.$faker->paragraphs(2, true),
                'price_eur' => $faker->numberBetween(45, 550) * 1000 + $faker->numberBetween(0, 900),
                'price_cents' => 0, // will be set via price_eur
                'currency' => 'EUR',
                'category' => $cat,
                'status' => $faker->randomElement($statuses),
                'beds' => $faker->numberBetween(1, 5),
                'baths' => $faker->numberBetween(1, 3),
                'size_m2' => $faker->numberBetween(35, 220),
                'land_m2' => in_array($cat, ['Zeme', 'Māja']) ? $faker->numberBetween(500, 5000) : null,
                'kadastra_nr' => $faker->numerify('####-###-####'),
                'city' => $city,
                'address' => $faker->streetAddress().', '.$city,
                'lat' => $faker->latitude(56.8, 57.3),
                'lng' => $faker->longitude(21.5, 24.5),
                'owner_user_id' => $faker->randomElement($allUsers)->id,
                'sort_order' => $i,
            ]);
            // Attach 2-5 placeholder images
            $imgCount = $faker->numberBetween(2, 5);
            for ($j = 0; $j < $imgCount; $j++) {
                $this->createPlaceholderAttachment($p, $faker, $j);
            }
            // Randomly link 1-2 clients as sellers
            if ($clients->isNotEmpty() && $faker->boolean(60)) {
                $linked = $clients->random(min(2, $clients->count()));
                if (! is_iterable($linked) || $linked instanceof Client) {
                    $linked = collect([$linked]);
                }
                foreach ($linked as $lc) {
                    try {
                        $p->clients()->attach($lc->id, ['relation' => 'seller', 'notes_md' => '[TEST] seller link']);
                    } catch (\Exception $e) {
                    }
                }
            }
            $properties->push($p);
        }
        $this->info("Properties seeded: {$properties->count()}");

        // Add sold properties for Top 5 agents widget (septembris 2026)
        $this->info('Seeding sold properties for Top 5 widget...');
        $soldAgents = $agents->isNotEmpty() ? $agents : collect([$owner]);
        $soldCount = min(8, $soldAgents->count() * 2);
        $soldProperties = collect();
        for ($i = 0; $i < $soldCount; $i++) {
            $agent = $faker->randomElement($soldAgents);
            $priceEur = $faker->numberBetween(45000, 550000);
            $commissionEur = round($priceEur * ($faker->randomFloat(2, 0.01, 0.08)), 2);
            $soldProp = CrmProperty::create([
                'title' => '[TEST] Pārdots īpašums '.($i + 1).' — '.$faker->streetAddress(),
                'slug' => 'test-sold-'.Str::slug($faker->words(3, true)).'-'.Str::random(6),
                'description' => '[TEST] Pārdots īpašums '.$faker->paragraph(2),
                'price_eur' => $priceEur,
                'price_cents' => 0,
                'currency' => 'EUR',
                'category' => $faker->randomElement($categories),
                'status' => 'sold',
                'beds' => $faker->numberBetween(1, 5),
                'baths' => $faker->numberBetween(1, 3),
                'size_m2' => $faker->numberBetween(35, 220),
                'city' => $faker->randomElement($cities),
                'address' => $faker->streetAddress().', '.$faker->randomElement($cities),
                'lat' => $faker->latitude(56.8, 57.3),
                'lng' => $faker->longitude(21.5, 24.5),
                'owner_user_id' => $agent->id,
                'sold_at' => now()->startOfMonth()->addDays($faker->numberBetween(1, 10))->setHour(10),
                'final_price_eur' => $priceEur,
                'commission_eur' => $commissionEur,
                'sort_order' => 1000 + $i,
            ]);
            $soldProperties->push($soldProp);
            // Link clients as buyers
            if ($clients->isNotEmpty() && $faker->boolean(70)) {
                $buyer = $clients->random();
                try {
                    $soldProp->clients()->attach($buyer->id, ['relation' => 'buyer', 'notes_md' => '[TEST] buyer link']);
                } catch (\Exception $e) {
                }
            }
        }
        $this->info("Sold properties seeded: {$soldProperties->count()}");

        // Deals
        $this->info('Seeding test deals...');
        $dealStages = array_keys(Deal::STAGES);
        $dealTitles = ['Pārdošana', 'Iegāde', 'Atsavināšana', 'Izmaiņa', 'Ceļa segums', 'Sastāva maiņa'];
        // Ensure every stage is represented at least once so the pipeline looks full,
        // then pad with random stages up to a demo count.
        $stagesPool = $dealStages;
        for ($i = 0; $i < 18; $i++) {
            if ($stagesPool === []) {
                $stagesPool = $dealStages;
            }
            $stage = array_shift($stagesPool);
            $client = $clients->random();
            $value = $faker->numberBetween(45000, 550000);
            $deal = Deal::create([
                'title' => '[TEST] '.$faker->randomElement($dealTitles).' — '.$faker->streetAddress(),
                'client_id' => $client->id,
                'owner_user_id' => $faker->randomElement($allUsers)->id,
                'stage' => $stage,
                'value_eur' => $value,
                'currency' => 'EUR',
                'lead_source' => $faker->randomElement(['internal', 'external']),
            ]);

            if ($stage === 'pardots') {
                // Closed deals this month (feed CategoryLeaders + deal activity feed)
                $closedAt = now()->startOfMonth()->addDays($faker->numberBetween(0, now()->day - 1))->setHour($faker->numberBetween(9, 17));
                $deal->update([
                    'closed_at' => $closedAt,
                    'expected_close_date' => $closedAt->startOfDay(),
                ]);
            } elseif ($i < 3) {
                // A few deals due to close today (feed TodayPriorities 'Darījums' section)
                $deal->update([
                    'expected_close_date' => now()->startOfDay(),
                    'closed_at' => null,
                ]);
            } else {
                $deal->update([
                    'expected_close_date' => Carbon::instance($faker->dateTimeBetween('-7 days', '+21 days'))->startOfDay(),
                    'closed_at' => null,
                ]);
            }
        }
        $this->info('Test deals seeded: '.Deal::where('title', 'like', '[TEST]%')->count());

        // Tasks
        $tasksCount = (int) $this->option('tasks');
        $this->info("Seeding {$tasksCount} test tasks...");
        $taskTitles = ['Zvanīt klientam', 'Sagatavot līgumu', 'Noorganizēt apskati', 'Atjaunināt sludinājumu', 'Pārbaudīt dokumentus', 'Nosūtīt piedāvājumu'];
        for ($i = 0; $i < $tasksCount; $i++) {
            $due = match ($faker->numberBetween(0, 4)) {
                0 => now()->subDays($faker->numberBetween(1, 5))->setHour($faker->numberBetween(9, 16)),
                1 => now()->addHours($faker->numberBetween(1, 24)),
                2 => now()->addDays($faker->numberBetween(1, 7)),
                default => $faker->dateTimeBetween('-2 days', '+7 days'),
            };
            $completed = $faker->boolean(20) ? now()->subHours($faker->numberBetween(1, 48)) : null;
            Task::create([
                'title' => '[TEST] '.$faker->randomElement($taskTitles).' #'.($i + 1),
                'body' => $faker->paragraph(2),
                'due_at' => $due,
                'completed_at' => $completed,
                'assigned_user_id' => $faker->randomElement($allUsers)->id,
                'created_by_user_id' => $owner->id,
                'client_id' => $faker->boolean(80) ? $faker->randomElement($clients)->id : null,
                'deal_id' => null,
                'property_id' => null,
            ]);
        }
        $this->info('Tasks seeded.');

        // Viewings
        $viewingsCount = (int) $this->option('viewings');
        $this->info("Seeding {$viewingsCount} test viewings...");
        // Ensure at least one PropertyCache exists for FK
        $fallbackPropertyCacheId = PropertyCache::first()?->id;
        if (! $fallbackPropertyCacheId) {
            $fallbackPropertyCacheId = PropertyCache::create([
                'title' => '[TEST] Fallback Property',
                'slug' => 'test-fallback-'.Str::random(6),
                'status' => 'publish',
                'price_cents' => 10000000,
                'currency' => 'EUR',
                'city' => 'Rīga',
            ])->id;
            $this->info("Created fallback PropertyCache id {$fallbackPropertyCacheId} for viewings FK");
        }
        $statusesV = ['scheduled', 'done', 'cancelled', 'no_show'];
        for ($i = 0; $i < $viewingsCount; $i++) {
            $sched = match ($faker->numberBetween(0, 3)) {
                0 => now()->subDays($faker->numberBetween(1, 3))->setHour($faker->numberBetween(10, 18)),
                1 => now()->addDays($faker->numberBetween(0, 3))->setHour($faker->numberBetween(10, 18)),
                2 => $faker->dateTimeBetween('-7 days', '+7 days'),
                default => now()->addDay()->setHour(14),
            };
            $prop = $faker->boolean(70) && $properties->isNotEmpty() ? $faker->randomElement($properties) : null;
            $propCacheId = $fallbackPropertyCacheId;
            if ($prop) {
                $pc = PropertyCache::where('title', $prop->title)->first();
                if ($pc) {
                    $propCacheId = $pc->id;
                }
            }
            // Fallback to random PropertyCache if still null
            if (! $propCacheId) {
                $propCacheId = PropertyCache::inRandomOrder()->first()?->id ?? $fallbackPropertyCacheId;
            }
            Viewing::create([
                'property_id' => $propCacheId,
                'client_id' => $faker->randomElement($clients)->id,
                'agent_user_id' => $faker->randomElement($allUsers)->id,
                'scheduled_at' => $sched,
                'duration_min' => $faker->randomElement([30, 45, 60]),
                'status' => $faker->randomElement($statusesV),
                'notes_md' => '[TEST] Apskate '.$faker->sentence(6),
            ]);
        }
        $this->info('Viewings seeded.');

        // WpformEntries (optional, 5)
        $this->info('Seeding 5 test WPForm entries...');
        for ($i = 0; $i < 5; $i++) {
            WpformEntry::create([
                'external_id' => 'test-'.Str::random(8),
                'entry_id' => 90000 + $i,
                'form_id' => $faker->numberBetween(1, 3),
                'form_name' => '[TEST] Forma '.$faker->word(),
                'status' => $faker->randomElement(['new', 'viewed', 'review']),
                'viewed' => $faker->boolean(),
                'starred' => false,
                'ip_address' => $faker->ipv4(),
                'fields' => [
                    ['name' => 'Vārds', 'value' => $faker->firstName()],
                    ['name' => 'Tālrunis', 'value' => '+371 2'.$faker->numerify('#######')],
                    ['name' => 'E-pasts', 'value' => $faker->safeEmail()],
                    ['name' => 'Ziņa', 'value' => $faker->sentence(8)],
                ],
                'client_id' => $faker->boolean(50) ? $faker->randomElement($clients)->id : null,
                'created_at' => $faker->dateTimeBetween('-7 days', 'now'),
                'updated_at' => now(),
            ]);
        }

        $this->info('Done! Test data summary:');
        $this->table(['Type', 'Count'], [
            ['Users (agents)', User::where('email', 'like', 'test.agent%')->count()],
            ['Clients [TEST]', Client::where('name', 'like', '[TEST]%')->count()],
            ['Properties [TEST]', CrmProperty::where('title', 'like', '[TEST]%')->count()],
            ['Deals [TEST]', Deal::where('title', 'like', '[TEST]%')->count()],
            ['Tasks [TEST]', Task::where('title', 'like', '[TEST]%')->count()],
            ['Viewings [TEST]', Viewing::where('notes_md', 'like', '[TEST]%')->count()],
            ['Attachments (test/*)', Attachment::where('path', 'like', 'attachments/test/%')->count()],
        ]);

        return self::SUCCESS;
    }

    private function createPlaceholderAttachment($model, $faker, int $index = 0): void
    {
        // Use picsum placeholder, store locally to have real file
        $width = 800;
        $height = 600;
        $seed = Str::random(8);
        $url = "https://picsum.photos/seed/{$seed}/{$width}/{$height}";
        $path = "attachments/test/{$model->getTable()}/{$model->id}/".Str::random(12).'.jpg';

        try {
            $tmp = @file_get_contents($url);
            if ($tmp !== false) {
                Storage::disk('public')->put($path, $tmp);
            } else {
                // fallback: create empty file if fetch fails (offline)
                Storage::disk('public')->put($path, '');
            }
        } catch (\Exception $e) {
            Storage::disk('public')->put($path, '');
        }

        $model->attachments()->create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => 'TEST-'.$faker->word()."-{$index}.jpg",
            'mime_type' => 'image/jpeg',
            'size' => Storage::disk('public')->size($path) ?: 0,
            'sort_order' => $index,
        ]);
    }
}
