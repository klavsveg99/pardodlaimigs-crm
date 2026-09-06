<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use App\Models\Viewing;
use Illuminate\Console\Command;

class CleanupTestData extends Command
{
    protected $signature = 'pdc:cleanup-test-data
        {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Remove [TEST] tagged clients, agents, tasks and viewings.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $stats = [
            'Viewing' => 0,
            'Task' => 0,
            'Client' => 0,
            'User' => 0,
        ];

        if ($isDryRun) {
            $this->warn('DRY RUN — no data will be modified.');
        }

        $stats['Viewing'] = Viewing::whereHas('client', fn ($q) => $q->where('name', 'like', '[TEST]%'))->count();
        $stats['Task'] = Task::where('title', 'like', '[TEST]%')->count();
        $stats['Client'] = Client::withTrashed()->where('name', 'like', '[TEST]%')->count();
        $stats['User'] = User::where('email', 'like', 'test.agent%')->count();

        if (! $isDryRun) {
            Viewing::whereHas('client', fn ($q) => $q->where('name', 'like', '[TEST]%'))->delete();
            Task::where('title', 'like', '[TEST]%')->delete();
            Client::withTrashed()->where('name', 'like', '[TEST]%')->forceDelete();
            User::where('email', 'like', 'test.agent%')->delete();
        }

        $this->newLine();
        $this->info('Summary:');
        foreach ($stats as $type => $count) {
            $this->line("  {$type}: {$count}");
        }

        if ($isDryRun) {
            $this->warn('Run without --dry-run to actually delete the test data.');
        } else {
            $this->info('Test data cleanup completed.');
        }

        return self::SUCCESS;
    }
}
