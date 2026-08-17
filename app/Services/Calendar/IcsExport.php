<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use App\Models\Viewing;
use Carbon\Carbon;

class IcsExport
{
    public function generateForUser(User $user): string
    {
        $events = collect();

        // Viewings assigned to this agent
        $viewings = Viewing::query()
            ->with(['property', 'client'])
            ->where('agent_user_id', $user->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now()->subDays(7))
            ->get();

        foreach ($viewings as $v) {
            $start = $v->scheduled_at;
            $end = $start->copy()->addMinutes($v->duration_min ?? 60);
            $summary = 'Apskate: '.($v->property?->title ?? '—');
            $description = collect([
                $v->client ? 'Klients: '.$v->client->name : null,
                $v->notes_md ? 'Piezīmes: '.$v->notes_md : null,
            ])->filter()->implode('\n');

            $events->push($this->makeEvent(
                uid: "viewing-{$v->id}@crm.pardodlaimigs.lv",
                summary: $summary,
                description: $description,
                start: $start,
                end: $end,
            ));
        }

        // Tasks assigned to this user
        $tasks = Task::query()
            ->with(['client', 'deal'])
            ->where('assigned_user_id', $user->id)
            ->whereNull('completed_at')
            ->where('due_at', '>=', now()->subDays(7))
            ->get();

        foreach ($tasks as $t) {
            $start = $t->due_at;
            $end = $start->copy()->addMinutes(30);
            $summary = 'Uzdevums: '.$t->title;
            $description = collect([
                $t->client ? 'Klients: '.$t->client->name : null,
                $t->deal ? 'Darījums: '.$t->deal->title : null,
                $t->body ? $t->body : null,
            ])->filter()->implode('\n');

            $events->push($this->makeEvent(
                uid: "task-{$t->id}@crm.pardodlaimigs.lv",
                summary: $summary,
                description: $description,
                start: $start,
                end: $end,
            ));
        }

        // Deals with expected close date
        $deals = Deal::query()
            ->with(['client', 'property'])
            ->where('owner_user_id', $user->id)
            ->where('stage', '!=', 'pardots')
            ->whereNotNull('expected_close_date')
            ->where('expected_close_date', '>=', now()->subDays(7))
            ->get();

        foreach ($deals as $d) {
            $start = Carbon::parse($d->expected_close_date)->startOfDay();
            $end = $start->copy()->endOfDay();
            $summary = 'Darījums: '.$d->title;
            $description = collect([
                $d->client ? 'Klients: '.$d->client->name : null,
                $d->property ? 'Īpašums: '.$d->property->title : null,
                $d->value_eur ? 'Vērtība: '.number_format((float) $d->value_eur, 0, '.', ' ').' €' : null,
                'Posms: '.($d->stage_label ?? $d->stage),
            ])->filter()->implode('\n');

            $events->push($this->makeEvent(
                uid: "deal-{$d->id}@crm.pardodlaimigs.lv",
                summary: $summary,
                description: $description,
                start: $start,
                end: $end,
                allDay: true,
            ));
        }

        return $this->wrapCalendar($events->implode("\n"));
    }

    private function makeEvent(string $uid, string $summary, string $description, Carbon $start, Carbon $end, bool $allDay = false): string
    {
        $lines = [
            'BEGIN:VEVENT',
            "UID:{$uid}",
            'DTSTAMP:'.now()->format('Ymd\THis\Z'),
        ];

        if ($allDay) {
            $lines[] = 'DTSTART;VALUE=DATE:'.$start->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$end->addDay()->format('Ymd');
        } else {
            $lines[] = 'DTSTART;TZID=Europe/Riga:'.$start->format('Ymd\THis');
            $lines[] = 'DTEND;TZID=Europe/Riga:'.$end->format('Ymd\THis');
        }

        $lines[] = 'SUMMARY:'.$this->escapeText($summary);
        $lines[] = 'DESCRIPTION:'.$this->escapeText($description);
        $lines[] = 'END:VEVENT';

        return implode("\r\n", $lines);
    }

    private function wrapCalendar(string $events): string
    {
        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Pardod Laimigs CRM//Calendar//LV',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:Pārdod Laimīgs CRM',
            'X-WR-TIMEZONE:Europe/Riga',
            'BEGIN:VTIMEZONE',
            'TZID:Europe/Riga',
            'BEGIN:DAYLIGHT',
            'TZOFFSETFROM:+0200',
            'TZOFFSETTO:+0300',
            'TZNAME:EEST',
            'DTSTART:19700329T030000',
            'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU',
            'END:DAYLIGHT',
            'BEGIN:STANDARD',
            'TZOFFSETFROM:+0300',
            'TZOFFSETTO:+0200',
            'TZNAME:EET',
            'DTSTART:19701025T040000',
            'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU',
            'END:STANDARD',
            'END:VTIMEZONE',
            $events,
            'END:VCALENDAR',
        ])."\r\n";
    }

    private function escapeText(string $text): string
    {
        return str_replace(
            ["\n", "\r", ',', ';'],
            ['\\n', '\\r', '\\,', '\\;'],
            $text,
        );
    }
}
