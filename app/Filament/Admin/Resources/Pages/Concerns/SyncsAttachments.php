<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

trait SyncsAttachments
{
    /** @var array<int, array{0: string, 1: string}> */
    protected array $attachmentsToSync = [];

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

    private function captureAttachments(array &$data): void
    {
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

            $record->attachments()->create([
                'path'          => $path,
                'disk'          => 'public',
                'original_name' => $originalName,
                'mime_type'     => $disk->mimeType($path),
                'size'          => $disk->size($path),
                'sort_order'    => $i,
            ]);
        }
    }
}
