<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use FilesystemIterator;
use ZipArchive;

/**
 * Daily file backup of EVERYTHING users upload to the CRM's public storage
 * disk — property images/plans, plus attachments on clients, tasks,
 * viewings, avatars etc. The whole disk (`storage/app/public`) is archived,
 * so every future attachment subdirectory is covered automatically.
 *
 * Produces `<dir>/files-YYYY-MM-DD.zip` and keeps only the newest N copies
 * (same retention as the database backup). Photos dominate the payload and
 * are already optimized server-side — the zip is stored with default
 * compression, which is enough without slowing uploads of the archive.
 */
class BackupCrmFiles extends Command
{
    protected $signature = 'pdc:backup-files
        {--keep= : Number of daily file backups to keep (default: config backup.keep)}';

    protected $description = 'Backup all uploaded storage files (whole public disk: attachments, avatars, …) into a dated zip, keeping the newest N backups';

    public function handle(): int
    {
        $diskPath = (string) Storage::disk('public')->path('');

        if (! is_dir($diskPath)) {
            $this->error("Storage root nav atrasts: {$diskPath}");

            return self::FAILURE;
        }

        $count = iterator_count(new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($diskPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        ));

        if ($count === 0) {
            $this->info('Nav datņu, ko rezervēt — izlaista.');

            return self::SUCCESS;
        }

        $dir = rtrim((string) config('backup.dir'), '/');
        $keep = max(1, (int) ($this->option('keep') ?: (int) config('backup.keep', 5)));
        $dest = $dir.'/files-'.now()->format('Y-m-d').'.zip';
        $tmp = $dir.'/.files-backup.tmp.zip';

        if (! is_dir($dir) && ! mkdir($dir, 0700, true)) {
            $this->error("Neizdevās izveidot mapi: {$dir}");

            return self::FAILURE;
        }

        $zip = new ZipArchive;

        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('Neizdevās izveidot zip arhīvu.');

            return self::FAILURE;
        }

        $added = 0;

        try {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($diskPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY,
            );

            foreach ($it as $file) {
                /** @var \SplFileInfo $file */
                if (! $file->isFile()) {
                    continue;
                }

                // Relative path inside the zip = relative to the disk root.
                $relative = ltrim(str_replace($diskPath, '', str_replace('\\', '/', $file->getPathname())), '/');

                if (! $zip->addFile($file->getPathname(), $relative)) {
                    // addFile failures would silently lose files — fail loudly.
                    throw new RuntimeException('Neizdevās pievienot '.$file->getPathname());
                }

                $added++;
            }

            $zip->close();

            if (! rename($tmp, $dest)) {
                throw new RuntimeException('Neizdevās pārsaukt arhīvu uz '.$dest);
            }
        } catch (\Throwable $e) {
            $zip->close();
            is_file($tmp) && @unlink($tmp);

            $this->error('Files backup neizdevās: '.$e->getMessage());
            report($e);

            return self::FAILURE;
        }

        $this->rotate($dir, $keep);

        $this->info('Files backup saglabāts: '.$dest.' ('.number_format((float) filesize($dest) / 1024 / 1024, 1, ',', ' ').' MB, '.$added.' datnes)');

        return self::SUCCESS;
    }

    /**
     * Keep only the newest `$keep` daily file backups (date-named
     * files-YYYY-MM-DD.zip). Manual/emergency copies never match the
     * pattern and are never removed.
     *
     * @param  string  $dir
     * @param  int  $keep
     */
    private function rotate(string $dir, int $keep): void
    {
        $backups = glob($dir.'/files-[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9].zip') ?: [];

        usort(
            $backups,
            fn (string $a, string $b): int => filemtime($b) <=> filemtime($a),
        );

        foreach (array_slice($backups, $keep) as $obsolete) {
            unlink($obsolete);
            $this->line('Izdzēsts vecais files backup: '.$obsolete);
        }
    }
}
