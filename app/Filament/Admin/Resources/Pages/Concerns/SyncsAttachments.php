<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

use App\Services\ImageOptimizer;
use App\Services\PdfOptimizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

trait SyncsAttachments
{
    /** @var array<int, array{0: string, 1: string}> */
    protected array $attachmentsToSync = [];

    protected bool $attachmentsFieldPresent = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->captureAttachments($data);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->captureAttachments($data);

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $attachments = $this->getRecord()->attachments()->get();

        $data['attachments'] = $attachments->pluck('path')->all();
        $data['attachment_original_names'] = $attachments->pluck('original_name', 'path')->all();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncAttachments($this->record);
    }

    protected function afterSave(): void
    {
        $this->syncAttachments($this->record);
    }

    protected function captureAttachments(array &$data): void
    {
        // Only sync when the attachments field was actually part of the
        // submitted data. A missing key (e.g. a save that never rendered the
        // field) must never be treated as "delete everything".
        $this->attachmentsFieldPresent = array_key_exists('attachments', $data);

        $names = $data['attachment_original_names'] ?? [];

        $this->attachmentsToSync = array_map(
            fn (string $path): array => [$path, $names[$path] ?? basename($path)],
            array_values(array_filter(
                $data['attachments'] ?? [],
                fn ($path): bool => is_string($path) && $path !== ''
            ))
        );

        unset($data['attachments'], $data['attachment_original_names']);
    }

    private function syncAttachments(Model $record): void
    {
        if (! $this->attachmentsFieldPresent) {
            return;
        }

        $paths = array_column($this->attachmentsToSync, 0);

        $existing = $record->attachments()->get()->keyBy('path');

        foreach ($existing as $attachment) {
            if (! in_array($attachment->path, $paths, true)) {
                Storage::disk($attachment->disk)->delete($attachment->path);
                $attachment->delete();
            }
        }

        $disk = Storage::disk('public');

        foreach ($this->attachmentsToSync as $i => [$path, $originalName]) {
            $attachment = $existing[$path] ?? null;

            if ($attachment) {
                if ($attachment->sort_order !== $i) {
                    $attachment->update(['sort_order' => $i]);
                }

                continue;
            }

            $attachment = $record->attachments()->create([
                'path' => $path,
                'disk' => 'public',
                'original_name' => $originalName,
                'mime_type' => $disk->mimeType($path),
                'size' => $disk->size($path),
                'sort_order' => $i,
            ]);

            // Newly attached local files are optimised immediately.
            // Images: max 1920px + re-encode + thumbnail (property uploads
            // are already optimised by the upload endpoint — skip when a
            // thumb file already exists to avoid double re-encoding).
            // PDFs: Ghostscript re-compress, original kept on any failure.
            // Any other document (docx etc.) is never touched.
            $mime = (string) $disk->mimeType($path);

            if (str_starts_with($mime, 'image/') || $mime === 'application/pdf') {
                $absolute = $disk->path($path);
                $notThumb = dirname($absolute).DIRECTORY_SEPARATOR.'thumb-'.basename($absolute);

                try {
                    if (str_starts_with($mime, 'image/')) {
                        if (! is_file($notThumb)) {
                            $result = app(ImageOptimizer::class)->optimize($absolute);
                            $attachment->update(['size' => $result['size']]);
                        }
                    } else {
                        $pdfSize = app(PdfOptimizer::class)->optimize($absolute);
                        if ($pdfSize !== null) {
                            $attachment->update(['size' => $pdfSize]);
                        }
                    }
                } catch (Throwable) {
                    // Optimisation must never block saving the record.
                }
            }
        }
    }
}
