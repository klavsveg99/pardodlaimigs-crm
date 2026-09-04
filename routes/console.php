<?php

use App\Jobs\ReconcileAllProperties;
use App\Jobs\SyncWpForms;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// WP property feed sync every 5 minutes (recommended 5–15 min interval)
Schedule::job(new ReconcileAllProperties)
    ->cron(config('wp-bridge.feed.reconcile_cron'))
    ->name('wp-reconcile')
    ->withoutOverlapping();

// WPForms contact form submissions every 5 minutes (runs only when triggered;
// runs every minute so the mu-plugin's ~5-min ping always hits a "due" window)
Schedule::job(new SyncWpForms)
    ->everyMinute()
    ->name('wpforms-sync')
    ->withoutOverlapping();

// Reminder emails daily at 08:00
Schedule::command('pdc:send-reminders')
    ->dailyAt('08:00')
    ->name('send-reminders')
    ->withoutOverlapping();
