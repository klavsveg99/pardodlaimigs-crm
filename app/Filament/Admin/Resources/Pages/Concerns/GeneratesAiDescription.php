<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

use App\Services\Ai\DescriptionGenerator;
use Filament\Notifications\Notification;

/**
 * AI aprakstu ģenerators (CRM 2.1): aizpilda ģenerētos tekstus $aiResult,
 * ko attēlo ai-generator-panel popup. Ģenerēšana notiek uz formas Livewire
 * komponentes (Edit/Create īpašuma lapa), izmantojot pašreizējos form datos.
 */
trait GeneratesAiDescription
{
    /** @var array<string, string> ģenerētie teksti: title, description, description_ss, facebook, instagram */
    public array $aiResult = [];

    public bool $aiGenerating = false;

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
            $data = $this->data ?? [];

            $context = [
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

            $notes = is_array($data['ai_notes'] ?? null) ? $data['ai_notes'] : [];

            $this->aiResult = app(DescriptionGenerator::class)
                ->generate($context, $notes, $mode, $currentDescription);

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

    public function getAiProviderLabel(): string
    {
        return match (DescriptionGenerator::provider()) {
            'gemini' => 'Google Gemini',
            'openai' => 'OpenAI',
            default => '—',
        };
    }
}
