<?php

use App\Models\CrmProperty;
use App\Models\Task;
use App\Models\User;
use App\Models\WpformEntry;
use App\Services\Calendar\IcsExport;
use App\Services\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Trigger the Laravel scheduler over HTTP (no system cron on shared hosting).
// Called by the WordPress mu-plugin on every 5-minute sync tick.
Route::get('/cron-schedule', function () {
    if (request()->header('X-CRM-API-Key') !== config('wp-bridge.wordpress.api_key')) {
        abort(403);
    }
    Artisan::call('schedule:run');

    return response()->json(['ok' => true, 'ran_at' => now()->toIso8601String()]);
})->name('cron-schedule');

// Vecie īpašumu URL (/admin/crm-properties/66, .../66/edit) → jaunie slug URL.
// Resursam tagad ir slug "properties" ar ieraksta atslēgu "slug".
Route::get('/admin/crm-properties/{id}/{page?}', function (string $id, ?string $page = null) {
    $property = CrmProperty::find($id)
        ?? CrmProperty::where('slug', $id)->first();
    abort_unless($property?->slug, 404);
    $path = '/properties/'.$property->slug;
    if ($page === 'edit') {
        $path .= '/edit';
    }

    return redirect($path, 301);
})->where(['id' => '[A-Za-z0-9_-]+', 'page' => 'edit']);

// Panelis ir pārcelts uz sākumlapu ("/") — visi oriģinālie /admin/... un
// /public/... URL tiek pārvirzīti uz tīrajiem ceļiem bez /admin.
Route::redirect('/admin', '/', 301);
Route::redirect('/admin/{path}', '/{path}', 301)->where('path', '.*');

Route::get('/api/badges', function () {
    if (! auth()->check()) {
        return response()->json(['tasks' => 0, 'wpforms' => 0], 200);
    }

    return response()->json([
        'tasks' => Task::whereNull('completed_at')->count(),
        'wpforms' => WpformEntry::where('status', 'new')->count(),
    ]);
});

Route::get('/api/crm/attachment-proxy', function (Request $request) {
    $key = (string) $request->query('_k', '');
    $path = (string) $request->query('path', '');
    if ($key === '' || $path === '') {
        abort(400);
    }
    $expected = substr(hash_hmac('sha256', $path, (string) config('wp-bridge.wordpress.api_key')), 0, 16);
    if (! hash_equals($expected, $key)) {
        abort(403);
    }

    $disk = Storage::disk('public');
    $abs = $disk->path($path);
    if (! is_file($abs)) {
        $basename = basename($path);
        if ($basename !== '' && $basename !== '.' && $basename !== '/') {
            foreach (['attachments', 'avatars'] as $dir) {
                $candidate = $disk->path($dir.'/'.$basename);
                if (is_file($candidate)) {
                    $abs = $candidate;
                    break;
                }
            }
        }
    }
    if (! is_file($abs)) {
        abort(404);
    }

    $mime = File::mimeType($abs) ?: 'application/octet-stream';

    return response()->file($abs, [
        'Content-Type' => $mime,
        'Cache-Control' => 'no-store',
    ]);
})->name('crm.attachment.proxy');

// ── Calendar .ics feed (authenticated by token) ───────────────
// {user} var būt kā slug (raivo-pukes), tā arī vecais skaitliskais id —
// lai jau esošās kalendāru parakstīšanās saites paliek derīgas.
Route::get('/calendar/feed/{user}/{token}.ics', function (string $user, string $token) {
    $feedUser = User::where('slug', $user)->first() ?? User::whereKey((int) $user)->first();
    if (! $feedUser || ! hash_equals($feedUser->calendar_token ?? '', $token)) {
        abort(403);
    }

    $ics = app(IcsExport::class)->generateForUser($feedUser);

    return response($ics, 200, [
        'Content-Type' => 'text/calendar; charset=utf-8',
        'Content-Disposition' => 'inline; filename="pardod-laimigs-crm.ics"',
        'Cache-Control' => 'no-cache, must-revalidate',
    ]);
})->name('calendar.feed');

// ── Avatar upload endpoint (single file, avatars) ────────────────
Route::post('/avatar/upload', function () {
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

    $originalName = $file->getClientOriginalName();
    $path = $file->storeAs('avatars', $originalName, 'public');

    return response()->json([
        'path' => $path,
        'url' => Storage::disk('public')->url($path),
        'name' => $originalName,
    ]);
})->middleware(['auth', 'web'])->name('filament.admin.avatar.upload');

// ── Same-origin image proxy for the photo editor ───────────────
// Gallery images may live on the WP host (cross-origin). Loading them
// directly taints the editor canvas — Cropper's CORS fetch fails too since
// WP sends no ACAO headers — so cropping/saving breaks. This endpoint
// fetches the remote file server-side and streams it same-origin.
Route::get('/property/image-proxy', function (Request $request) {
    $url = (string) $request->query('url', '');
    $parts = parse_url($url);
    if (! $parts || ($parts['scheme'] ?? '') !== 'https') {
        abort(400);
    }
    $host = strtolower($parts['host'] ?? '');
    // SSRF guard: only our own WP/CRM hosts.
    if (! in_array($host, ['pardodlaimigs.lv', 'www.pardodlaimigs.lv', 'crm.pardodlaimigs.lv'], true)) {
        abort(403);
    }

    try {
        $response = Http::timeout(20)->get($url);
    } catch (Throwable) {
        abort(502);
    }
    if (! $response->successful()) {
        abort(404);
    }
    $mime = strtok((string) $response->header('Content-Type', ''), ';');
    if (! str_starts_with($mime, 'image/')) {
        abort(415);
    }
    $body = $response->body();
    if (strlen($body) > 25 * 1024 * 1024) {
        abort(413);
    }

    return response($body, 200, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->middleware(['auth', 'web'])->name('filament.admin.property.image-proxy');

// ── Attachment upload endpoint ────────────────────────────────
Route::post('/property/upload-attachment', function () {
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

    $originalName = $file->getClientOriginalName();
    $disk = Storage::disk('public');
    $base = pathinfo($originalName, PATHINFO_FILENAME);
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $dir = 'attachments';
    $candidate = $dir.'/'.$originalName;
    if ($disk->exists($candidate)) {
        $i = 1;
        do {
            $candidate = $dir.'/'.$base.'-'.$i.($ext ? '.'.$ext : '');
            $i++;
        } while ($disk->exists($candidate));
    }
    $path = $file->storeAs($dir, basename($candidate), 'public');

    try {
        $abs = Storage::disk('public')->path($path);
        if (is_file($abs) && str_starts_with((string) $file->getMimeType(), 'image/')) {
            app(ImageOptimizer::class)->optimize($abs);
        }
    } catch (Throwable $e) {
    }

    return response()->json([
        'path' => $path,
        'url' => Storage::disk('public')->url($path),
        'name' => $originalName,
    ]);
})->middleware(['auth', 'web'])->name('filament.admin.property.upload-attachment');
