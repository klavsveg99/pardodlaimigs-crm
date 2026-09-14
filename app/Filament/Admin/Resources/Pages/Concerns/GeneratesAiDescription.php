<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

use App\Services\Ai\DescriptionGenerator;
use Filament\Notifications\Notification;

/**
 * AI aprakstu ģenerators (CRM 2.1).
 *
 * Ievades modalā = Filament standarta Action modal ar formu (sk.
 * CrmPropertyResource "AI ģenerēt aprakstu" sekcijas darbību). Pēc veiksmīgas
 * ģenerēšanas rezultāti tiek attēloti rezultātu popup (ai-generator-panel).
 */
trait GeneratesAiDescription
{
    /** @var array<string, string> ģenerētie teksti: title, description, description_ss, facebook, instagram */
    public array $aiResult = [];

    public bool $aiGenerating = false;

    /** @return array<string, mixed> konteksts no pašreizējās formas datiem */
    private function aiContext(): array
    {
        $data = $this->data ?? [];

        return [
            'title' => $data['title'] ?? null,
            'category' => $data['category'] ?? null,
            'status' => $data['status'] ?? null,
            'price_eur' => $data['price_eur'] ?? null,
            'beds' => $data['beds'] ?? null,
            'baths' => $data['baths'] ?? null,
            'size_m2' => $data['size_m2'] ?? null,
            'land_m2' => $data['land_m2'] ?? null,
            'kadastra_nr' => $data['kadastra_nr'] ?? null,
            'city' => $data['city'] ?? null,
            'address' => $data['address'] ?? null,
        ];
    }

    /** Saglabā ģenerētos tekstus ierakstā, lai popup aizvēršana tos nezaudētu. */
    protected function persistAiResult(): void
    {
        if ($this->record === null || $this->aiResult === []) {
            return;
        }

        try {
            $this->record->update(['ai_result' => $this->aiResult]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Atver rezultātu popup ar iepriekš saglabātajiem tekstiem (bez ģenerēšanas). */
    public function showSavedAiResult(): void
    {
        if (! is_array($this->record?->ai_result) || $this->record->ai_result === []) {
            return;
        }

        $this->aiResult = $this->record->ai_result;
        $this->dispatch('ai-results-updated');
    }

    /**
     * Popup aizvēršana: saglabā popup rediģētos tekstus ierakstā.
     */
    public function saveAiResult(array $texts): void
    {
        $this->aiResult = array_merge($this->aiResult, $texts);
        $this->persistAiResult();
    }

    /**
     * Izsauc "AI ģenerēt aprakstu" sekcijas darbība no formas (modala forma).
     * Trait kopā ar lapu komponenti — $this ir pati lapa, un piezīmes raksta
     * TIEŠI $this->data (Set/$get darbības kontekstā norāda uz modāļa shēmu,
     * nevis formas stāvokli).
     */
    public function runAiGenerator(array $notes): void
    {
        $aiNotes = array_filter($notes, fn ($v): bool => filled($v));
        $this->data = array_merge($this->data ?? [], ['ai_notes' => $aiNotes === [] ? null : $aiNotes]);

        if (DescriptionGenerator::provider() === null) {
            Notification::make()
                ->title('AI nav konfigurēts')
                ->body('Serverī nav iestatīta GEMINI_API_KEY vai OPENAI_API_KEY atslēga.')
                ->warning()
                ->send();

            return;
        }

        $this->aiGenerating = true;

        try {
            $this->aiResult = app(DescriptionGenerator::class)->generate($this->aiContext(), is_array($aiNotes) ? $aiNotes : []);
            $this->persistAiResult();
            $this->dispatch('ai-results-updated');
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('Aprakstu ģenerēšana neizdevās')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->aiGenerating = false;
        }
    }

    /**
     * Variantu pogas rezultātu popup ("Ģenerēt vēlreiz", "Saīsināt",
     * "Padarīt pārliecinošāku") — lasa pašreizējos form izteiksmes datus.
     */
    public function generateAi(string $mode = DescriptionGenerator::MODE_FULL, ?string $currentDescription = null): void
    {
        if (DescriptionGenerator::provider() === null) {
            Notification::make()
                ->title('AI nav konfigurēts')
                ->body('Serverī nav iestatīta GEMINI_API_KEY vai OPENAI_API_KEY atslēga.')
                ->warning()
                ->send();

            return;
        }

        $this->aiGenerating = true;

        try {
            $notes = is_array($this->data['ai_notes'] ?? null) ? $this->data['ai_notes'] : [];

            $this->aiResult = app(DescriptionGenerator::class)
                ->generate($this->aiContext(), $notes, $mode, $currentDescription);
            $this->persistAiResult();
            $this->dispatch('ai-results-updated');
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('Aprakstu ģenerēšana neizdevās')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->aiGenerating = false;
        }
    }
}
