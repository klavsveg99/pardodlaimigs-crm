<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use App\Models\Viewing;
use App\Models\WpformEntry;
use Illuminate\Console\Command;

class CleanupTestData extends Command
{
    protected $signature = 'pdc:cleanup-test-data
        {--dry-run : Show what would be deleted without actually deleting}
        {--keep-attachments : Keep attachment files in storage}';

    protected $description = 'Remove test data marked with [TEST] prefix or test.* paths';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $keepAttachments = (bool) $this->option('keep-attachments');

        $this->info('Cleaning up TEST data...');

        $stats = [
            'WpformEntry' => 0,
            'Client' => 0,
            'CrmProperty' => 0,
            'Deal' => 0,
            'Task' => 0,
            'Viewing' => 0,
            'User (test.agent)' => 0,
            'Attachment (test/*)' => 0,
        ];

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be modified');
        }

        if (! $isDryRun) {
            // Delete in FK order

            // WpformEntry with [TEST] in form_name OR test- prefix in external_id
            $wpformCount = WpformEntry::where('form_name', 'like', '[TEST]%')->orWhere('external_id', 'like', 'test-%')->count();
            $stats['WpformEntry'] = $wpformCount;
            if (! $isDryRun) {
                WpformEntry::where('form_name', 'like', '[TEST]%')->orWhere('external_id', 'like', 'test-%')->delete();
                $this->info("Deleted {$wpformCount} WpformEntry records");
            }

            // Viewing with [TEST] notes
            $viewingCount = Viewing::where('notes_md', 'like', '[TEST]%')->orWhere('notes_md', 'like', '%[TEST]%')->count();
            $stats['Viewing'] = $viewingCount;
            if (! $isDryRun) {
                Viewing::where('notes_md', 'like', '[TEST]%')->orWhere('notes_md', 'like', '%[TEST]%')->delete();
                $this->info("Deleted {$viewingCount} Viewing records");
            }

            // Task with [TEST] in title
            $taskCount = Task::where('title', 'like', '[TEST]%')->count();
            $stats['Task'] = $taskCount;
            if (! $isDryRun) {
                Task::where('title', 'like', '[TEST]%')->delete();
                $this->info("Deleted {$taskCount} Task records");
            }

            // Deal with [TEST] in title OR attached to a [TEST] client
            $dealCount = Deal::where('title', 'like', '[TEST]%')
                ->orWhereIn('client_id', Client::withTrashed()->where('name', 'like', '[TEST]%')->pluck('id'))
                ->count();
            $stats['Deal'] = $dealCount;
            if (! $isDryRun) {
                $dealIds = Deal::where('title', 'like', '[TEST]%')
                    ->orWhereIn('client_id', Client::withTrashed()->where('name', 'like', '[TEST]%')->pluck('id'))
                    ->pluck('id');
                Deal::whereIn('id', $dealIds)->delete();
                $this->info("Deleted {$dealCount} Deal records");
            }

            // CrmProperty with [TEST] title (delete attachments first)
            $propsWithAttachments = CrmProperty::where('title', 'like', '[TEST]%')->with('attachments')->get();
            $propertyCount = $propsWithAttachments->count();
            $stats['CrmProperty'] = $propertyCount;
            if (! $isDryRun) {
                foreach ($propsWithAttachments as $prop) {
                    foreach ($prop->attachments as $attachment) {
                        if (! $keepAttachments) {
                            \Storage::disk($attachment->disk)->delete($attachment->path);
                        }
                        $attachment->delete();
                    }
                    $prop->delete();
                }
                $this->info("Deleted {$propertyCount} CrmProperty records");
            }

            // Clients with [TEST] (delete attachments first)
            $clientsWithAttachments = Client::where('name', 'like', '[TEST]%')->with('attachments')->get();
            $clientCount = $clientsWithAttachments->count();
            $stats['Client'] = $clientCount;
            if (! $isDryRun) {
                foreach ($clientsWithAttachments as $client) {
                    foreach ($client->attachments as $attachment) {
                        if (! $keepAttachments) {
                            \Storage::disk($attachment->disk)->delete($attachment->path);
                        }
                        $attachment->delete();
                    }
                    $client->attachments()->delete();
                    $client->forceDelete();
                }
                $this->info("Deleted {$clientCount} Client records");
            }

            // Users with test.agent email pattern
            $userCount = User::where('email', 'like', 'test.agent%')->count();
            $stats['User (test.agent)'] = $userCount;
            if (! $isDryRun) {
                User::where('email', 'like', 'test.agent%')->delete();
                $this->info("Deleted {$userCount} test.agent users");
            }

            // Cleanup test attachment files
            if (! $keepAttachments) {
                $attachmentCount = Attachment::where('path', 'like', 'attachments/test/%')->count();
                $stats['Attachment (test/*)'] = $attachmentCount;
                if ($attachmentCount > 0) {
                    Attachment::where('path', 'like', 'attachments/test/%')->delete();
                    $this->info("Deleted {$attachmentCount} test attachments");
                }
            }
        } else {
            // Dry run - show stats
            $stats['WpformEntry'] = WpformEntry::where('form_name', 'like', '[TEST]%')->orWhere('external_id', 'like', 'test-%')->count();
            $stats['Viewing'] = Viewing::where('notes_md', 'like', '[TEST]%')->orWhere('notes_md', 'like', '%[TEST]%')->count();
            $stats['Task'] = Task::where('title', 'like', '[TEST]%')->count();
            $stats['Deal'] = Deal::where('title', 'like', '[TEST]%')
                ->orWhereIn('client_id', Client::withTrashed()->where('name', 'like', '[TEST]%')->pluck('id'))
                ->count();
            $stats['CrmProperty'] = CrmProperty::where('title', 'like', '[TEST]%')->count();
            $stats['Client'] = Client::withTrashed()->where('name', 'like', '[TEST]%')->count();
            $stats['User (test.agent)'] = User::where('email', 'like', 'test.agent%')->count();
            $stats['Attachment (test/*)'] = Attachment::where('path', 'like', 'attachments/test/%')->count();
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
