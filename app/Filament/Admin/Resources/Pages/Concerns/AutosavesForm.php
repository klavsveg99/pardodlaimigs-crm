<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

trait AutosavesForm
{
    #[Locked]
    public bool $isAutosaveRun = false;

    public ?string $autosaveState = null;

    public ?string $autosaveAt = null;

    public ?string $autosaveError = null;

    protected function getAutosaveDebounceMs(): int
    {
        return 1500;
    }

    /**
     * Override to restrict when autosave is allowed to run (e.g. only for
     * draft records). Autosaving an already published record means every
     * keystroke silently persists — such records must be saved explicitly.
     */
    protected function canAutosave(): bool
    {
        return true;
    }

    public function booted(): void
    {
        $debounce = (int) $this->getAutosaveDebounceMs();
        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_BEFORE,
            fn (): string => view('filament.admin.partials.autosave-script', ['debounce' => $debounce])->render(),
        );
    }

    /**
     * Invoked via the global `pdc-autosave-tick` Livewire event (dispatched by
     * the autosave script). Event-based delivery is deliberate: components
     * without this listener (topbar, sidebar, tables, relation managers)
     * ignore the event, whereas a direct `call('autosave')` on a mis-targeted
     * component throws MethodNotFoundException → HTTP 500.
     */
    #[On('pdc-autosave-tick')]
    public function autosave(): void
    {
        if (! $this->canAutosave()) {
            return;
        }

        if (! $this->getRecord()?->getKey()) {
            return;
        }

        $this->isAutosaveRun = true;

        try {
            $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
            $this->autosaveState = 'saved';
            $this->autosaveAt = now()->format('H:i:s');
            $this->autosaveError = null;
        } catch (ValidationException $e) {
            // Draft autosave must never blow up the page (e.g. switching
            // "Pieteikuma avots" to Ārējais before "Atbildīgais aģents" is filled).
            // Record the first validation message so the indicator can show it.
            $this->autosaveState = 'error';
            $messages = collect($e->errors())->flatten()->filter()->values();
            $this->autosaveError = $messages->first() ?: 'Pagaidu saglabāšana neizdevās — pabeidziet obligātos laukus.';
            Log::info('Autosave skipped (validation)', ['msg' => $this->autosaveError]);
        } catch (\Throwable $e) {
            $this->autosaveState = 'error';
            $this->autosaveError = 'Pagaidu saglabāšana neizdevās.';
            Log::warning('Autosave failed', ['msg' => $e->getMessage()]);
        } finally {
            $this->isAutosaveRun = false;
        }
    }
}
