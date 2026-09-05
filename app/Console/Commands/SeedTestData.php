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
use Illuminate\Support\Str;

class SeedTestData extends Command
{
    protected $signature = 'pdc:seed-test-data
        {--fresh : Delete existing TEST data before seeding}
        {--clients=12 : Number of test clients}
        {--tasks=15 : Number of test tasks}
        {--viewings=12 : Number of test viewings}
        {--users=3 : Number of test agents}';

    protected $description = 'Seed TEST agents, clients, deals, tasks and viewings – prefixed with [TEST] for easy cleanup. Never creates fake properties or contact form submissions; viewings relate to existing (real) properties.';

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
            $clients->push($c);
        }
        $this->info("Clients seeded: {$clients->count()}");

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

        // Force tasks so the "Šodien jāizdara" table and stat boxes never render empty:
        // two open tasks due today, one overdue (shows the "Nokavētas" warning).
        // Times are pinned to today's date so they match whereDate/whereBetween("today")
        // no matter what time of day the seed runs.
        $todayAnchor = now()->startOfDay();
        foreach ([11, 14] as $hour) {
            Task::create([
                'title' => '[TEST] '.$faker->randomElement($taskTitles).' (šodien)',
                'body' => $faker->paragraph(2),
                'due_at' => $todayAnchor->setHour($hour)->setMinute(30),
                'completed_at' => null,
                'assigned_user_id' => $faker->randomElement($allUsers)->id,
                'created_by_user_id' => $owner->id,
                'client_id' => $faker->randomElement($clients)->id,
                'deal_id' => null,
                'property_id' => null,
            ]);
        }
        Task::create([
            'title' => '[TEST] Pārbaudīt dokumentus (nokavēts)',
            'body' => $faker->paragraph(2),
            'due_at' => now()->subHours(3),
            'completed_at' => null,
            'assigned_user_id' => $faker->randomElement($allUsers)->id,
            'created_by_user_id' => $owner->id,
            'client_id' => $faker->randomElement($clients)->id,
            'deal_id' => null,
            'property_id' => null,
        ]);

        // Viewings
        $viewingsCount = (int) $this->option('viewings');
        $this->info("Seeding {$viewingsCount} test viewings...");
        // Relate viewings to existing (real) cached properties; leave property_id
        // null when none exist rather than fabricating a fallback.
        $propertyCacheIds = PropertyCache::pluck('id');
        $statusesV = ['scheduled', 'done', 'cancelled', 'no_show'];
        for ($i = 0; $i < $viewingsCount; $i++) {
            $sched = match ($faker->numberBetween(0, 3)) {
                0 => now()->subDays($faker->numberBetween(1, 3))->setHour($faker->numberBetween(10, 18)),
                1 => now()->addDays($faker->numberBetween(0, 3))->setHour($faker->numberBetween(10, 18)),
                2 => $faker->dateTimeBetween('-7 days', '+7 days'),
                default => now()->addDay()->setHour(14),
            };
            $propCacheId = $propertyCacheIds->isNotEmpty() ? $propertyCacheIds->random() : null;
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

        // Force two viewings today so "Atvērtas apskates šodien" and TodayPriorities
        // always have rows, matching whereDate/whereBetween("today") at any run hour.
        $todayPropCacheId = $propertyCacheIds->isNotEmpty() ? $propertyCacheIds->first() : null;
        foreach ([10, 15] as $hour) {
            Viewing::create([
                'property_id' => $todayPropCacheId,
                'client_id' => $faker->randomElement($clients)->id,
                'agent_user_id' => $faker->randomElement($allUsers)->id,
                'scheduled_at' => $todayAnchor->setHour($hour)->setMinute(0),
                'duration_min' => 45,
                'status' => 'scheduled',
                'notes_md' => '[TEST] Apskate šodien (obligāta)',
            ]);
        }

        // WpformEntries are never seeded – they come from the live site's contact forms.

        $this->info('Done! Test data summary:');
        $this->table(['Type', 'Count'], [
            ['Users (agents)', User::where('email', 'like', 'test.agent%')->count()],
            ['Clients [TEST]', Client::where('name', 'like', '[TEST]%')->count()],
            ['Deals [TEST]', Deal::where('title', 'like', '[TEST]%')->count()],
            ['Tasks [TEST]', Task::where('title', 'like', '[TEST]%')->count()],
            ['Viewings [TEST]', Viewing::where('notes_md', 'like', '[TEST]%')->count()],
            ['Attachments (test/*)', Attachment::where('path', 'like', 'attachments/test/%')->count()],
        ]);

        return self::SUCCESS;
    }
}
