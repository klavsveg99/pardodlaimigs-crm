<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $task = $this->task;
        $due = $task->due_at?->locale('lv')->translatedFormat('d.m.Y H:i') ?? '—';

        $mail = (new MailMessage)
            ->subject("Jauns uzdevums: {$task->title}")
            ->greeting('Sveiki!')
            ->line("Jums piešķirts jauns uzdevums: **{$task->title}**")
            ->line('')
            ->line('**Uzdevuma kopsavilkums**')
            ->line('Nosaukums: '.$task->title)
            ->when((bool) $task->body, fn ($m) => $m->line('Apraksts: '.(string) $task->body))
            ->line("Termiņš: {$due}")
            ->when($task->assignedTo, fn ($m) => $m->line('Aģents: '.$task->assignedTo->name))
            ->when($task->izpilditajs, fn ($m) => $m->line('Izpildītājs: '.$task->izpilditajs->name))
            ->when($task->client, fn ($m) => $m->line('Klients: '.$task->client->name))
            ->when($task->property, fn ($m) => $m->line('Īpašums: '.$task->property->title));

        // Pielikumu saites — izpildītājam nav CRM pieejas, tāpēc faili ir
        // jāsasniedz pa e-pastu.
        if (filled($attachments = $task->attachments()->get())) {
            $mail->line('');
            $mail->line('**Pielikumi**');
            $attachments->each(function (Attachment $file) use (&$mail): void {
                $url = $file->url;

                if (! str_starts_with($url, 'http')) {
                    $url = rtrim(config('app.url'), '/').$url;
                }

                $mail->line("- [{$file->original_name}]({$url})");
            });
        }

        return $mail->salutation('Pārdod Laimīgs');
    }
}