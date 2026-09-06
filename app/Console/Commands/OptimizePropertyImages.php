<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Services\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizePropertyImages extends Command
{
    protected $signature = 'pdc:optimize-images
        {--property= : Only optimize attachments of this attachable_id}
        {--dry-run : Report what would change without writing anything}';

    protected $description = 'Downscale + optimise all local image attachments (max 1920px) and generate thumbnails.';

    public function handle(ImageOptimizer $optimizer): int
    {
        $query = Attachment::query()
            ->where('disk', 'public')
            ->where('mime_type', 'LIKE', 'image/%')
            ->where('path', 'NOT LIKE', 'http://%')
            ->where('path', 'NOT LIKE', 'https://%')
            ->where('path', 'NOT LIKE', 'thumb-%')
            ->orderBy('id');

        if ($property = $this->option('property')) {
            $query->where('attachable_id', (int) $property);
        }

        $total = (int) (clone $query)->count();
        if ($total === 0) {
            $this->info('No image attachments to process.');

            return self::SUCCESS;
        }

        $this->info("Processing {$total} image attachment(s)…");
        $dryRun = (bool) $this->option('dry-run');

        $checked = 0;
        $changed = 0;
        $unchanged = 0;
        $skipped = 0;
        $missing = 0;

        $query->chunk(100, function ($attachments) use ($optimizer, $dryRun, &$checked, &$changed, &$unchanged, &$skipped, &$missing) {
            foreach ($attachments as $attachment) {
                $checked++;

                $path = Storage::disk($attachment->disk)->path($attachment->path);
                if (! is_file($path)) {
                    $missing++;
                    $this->line("  [SKIP] #{$attachment->id} missing file: {$attachment->path}");

                    continue;
                }

                $result = $optimizer->optimize($path, dryRun: $dryRun);

                if (! $result['optimized']) {
                    $skipped++;
                    $this->line("  [SKIP] #{$attachment->id} {$attachment->path}");

                    continue;
                }

                if ($result['changed']) {
                    $changed++;

                    $w = trim($attachment->path, '/');
                    if ($dryRun) {
                        $this->line("  [WOULD] #{$attachment->id} {$w} → {$result['width']}×{$result['height']} ({$this->humanBytes($result['size'])})");
                    } else {
                        $attachment->update([
                            'size' => $result['size'],
                            'mime_type' => $this->refreshMime($path, (string) $attachment->mime_type),
                        ]);
                        $this->line("  [OK]   #{$attachment->id} {$w} → {$result['width']}×{$result['height']} thumb=".($result['thumb'] ? 'yes' : 'no'));
                    }
                } else {
                    $unchanged++;
                    if ($result['size'] !== (int) $attachment->size) {
                        $attachment->update(['size' => $result['size']]);
                    }
                }
            }
        });

        $this->newLine();
        $this->info(sprintf(
            'Done. checked=%d changed=%d unchanged=%d skipped=%d missing=%d%s',
            $checked,
            $changed,
            $unchanged,
            $skipped,
            $missing,
            $dryRun ? ' (dry-run)' : '',
        ));

        return self::SUCCESS;
    }

    private function refreshMime(string $path, string $current): string
    {
        $mime = @mime_content_type($path) ?: $current;

        return is_string($mime) ? $mime : $current;
    }

    private function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        $value = $bytes;
        $unitIdx = 0;
        while ($value >= 1024 && $unitIdx < count($units) - 1) {
            $value /= 1024;
            $unitIdx++;
        }

        return sprintf($unitIdx === 0 ? '%d%s' : '%.1f%s', $value, $units[$unitIdx]);
    }
}
