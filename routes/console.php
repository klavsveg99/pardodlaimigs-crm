<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// WPForms sync is retired: the WordPress site is gone (static site), and the
// public contact form posts straight to /api/public/leads. The job and the
// manual command stay for the historical entries, but nothing schedules them.

// Automātiski klientu apsveikumi (dzimšanas dienās, vārdadienās un svētkos) daily at 09:00
Schedule::command('pdc:send-greetings')
    ->dailyAt('09:00')
    ->name('send-greetings')
    ->withoutOverlapping();

// Full-data backup daily at 00:00 (APP_TIMEZONE, i.e. Europe/Riga on prod).
// The Hostinger hPanel cron runs `pdc:cron` every minute, so the first
// post-midnight tick runs it; runs once per day and keeps
// config('backup.keep') copies.
Schedule::command('pdc:backup-database')
    ->dailyAt('00:00')
    ->name('daily-backup')
    ->withoutOverlapping();

// Uploaded-files backup (attachments/avatars/...) as a SEPARATE archive 5
// minutes later, same retention as the database backup.
Schedule::command('pdc:backup-files')
    ->dailyAt('00:05')
    ->name('daily-files-backup')
    ->withoutOverlapping();

// ss.lv / city24.lv saved searches (development-property inbox). Each search
// picks its own frequency, so the command runs on every scheduler ping and
// only queues the ones whose interval has elapsed.
Schedule::command('pdc:scrape-property-searches')
    ->everyMinute()
    ->name('scrape-property-searches')
    ->withoutOverlapping();

// Drošības tīkls klientu statusiem ("Laimīgs"/"Aktīvs") - pamatā statuss
// mainās uzreiz, kad darījums tiek noslēgts vai atsaukts.
Schedule::command('pdc:sync-client-statuses')
    ->dailyAt('03:30')
    ->name('sync-client-statuses')
    ->withoutOverlapping();
