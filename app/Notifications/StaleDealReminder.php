<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Deal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaleDealReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Deal $deal,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $deal = $this->deal;
        $days = (int) now()->diffInDays($deal->updated_at);

        return (new MailMessage)
            ->subject('Darījums nav atjaunināts: '.$deal->title)
            ->line("Darījums \"{$deal->title}\" (#{$deal->id}) nav atjaunināts jau {$days} dienas.")
            ->line('Pašreizējais posms: '.($deal->stage_label ?? $deal->stage))
            ->when($deal->client, fn (MailMessage $m, $c) => $m->line('Klients: '.$c->name))
            ->when($deal->value_eur, fn (MailMessage $m, $d) => $m->line('Vērtība: '.number_format((float) $d->value_eur, 0, '.', ' ').' €'))
            ->action('Atvērt darījumu', url('/admin/deals/'.$deal->id.'/edit'))
            ->line('Lūdzu, atjauniniet darījuma statusu vai pievienojiet piezīmes.');
    }
}
