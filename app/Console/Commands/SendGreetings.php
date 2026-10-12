<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\EmailLog;
use App\Services\Mail\EmailSender;
use App\Support\LatvianCalendar;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Automātiski nosūta apsveikumu e-pastus klientiem, kuriem ieslēgti
 * apsveikumi (clients.greetings_enabled):
 *  - dzimšanas dienā (clients.birth_date);
 *  - vārdadienā (latviešu kalendārvārdi, config/name_days.php);
 *  - valsts svētkos (config('crm.greetings.holidays'), arī kustīgajos).
 *
 * Klientam jābūt ar e-pastu un ieslēgtu clients.greetings_enabled. "Laimīgajiem"
 * klientiem tas tiek ieslēgts automātiski, bet atzīmēt var arī jebkuru citu.
 * Komandu ikdienā palaiž plānotājs; tā ir droša pret dubultu palaišanu
 * (dzimšanas dienai birthday_greeted_at, pārējiem email_logs ieraksts).
 */
class SendGreetings extends Command
{
    protected $signature = 'pdc:send-greetings';

    protected $description = 'Nosūta automātiskos apsveikumus klientiem (dzimšanas dienās, vārdadienās un svētkos).';

    public function handle(): int
    {
        $config = config('crm.greetings', []);

        if (! ($config['enabled'] ?? false)) {
            $this->info('Apsveikumu sūtīšana ir izslēgta (crm.greetings.enabled).');

            return self::SUCCESS;
        }

        $today = now()->startOfDay();
        $birthdays = $this->sendBirthdays($config['birthday'] ?? [], $today);
        $namedays = $this->sendNamedays($config['name_day'] ?? [], $today);
        $holidays = $this->sendHolidays($config['holidays'] ?? [], $today);

        $this->info("Nosūtīti apsveikumi: {$birthdays} dzimšanas dienās, {$namedays} vārdadienās, {$holidays} svētkos.");

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $template */
    private function sendBirthdays(array $template, Carbon $today): int
    {
        $subject = $template['subject'] ?? null;
        $body = $template['body'] ?? null;

        if (blank($subject) || blank($body)) {
            return 0;
        }

        $sent = 0;

        foreach ($this->eligibleClients() as $client) {
            if ($client->birthday_greeted_at?->year === $today->year) {
                continue;
            }

            $birthday = $client->birthdayThisYear();

            if (! $birthday || ! $birthday->isSameDay($today)) {
                continue;
            }

            if ($this->send($client, $subject, $body, 'greeting_birthday')) {
                $client->forceFill(['birthday_greeted_at' => $today->toDateString()])->saveQuietly();
                $sent++;
            }
        }

        return $sent;
    }

    /** @param array<string, mixed> $template */
    private function sendNamedays(array $template, Carbon $today): int
    {
        $subject = $template['subject'] ?? null;
        $body = $template['body'] ?? null;

        if (blank($subject) || blank($body)) {
            return 0;
        }

        $names = LatvianCalendar::nameDays($today);

        if ($names === []) {
            return 0;
        }

        $normalized = array_map(fn (string $name): string => mb_strtolower($name, 'UTF-8'), $names);
        $context = 'greeting_nameday:'.$today->format('m-d');
        $sent = 0;

        foreach ($this->eligibleClients() as $client) {
            if ($client->isLegalPerson()) {
                continue;
            }

            $first = mb_strtolower($this->firstName((string) $client->name), 'UTF-8');

            if ($first === '' || ! in_array($first, $normalized, true)) {
                continue;
            }

            if ($this->alreadySent($client, $context, $today)) {
                continue;
            }

            if ($this->send($client, $subject, $body, $context)) {
                $sent++;
            }
        }

        return $sent;
    }

    /** @param array<int, array<string, mixed>> $holidays */
    private function sendHolidays(array $holidays, Carbon $today): int
    {
        $context = 'greeting_holiday:'.$today->format('m-d');
        $sent = 0;

        foreach ($holidays as $holiday) {
            $date = LatvianCalendar::holidayDate($holiday, $today->year);

            if (! $date || ! $date->isSameDay($today)) {
                continue;
            }

            $subject = $holiday['subject'] ?? null;
            $body = $holiday['body'] ?? null;

            if (blank($subject) || blank($body)) {
                continue;
            }

            foreach ($this->eligibleClients() as $client) {
                if ($this->alreadySent($client, $context, $today)) {
                    continue;
                }

                if ($this->send($client, $subject, $body, $context)) {
                    $sent++;
                }
            }
        }

        return $sent;
    }

    /** @return Collection<int, Client> */
    private function eligibleClients(): Collection
    {
        return Client::query()
            ->where('greetings_enabled', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();
    }

    private function alreadySent(Client $client, string $context, Carbon $today): bool
    {
        return EmailLog::query()
            ->where('client_id', $client->id)
            ->where('context', $context)
            ->whereYear('created_at', $today->year)
            ->exists();
    }

    private function firstName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];

        return $parts[0] ?? '';
    }

    private function send(Client $client, string $subject, string $body, string $context): bool
    {
        $text = str_replace('{vārds}', (string) $client->name, $body);

        try {
            app(EmailSender::class)->send(
                to: (string) $client->email,
                subject: $subject,
                body: nl2br(e($text)),
                internal: true,
                context: ['context' => $context, 'client_id' => $client->id],
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('Greeting email failed', [
                'client' => $client->id,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
