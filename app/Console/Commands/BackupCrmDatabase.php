<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Full-data daily backup.
 *
 * CRM runs on SQLite, so the entire dataset (clients, properties, WPForms
 * entries, viewings, tasks, audit log…) lives in one file — a consistent
 * copy is produced via `VACUUM INTO` (clean snapshot without locking the
 * live database) and only the newest N backups are kept.
 */
class BackupCrmDatabase extends Command
{
    protected $signature = 'pdc:backup-database
        {--keep= : Number of daily backups to keep (default: config backup.keep)}';

    protected $description = 'Backup the whole CRM database (consistent SQLite copy), keeping the newest N backups';

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            // MySQL backup path (mysqldump) is intentionally not available:
            // prod data lives in SQLite. Fail loudly instead of silently
            // producing an incomplete backup.
            $this->error('Tikai SQLite savienojums tiek atbalstīts — produkcijas dati dzīvo SQLite failā.');

            return self::FAILURE;
        }

        $source = (string) DB::connection()->getConfig('database');
        if (! is_file($source)) {
            $this->error("Datubāzes fails nav atrasts: {$source}");

            return self::FAILURE;
        }

        $dir = rtrim((string) config('backup.dir'), '/');
        $keep = max(1, (int) ($this->option('keep') ?: (int) config('backup.keep', 5)));

        if (! is_dir($dir) && ! mkdir($dir, 0700, true)) {
            $this->error("Neizdevās izveidot mapi: {$dir}");

            return self::FAILURE;
        }

        $dest = $dir.'/crm-db-'.now()->format('Y-m-d').'.sqlite';
        $tmp = $dir.'/.crm-db-backup.tmp';

        try {
            $pdo = DB::connection()->getPdo();
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA busy_timeout = 60000');

            if (is_file($tmp)) {
                unlink($tmp);
            }

            $pdo->exec('VACUUM INTO '.$pdo->quote($tmp));

            if (! rename($tmp, $dest)) {
                throw new RuntimeException('Neizdevās pārsaukt pagaidu kopiju uz '.$dest);
            }
        } catch (\Throwable $e) {
            if (is_file($tmp)) {
                @unlink($tmp);
            }

            $this->error('Backup neizdevās: '.$e->getMessage());

            report($e);

            return self::FAILURE;
        }

        $this->rotate($dir, $keep);

        $this->info('Backup saglabāts: '.$dest.' ('.number_format((float) filesize($dest) / 1024, 0, ',', ' ').' KB)');

        return self::SUCCESS;
    }

    /**
     * Keep only the newest `$keep` daily backup files (date-named
     * crm-db-YYYY-MM-DD.sqlite). Manual/emergency copies never match the
     * pattern and are never removed.
     *
     * @param  string  $dir
     * @param  int  $keep
     */
    private function rotate(string $dir, int $keep): void
    {
        $backups = glob($dir.'/crm-db-[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9].sqlite') ?: [];

        usort(
            $backups,
            fn (string $a, string $b): int => filemtime($b) <=> filemtime($a),
        );

        foreach (array_slice($backups, $keep) as $obsolete) {
            unlink($obsolete);
            $this->line('Izdzēsts vecais backup: '.$obsolete);
        }
    }
}
