<?php

declare(strict_types=1);

namespace App\Services\Wpform;

use App\Filament\Admin\Resources\WpformEntryResource;
use App\Models\User;
use App\Models\WpformEntry;
use App\Services\Mail\EmailSender;
use Illuminate\Support\Facades\Log;

/**
 * Sends the "new contact form submission" email through the shared
 * EmailSender template. Main-page forms go to administrators; a property
 * enquiry goes to the agent who owns that property.
 */
class WpformEntryNotifier
{
    public function send(WpformEntry $entry): void
    {
        $recipients = $this->recipients($entry);

        if ($recipients === []) {
            return;
        }

        $subject = $entry->isPropertyForm()
            ? 'Jauns pieteikums par īpašumu: '.($entry->property?->title ?? 'nav norādīts')
            : 'Jauns kontaktformas pieteikums: '.($entry->fieldValue('Jūsu vārds') ?? 'nav norādīts');

        $body = $this->body($entry);

        foreach ($recipients as $recipient) {
            try {
                app(EmailSender::class)->send(
                    to: $recipient['email'],
                    subject: $subject,
                    body: $body,
                    // System notification: branded footer, no agent signature.
                    signature: '',
                    context: [
                        'context' => 'wpform_entry',
                        'property_id' => $entry->property_id,
                        'recipient_role' => $recipient['role'],
                    ],
                );
            } catch (\Throwable $e) {
                Log::warning('Contact form notification email failed', [
                    'entry' => $entry->id,
                    'to' => $recipient['email'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @return array<int, array{email: string, role: string}>
     */
    private function recipients(WpformEntry $entry): array
    {
        if ($entry->isPropertyForm()) {
            $owner = $entry->property?->owner;

            if ($owner !== null && filled($owner->email)) {
                return [['email' => (string) $owner->email, 'role' => 'agent']];
            }
        }

        return User::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->map(fn (string $email): array => ['email' => $email, 'role' => 'admin'])
            ->values()
            ->all();
    }

    private function body(WpformEntry $entry): string
    {
        $rows = [];

        if ($entry->isPropertyForm() && $entry->property) {
            $rows[] = '<p><strong>Īpašums:</strong> '.e($entry->property->title).'</p>';
        }

        foreach ($entry->displayFields() as $field) {
            $name = (string) ($field['name'] ?? '');
            $value = $field['value'] ?? '';

            if ($name === 'Privātuma politika' || $name === '') {
                continue;
            }

            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }

            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            if ($name === 'Īpašuma saite' && preg_match('#^https?://#i', $value)) {
                $formatted = '<a href="'.e($value).'">'.e($value).'</a>';
            } else {
                $formatted = nl2br(e($value));
            }

            $rows[] = '<p><strong>'.e($name).':</strong> '.$formatted.'</p>';
        }

        $url = WpformEntryResource::getUrl('view', ['record' => $entry]);
        $rows[] = '<p><a href="'.e($url).'">Skatīt pieteikumu CRM</a></p>';

        return implode('', $rows);
    }
}
