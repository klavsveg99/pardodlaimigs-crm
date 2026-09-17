<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

use App\Models\Client;
use Illuminate\Support\Facades\Mail;

/**
 * "Nosūtīt" action for client attachments: opens a Filament email modal
 * (recipient / subject / RichEditor body / file selection) and sends the
 * selected files as SMTP attachments from the configured mailbox
 * (info@pardodlaimigs.lv).
 *
 * Attachments chosen are validated against a max combined size, both for
 * our own SMTP limits and typical recipient (Gmail-25MB) limits.
 */
trait SendsClientAttachments
{
    #[\Livewire\Attributes\Locked]
    public int $attachmentSendId = 0;

    // Livewire dispatch payloads are splatted as named arguments, so the
    // dispatched `id` lands directly on this parameter.
    #[\Livewire\Attributes\On('pdc-attachment-send')]
    public function openAttachmentSendModal($id = null): void
    {
        $this->attachmentSendId = (int) $id;
        $this->mountAction('nosutiit_pielikumu');
    }

    public function nosutiitPielikumu(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('nosutiit_pielikumu')
            ->label('Nosūtīt')
            ->icon('heroicon-m-paper-airplane')
            ->modalHeading('Nosūtīt failus ar e-pastu')
            ->modalDescription('No info@pardodlaimigs.lv · maksimālais kopējais pielikumu izmērs 18 MB.')
            ->mountUsing(function (\Filament\Schemas\Schema $schema): void {
                $record = $this->getRecord();

                $attachments = $record?->attachments()->orderBy('sort_order')->get()
                    ->mapWithKeys(fn ($a) => [(string) $a->id => $a->original_name])
                    ->all();

                // The clicked file is preselected; on records without
                // attachments the field stays empty.
                $preselect = $this->attachmentSendId > 0 && isset($attachments[(string) $this->attachmentSendId])
                    ? [(string) $this->attachmentSendId]
                    : [];

                $schema->fill([
                    'to' => $record?->email,
                    'subject' => $preselect ? ($attachments[(string) $this->attachmentSendId] ?? '') : '',
                    'files' => $preselect,
                    'body' => '<p>Sveiki,</p><p>pievienoju saistītos failus.</p><p>Ar cieņu,<br>Pārdod Laimīgs</p>',
                ]);
            })
            ->form(fn (): array => [
                \Filament\Forms\Components\TextInput::make('to')
                    ->label('Saņēmējs')
                    ->email()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('subject')
                    ->label('Temats')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('files')
                    ->label('Faili')
                    ->options($this->getRecord()?->attachments()->orderBy('sort_order')->get()
                        ->mapWithKeys(fn ($a) => [(string) $a->id => $a->original_name])->all() ?: [])
                    ->multiple()
                    ->searchable()
                    ->minItems(1)
                    ->required(),
                \Filament\Forms\Components\RichEditor::make('body')
                    ->label('Saturs')
                    ->toolbarButtons(['blockquote', 'bold', 'bulletList', 'italic', 'link', 'orderedList', 'redo', 'strike', 'underline', 'undo'])
                    ->extraInputAttributes(['style' => 'min-height: 200px'])
                    ->columnSpanFull(),
            ])
            ->modalSubmitActionLabel('Nosūtīt')
            ->action(function (array $data, array $arguments) {
                /** @var Client $record */
                $record = $this->getRecord();

                $selected = $record->attachments()
                    ->whereIn('id', $data['files'] ?? [])
                    ->orderBy('sort_order')
                    ->get();

                if ($selected->isEmpty()) {
                    \Filament\Notifications\Notification::make()->title('Faili netika atrasti')->danger()->send();

                    return;
                }

                // Total attachment size guard: shared SMTP + recipient limits
                // (base64 inflates payloads by ~37% over the raw size).
                $totalSize = $selected->sum('size');
                if ($totalSize > 18 * 1024 * 1024) {
                    \Filament\Notifications\Notification::make()
                        ->title('Pielikumi pārsniedz 18 MB')
                        ->body('Izvēlētie faili ir '.sprintf('%.1f', $totalSize / (1024 * 1024)).' MB. Sūtiet mazāk failu vienā e-pastā.')
                        ->danger()
                        ->send();

                    return;
                }

                $html = self::renderEmailHtml($data['body']);
                $to = trim($data['to']);
                $subject = trim($data['subject']);

                try {
                    $disk = \Illuminate\Support\Facades\Storage::disk('public');

                    Mail::html($html, function ($message) use ($to, $subject, $selected, $disk) {
                        $message->subject($subject)->to($to);

                        foreach ($selected as $file) {
                            $message->attach($disk->path($file->path), [
                                'as' => $file->original_name,
                                'mime' => $file->mime_type,
                            ]);
                        }
                    });
                } catch (\Throwable $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Nosūtīšana neizdevās')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                    logger()->error('Attachment email failed', ['msg' => $e->getMessage(), 'client' => $record->id]);

                    return;
                }

                app(\App\Services\AuditLogger::class)->activity('attachment_email_sent', [
                    'client_id' => $record->id,
                    'to' => $to,
                    'subject' => $subject,
                    'files' => $selected->pluck('id')->all(),
                ]);

                \Filament\Notifications\Notification::make()
                    ->title('E-pasts nosūtīts uz '.$to)
                    ->success()
                    ->send();
            });
    }

    /**
     * RichEditor HTML → email-safe HTML: RichEditor content is HTML already,
     * so we only strip dangerous elements and wrap it in a minimal template.
     */
    public static function renderEmailHtml(string $body): string
    {
        $body = trim((string) $body);
        if ($body === '') {
            $body = '<p></p>';
        }

        // Strip scripts/styles/iframes — RichEditor never produces them but
        // content is user-authored HTML, keep the email safe.
        $body = preg_replace('/<\s*(script|style|iframe|object|embed)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $body) ?? $body;
        $body = preg_replace('/<\s*(script|style|iframe|object|embed)[^>]*\/?>/i', '', $body) ?? $body;

        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head>'
            .'<body style="margin:0;padding:24px;background:#f5f7f6;">'
            .'<div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;'
            .'border:1px solid #e2e8e6;padding:24px;font-family:Arial,Helvetica,sans-serif;'
            .'font-size:14px;line-height:1.6;color:#1f2937;">'
            .$body
            .'<hr style="border:none;border-top:1px solid #e2e8e6;margin:20px 0 12px;">'
            .'<p style="font-size:12px;color:#6b7280;margin:0;">Pārdod Laimīgs · <a href="https://pardodlaimigs.lv" style="color:#285854;">pardodlaimigs.lv</a></p>'
            .'</div></body></html>';
    }
}
