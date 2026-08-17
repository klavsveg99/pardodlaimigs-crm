<?php

use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use App\Models\WpformEntry;
use App\Services\Calendar\IcsExport;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/api/badges', function () {
    return response()->json([
        'deals' => Deal::where('stage', '!=', 'pardots')->count(),
        'tasks' => Task::whereNull('completed_at')->count(),
        'wpforms' => WpformEntry::where('status', 'new')->count(),
    ]);
})->middleware('auth');

// ── Calendar .ics feed (authenticated by token) ───────────────
Route::get('/calendar/feed/{user}/{token}.ics', function (User $user, string $token) {
    if (! hash_equals($user->calendar_token ?? '', $token)) {
        abort(403);
    }

    $ics = app(IcsExport::class)->generateForUser($user);

    return response($ics, 200, [
        'Content-Type' => 'text/calendar; charset=utf-8',
        'Content-Disposition' => 'inline; filename="pardod-laimigs-crm.ics"',
        'Cache-Control' => 'no-cache, must-revalidate',
    ]);
})->name('calendar.feed');
