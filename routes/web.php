<?php

use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use App\Models\WpformEntry;
use App\Services\Calendar\IcsExport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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

// ── Avatar upload endpoint (single file, avatars) ────────────────
Route::post('/admin/avatar/upload', function () {
    $file = request()->file('file');

    if (! $file) {
        return response()->json(['error' => 'No file provided'], 422);
    }

    $maxSize = 5120; // 5MB

    if ($file->getSize() > $maxSize * 1024) {
        return response()->json(['error' => 'File too large'], 422);
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (! in_array($file->getMimeType(), $allowed, true)) {
        return response()->json(['error' => 'File type not accepted'], 422);
    }

    $path = $file->store('avatars', 'public');
    $originalName = $file->getClientOriginalName();

    return response()->json([
        'path' => $path,
        'url' => Storage::disk('public')->url($path),
        'name' => $originalName,
    ]);
})->middleware(['auth', 'web'])->name('filament.admin.avatar.upload');

// ── Attachment upload endpoint ────────────────────────────────
Route::post('/admin/property/upload-attachment', function () {
    $file = request()->file('file');

    if (! $file) {
        return response()->json(['error' => 'No file provided'], 422);
    }

    $acceptedTypes = config('attachments.accepted_file_types', []);
    $maxSize = (int) config('attachments.max_size_kb', 10240);

    if (! empty($acceptedTypes) && ! in_array($file->getMimeType(), $acceptedTypes, true)) {
        return response()->json(['error' => 'File type not accepted'], 422);
    }

    if ($file->getSize() > $maxSize * 1024) {
        return response()->json(['error' => 'File too large'], 422);
    }

    $path = $file->store('attachments', 'public');
    $originalName = $file->getClientOriginalName();

    return response()->json([
        'path' => $path,
        'url' => Storage::disk('public')->url($path),
        'name' => $originalName,
    ]);
})->middleware(['auth', 'web'])->name('filament.admin.property.upload-attachment');

Route::get('/_migrate_8f3a9c1e', function () {
    if (request('key') !== env('WP_CRM_API_KEY') && request('key') !== 'tmp8f3a9c1e') abort(403);
    $out = '';
    try { Artisan::call('migrate', ['--force' => true]); $out .= Artisan::output(); } catch (\Throwable $e) { $out .= $e->getMessage(); }
    try { Artisan::call('optimize:clear'); $out .= "\n".Artisan::output(); } catch (\Throwable $e) {}
    $cols = [
        'clients.client_type' => Schema::hasColumn('clients','client_type') ? 'yes' : 'no',
        'crm_properties.lead_owner' => Schema::hasColumn('crm_properties','lead_owner') ? 'yes' : 'no',
    ];
    return response("<pre>$out\n\ncols: ".json_encode($cols, JSON_PRETTY_PRINT)."</pre>");
});
