<?php

declare(strict_types=1);

namespace App\Notifications;

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

        return (new MailMessage)
            ->subject("Jauns uzdevums: {$task->title}")
            ->greeting('Sveiki!')
            ->line("Jums piešķirts jauns uzdevums: **{$task->title}**")
            ->when((bool) $task->body, fn ($m) => $m->line((string) $task->body))
            ->line("Termiņš: {$due}")
            ->when($task->client, fn ($m) => $m->line("Klients: {$task->client->name}"))
            ->action('Atvērt uzdevumu', url("/tasks/{$task->id}/edit"))
            ->salutation('Pārdod Laimīgs');
    }
}