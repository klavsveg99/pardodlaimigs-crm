<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use App\Filament\Admin\Resources\Pages\Concerns\SendsClientAttachments;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClient extends ViewRecord
{
    use SendsClientAttachments;

    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return array_values(array_filter([
            Actions\EditAction::make()->label('Rediģēt'),
            $this->nosutiitPielikumu()->label('Nosūtīt e-pastu')->icon('heroicon-m-paper-airplane')->color('gray'),
            $this->whatsappClientAction(),
        ]));
    }

    /**
     * Opens a WhatsApp chat with the client's phone number.
     */
    private function whatsappClientAction(): ?Actions\Action
    {
        $phone = preg_replace('/\D+/', '', (string) $this->getRecord()->phone);

        if ($phone === '') {
            return null;
        }

        // Local number (missing +371) → prefix the dial code.
        if (strlen($phone) === 8) {
            $phone = '371'.$phone;
        }

        return Actions\Action::make('whatsapp')
            ->label('WhatsApp')
            ->color('success')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->url('https://wa.me/'.$phone)
            ->openUrlInNewTab()
            ->visible(fn () => filled($this->getRecord()->phone));
    }
}
