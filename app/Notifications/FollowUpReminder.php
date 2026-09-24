<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\FollowUpLead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Iknedēļas atgādinājums atbildīgajam aģentam, ka Follow Up līdim jāveic
 * atkārtots kontakts. Sūta tikai pdc:send-followup-reminders komanda.
 */
class FollowUpReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public FollowUpLead $lead,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lead = $this->lead;
        $client = $lead->client;

        $mail = (new MailMessage)
            ->subject('Follow Up atgādinājums: '.($client?->name ?? 'Līdis'))
            ->line('Pienācis laiks atkārtoti sazināties ar šo Follow Up līdi.')
            ->when($client, fn (MailMessage $m, $c) => $m->line('Klients: '.$c->name))
            ->when($client?->phone, fn (MailMessage $m, $p) => $m->line('Tālrunis: '.$p))
            ->when($lead->property?->title ?? $lead->property_address, fn (MailMessage $m, $p) => $m->line('Īpašums: '.$p))
            ->when($lead->reason, fn (MailMessage $m, $r) => $m->line('Iemesls: '.$r))
            ->when($lead->conversation_started_at, fn (MailMessage $m, $d) => $m->line('Saruna sākta: '.$d->format('d.m.Y')));

        if (filled($lead->notes)) {
            $mail->line('Piezīmes: '.$lead->notes);
        }

        return $mail
            ->action('Atvērt Follow Up līdi', url('/follow-up-leads/'.$lead->id.'/edit'))
            ->line('Atgādinājumi turpināsies, līdz līdis tiek atzīmēts kā "Sadarbība uzsākta" vai "Pārtraukts".');
    }
}
