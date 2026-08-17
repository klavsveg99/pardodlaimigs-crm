<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Viewing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueViewingReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Viewing $viewing,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $viewing = $this->viewing;

        return (new MailMessage)
            ->subject('Kavēta apskate: '.$viewing->property?->title)
            ->line('Apskate, kas bija ieplānota '.$viewing->scheduled_at->format('d.m.Y H:i').', nav notikusi vai nav atzīmēta kā pabeigta.')
            ->when($viewing->property, fn (MailMessage $m, $p) => $m->line('Īpašums: '.$p->title))
            ->when($viewing->client, fn (MailMessage $m, $c) => $m->line('Klients: '.$c->name))
            ->action('Atvērt apskati', url('/admin/viewings/'.$viewing->id.'/edit'))
            ->line('Lūdzu, atzīmējiet apskates rezultātu.');
    }
}
