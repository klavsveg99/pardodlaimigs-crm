<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * Daily file backup of everything users upload to the CRM's public storage
 * disk (property photos/plans under `attachments/`, agent `avatars/`).
 *
 * Produces `<dir>/files-YYYY-MM-DD.zip` and keeps only the newest N copies
 * (same retention as the database backup). Files are NOT compressed hard —
 * photos dominate and are already optimized — the zip is stored deflated,
 * which keeps rebuilds simple on shared hosting.
 */
class BackupCrmFiles extends Command
{
    protected $signature = 'pdc:backup-files
        {--keep= : Number of daily file backups to keep (default: config backup.keep)}';

    protected $description = 'Backup the uploaded storage files (attachments + avatars) into a dated zip, keeping the newest N backups';

    public function handle(): int
    {
        $diskPath = (string) Storage::disk('public')->path('');

        if (! is_dir($diskPath)) {
            $this->error("Storage root nav atrasts: {$diskPath}");

            return self::FAILURE;
        }

        $includeDirs = ['attachments', 'avatars'];

        $collectCount = function () use ($diskPath): int {
            $count = 0;
            foreach (['attachments', 'avatars'] as $subdir) {
                if (! is_dir($diskPath.$subdir)) {
                    continue;
                }

                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($diskPath.$subdir, FilesystemIterator::SKIP_DOTS),
                );
                $count += iterator_count($it);
            }

            return $count;
        };

        if ($collectCount() === 0) {
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

        try {
            foreach (['attachments', 'avatars'] as $subdir) {
                if (! is_dir($diskPath.$subdir)) {
                    continue;
                }

                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($diskPath.$subdir, FilesystemIterator::SKIP_DOTS),
                );

                foreach ($it as $file) {
                    /* @var SplFileInfo $file */
                    if (! $file->isFile()) {
                        continue;
                    }

                    if (! $zip->addFile($file->getPathname(), $subdir.'/'.$it->getSubPathName())) {
                        // The uploaded originals are on the same disk; addFile
                        // copies them while the file handle is open — failures
                        // here would silently lose data, so fail loudly.
                        throw new RuntimeException('Neizdevās pievienot '.$file->getPathname());
                    }

                    $fileCount++;
                }
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

        $this->info('Files backup saglabāts: '.$dest.' ('.number_format((float) filesize($dest) / 1024 / 1024, 1, ',', ' ').' MB, '.$fileCount.' datnes)');

        return self::SUCCESS;
    }

    /**
     * Keep only the newest `$keep` daily file backups (date-named
     * files-YYYY-MM-DD.zip). Manual / emergency copies never match the
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
