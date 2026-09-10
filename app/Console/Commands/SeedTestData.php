<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use App\Models\Viewing;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedTestData extends Command
{
    protected $signature = 'pdc:seed-test-data
        {--fresh : Delete existing TEST data before seeding}
        {--clients=8 : Number of test clients}
        {--tasks=10 : Number of test tasks}
        {--viewings=8 : Number of test viewings}
        {--users=2 : Number of test agents}';

    protected $description = 'Seed [TEST] tagged clients, agents, tasks and viewings (no properties, no deals, no WPForm entries).';

    private const FIRST_NAMES = [
        'Ringla', 'Birgita', 'Minadora', 'Airita', 'Margons',
        'Jūlijans', 'Avita', 'Vingra', 'Alvīns', 'Berita',
        'Uldis', 'Sarmīte', 'Dainis', 'Zane', 'Edgars',
    ];

    private const LAST_NAMES = [
        'Melngailis', 'Laizāns', 'Mauriņa', 'Titāns', 'Ķempe',
        'Rubenis', 'Plotnieks', 'Garanča', 'Krastiņa', 'Kārkliņa',
        'Bērziņš', 'Kalniņa', 'Ozols', 'Liepiņa', 'Saulītis',
    ];

    private const TASK_TEMPLATES = [
        'Sagatavot līgumu #{n}',
        'Pārbaudīt dokumentus #{n}',
        'Nosūtīt piedāvājumu #{n}',
        'Zvanīt klientam #{n}',
        'Noorganizēt apskati #{n}',
    ];

    private const TASK_STATUSES = [
        'Plānots', 'Gaida atbildi', 'Saskaņošanā', 'Nokavēts',
    ];

    public function handle(): int
    {
        // Test datu sēšana ir aizliegta produkcijā — AIZLIEGTS pārrakstīt reālos
        // klientus/īpašumus. Ja tomēr vajag, palaid uz pagaidu DB kopijas.
        if (config('app.env') === 'production') {
            $this->error('Aizliegts: test datu sēšana produkcijā nav atļauta.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->cleanTestData();
        }

        $userCount = (int) $this->option('users');
        $clientCount = (int) $this->option('clients');
        $taskCount = (int) $this->option('tasks');
        $viewingCount = (int) $this->option('viewings');

        $this->info('Seeding [TEST] data (no properties / no deals / no WPForms)...');

        // Deterministic ordering so re-seeds produce stable IDs when --fresh is used.
        mt_srand(20260905);

        $agents = $this->seedAgents($userCount);
        $clients = $this->seedClients($clientCount, $agents);
        $this->seedTasks($taskCount, $agents, $clients);
        $this->seedViewings($viewingCount, $agents, $clients);

        $this->newLine();
        $this->info('Seed summary:');
        $this->line("  Agents:    {$userCount}");
        $this->line("  Clients:   {$clientCount}");
        $this->line("  Tasks:     {$taskCount}");
        $this->line("  Viewings:  {$viewingCount}");
        $this->newLine();
        $this->info('Done. Login as info@pardodlaimigs.lv / changeme');

        return self::SUCCESS;
    }

    protected function cleanTestData(): void
    {
        $this->warn('Removing existing [TEST] records...');

        // FK order: viewings → tasks → clients → users
        Viewing::whereHas('client', fn ($q) => $q->where('name', 'like', '[TEST]%'))->delete();
        Task::where('title', 'like', '[TEST]%')->delete();
        Client::withTrashed()->where('name', 'like', '[TEST]%')->forceDelete();
        User::where('email', 'like', 'test.agent%')->delete();

        $this->info('Existing [TEST] data cleared.');
    }

    /**
     * @return array<int, User>
     */
    protected function seedAgents(int $count): array
    {
        $agents = [];
        for ($i = 1; $i <= $count; $i++) {
            $name = "[TEST] Aģents {$i} {$this->randomFirst()} {$this->randomLast()}";
            $email = "test.agent{$i}." . Str::random(4) . '@example.com';
            $agents[] = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
            ]);
        }
        $this->info("Created {$count} test agents.");

        return $agents;
    }

    /**
     * @param  array<int, User>  $agents
     * @return array<int, Client>
     */
    protected function seedClients(int $count, array $agents): array
    {
        $clients = [];
        for ($i = 1; $i <= $count; $i++) {
            $first = $this->randomFirst();
            $last = $this->randomLast();
            $clients[] = Client::create([
                'name' => "[TEST] {$first} {$last}",
                'phone' => '+371 2' . mt_rand(1000000, 9999999),
                'email' => Str::lower("{$first}.{$last}.{$i}") . '@example.lv',
                'source' => $this->randomSource(),
                'owner_user_id' => $agents[array_rand($agents)]->id,
                'gdpr_consent_at' => now()->subDays(mt_rand(1, 90)),
            ]);
        }
        $this->info("Created {$count} test clients.");

        return $clients;
    }

    /**
     * @param  array<int, User>  $agents
     * @param  array<int, Client>  $clients
     */
    protected function seedTasks(int $count, array $agents, array $clients): void
    {
        // Force today + a few overdue so the "Šodien jāizdara" widget has data.
        $slots = $this->buildTaskSlots($count);

        for ($i = 1; $i <= $count; $i++) {
            $template = self::TASK_TEMPLATES[($i - 1) % count(self::TASK_TEMPLATES)];
            $title = str_replace('{n}', (string) $i, $template);
            $due = $slots[$i - 1] ?? now()->addDays(mt_rand(1, 14));

            Task::create([
                'title' => "[TEST] {$title}",
                'body' => 'Automātiski izveidots testa uzdevums.',
                'due_at' => $due,
                'assigned_user_id' => $agents[array_rand($agents)]->id,
                'created_by_user_id' => $agents[0]->id,
                'client_id' => $clients[array_rand($clients)]->id,
            ]);
        }
        $this->info("Created {$count} test tasks (3 overdue, rest today).");
    }

    /**
     * @param  array<int, User>  $agents
     * @param  array<int, Client>  $clients
     */
    protected function seedViewings(int $count, array $agents, array $clients): void
    {
        $slots = $this->buildViewingSlots($count);

        for ($i = 1; $i <= $count; $i++) {
            $when = $slots[$i - 1] ?? now()->addDays(mt_rand(1, 7));

            Viewing::create([
                'client_id' => $clients[array_rand($clients)]->id,
                'agent_user_id' => $agents[array_rand($agents)]->id,
                'scheduled_at' => $when,
                'duration_min' => [30, 45, 60][array_rand([30, 45, 60])],
                'status' => 'scheduled',
                'notes_md' => '[TEST] Automātiski izveidota apskate.',
            ]);
        }
        $this->info("Created {$count} test viewings (2 today, rest past/future).");
    }

    /**
     * 3 overdue (yesterday's tasks), the rest due today at deterministic times.
     *
     * @return array<int, Carbon>
     */
    protected function buildTaskSlots(int $count): array
    {
        $slots = [];
        $today = Carbon::today();

        // 3 overdue tasks spread across yesterday and earlier today
        $overdue = [
            $today->copy()->subDay()->setTime(18, 41),
            $today->copy()->subHours(2)->setTime(11, 30),
            $today->copy()->subHours(3)->setTime(14, 30),
        ];
        $slots = array_merge($slots, array_slice($overdue, 0, min(3, $count)));

        // Remaining slots all due today at fixed times so widget stays deterministic
        $todayTimes = ['10:41', '16:41', '09:15', '13:30', '15:00', '17:20', '08:45', '12:00', '14:00', '18:00'];
        foreach ($todayTimes as $time) {
            if (count($slots) >= $count) {
                break;
            }
            [$h, $m] = explode(':', $time);
            $slots[] = $today->copy()->setTime((int) $h, (int) $m);
        }

        return array_slice($slots, 0, $count);
    }

    /**
     * 2 viewings today, others spread yesterday and tomorrow.
     *
     * @return array<int, Carbon>
     */
    protected function buildViewingSlots(int $count): array
    {
        $slots = [];
        $today = Carbon::today();

        $slots[] = $today->copy()->setTime(10, 0);
        $slots[] = $today->copy()->setTime(15, 0);

        $extraTimes = [
            $today->copy()->subDay()->setTime(13, 41),
            $today->copy()->subDay()->setTime(18, 41),
            $today->copy()->subDay()->setTime(20, 41),
            $today->copy()->addDay()->setTime(11, 30),
            $today->copy()->addDay()->setTime(13, 41),
            $today->copy()->addDay()->setTime(16, 41),
        ];
        foreach ($extraTimes as $slot) {
            if (count($slots) >= $count) {
                break;
            }
            $slots[] = $slot;
        }

        return array_slice($slots, 0, $count);
    }

    protected function randomFirst(): string
    {
        return self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
    }

    protected function randomLast(): string
    {
        return self::LAST_NAMES[array_rand(self::LAST_NAMES)];
    }

    protected function randomSource(): string
    {
        $sources = ['Ieteikums', 'Internets', 'Sludinājums', 'Sociālie tīkli', 'Cits: zināmais', 'Cits: citur redzēts'];
        return $sources[array_rand($sources)];
    }
}
