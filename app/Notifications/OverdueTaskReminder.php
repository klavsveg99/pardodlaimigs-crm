<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\EmailSetting;
use App\Models\Task;
use App\Support\EmailShortcodes;
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

        $settings = (array) (EmailSetting::current()->data['overdue_reminder'] ?? []);
        $values = [
            'task' => (string) $task->title,
            'due' => (string) ($task->due_display ?? '—'),
            'client' => (string) ($task->client?->name ?? ''),
            'property' => (string) ($task->property?->selection_label ?? ''),
        ];

        $subject = EmailShortcodes::replace(
            (string) ($settings['subject'] ?? 'Kavēts uzdevums: {task}'),
            $values,
        );
        $message = EmailShortcodes::replace(
            (string) ($settings['body'] ?? 'Jums ir kavēts uzdevums, kam jābūt pabeigtam līdz {due}.'),
            $values,
        );

        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->line('Uzdevums: '.$task->title)
            ->when($task->client, fn (MailMessage $m, $c) => $m->line('Klients: '.$c->name))
            ->when($task->property, fn (MailMessage $m, $p) => $m->line('Īpašums: '.$p->selection_label))
            ->action('Atvērt uzdevumu', url('/tasks/'.$task->id.'/edit'))
            ->line('Lūdzu, pabeidziet šo uzdevumu pēc iespējas ātrāk.');
    }
}
