<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Log;

trait AutosavesForm
{
    public ?string $autosaveState = null;

    public ?string $autosaveAt = null;

    protected function getAutosaveDebounceMs(): int
    {
        return 1500;
    }

    public function booted(): void
    {
        $debounce = (int) $this->getAutosaveDebounceMs();
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_BEFORE,
            fn (): string => view('filament.admin.partials.autosave-script', ['debounce' => $debounce])->render(),
        );
    }

    public function autosave(): void
    {
        if (! $this->getRecord()?->getKey()) {
            return;
        }

        try {
            $this->save();
            $this->autosaveState = 'saved';
            $this->autosaveAt = now()->format('H:i:s');
        } catch (\Throwable $e) {
            $this->autosaveState = 'error';
            Log::warning('Autosave failed', ['msg' => $e->getMessage()]);
        }
    }
}
