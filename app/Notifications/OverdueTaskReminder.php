<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueTaskReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $task = $this->task;

        return (new MailMessage)
            ->subject('Kavēts uzdevums: '.$task->title)
            ->line('Jums ir kavēts uzdevums, kam jābūt pabeigtam līdz '.$task->due_at->format('d.m.Y H:i').'.')
            ->line('Uzdevums: '.$task->title)
            ->when($task->client, fn (MailMessage $m, $c) => $m->line('Klients: '.$c->name))
            ->when($task->deal, fn (MailMessage $m, $d) => $m->line('Darījums: '.$d->title.' (#'.$d->id.')'))
            ->action('Atvērt uzdevumu', url('/admin/tasks/'.$task->id.'/edit'))
            ->line('Lūdzu, pabeidziet šo uzdevumu pēc iespējas ātrāk.');
    }
}
