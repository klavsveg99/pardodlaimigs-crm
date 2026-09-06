<?php

use App\Models\Task;
use App\Models\User;
use App\Models\WpformEntry;
use App\Services\Calendar\IcsExport;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Trigger the Laravel scheduler over HTTP (no system cron on shared hosting).
// Called by the WordPress mu-plugin on every 5-minute sync tick.
Route::get('/cron-schedule', function () {
    if (request()->header('X-CRM-API-Key') !== config('wp-bridge.wordpress.api_key')) {
        abort(403);
    }
    \Illuminate\Support\Facades\Artisan::call('schedule:run');

    return response()->json(['ok' => true, 'ran_at' => now()->toIso8601String()]);
})->name('cron-schedule');

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/api/badges', function () {
    if (! auth()->check()) {
        return response()->json(['tasks' => 0, 'wpforms' => 0], 200);
    }
    return response()->json([
        'tasks' => Task::whereNull('completed_at')->count(),
        'wpforms' => WpformEntry::where('status', 'new')->count(),
    ]);
});

Route::get('/api/crm/attachment-proxy', function (\Illuminate\Http\Request $request) {
    $key = (string) $request->query('_k', '');
    $path = (string) $request->query('path', '');
    if ($key === '' || $path === '') abort(400);
    $expected = substr(hash_hmac('sha256', $path, (string) config('wp-bridge.wordpress.api_key')), 0, 16);
    if (! hash_equals($expected, $key)) abort(403);
    $abs = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
    if (! is_file($abs)) abort(404);
    $mime = \Illuminate\Support\Facades\File::mimeType($abs) ?: 'application/octet-stream';
    return response()->file($abs, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->name('crm.attachment.proxy');

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

    $originalName = $file->getClientOriginalName();
    $path = $file->storeAs('avatars', $originalName, 'public');

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

    $originalName = $file->getClientOriginalName();
    $disk = Storage::disk('public');
    $base = pathinfo($originalName, PATHINFO_FILENAME);
    $ext  = pathinfo($originalName, PATHINFO_EXTENSION);
    $dir  = 'attachments';
    $candidate = $dir . '/' . $originalName;
    if ($disk->exists($candidate)) {
        $i = 1;
        do {
            $candidate = $dir . '/' . $base . '-' . $i . ($ext ? '.' . $ext : '');
            $i++;
        } while ($disk->exists($candidate));
    }
    $path = $file->storeAs($dir, basename($candidate), 'public');

    try {
        $abs = Storage::disk('public')->path($path);
        if (is_file($abs)) {
            $info = @getimagesize($abs);
            if ($info && ($info[0] > 1920 || $info[1] > 1920)) {
                [$width, $height, $type] = $info;
                $ratio = min(1920 / $width, 1920 / $height);
                $newW = (int) max(1, round($width * $ratio));
                $newH = (int) max(1, round($height * $ratio));
                $src = match ($type) {
                    IMAGETYPE_JPEG => @imagecreatefromjpeg($abs),
                    IMAGETYPE_PNG  => @imagecreatefrompng($abs),
                    IMAGETYPE_WEBP => @imagecreatefromwebp($abs),
                    IMAGETYPE_GIF  => @imagecreatefromgif($abs),
                    default        => null,
                };
                if ($src) {
                    $dst = imagecreatetruecolor($newW, $newH);
                    if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
                    }
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
                    match ($type) {
                        IMAGETYPE_JPEG => imagejpeg($dst, $abs, 88),
                        IMAGETYPE_PNG  => imagepng($dst, $abs, 6),
                        IMAGETYPE_WEBP => imagewebp($dst, $abs, 88),
                        IMAGETYPE_GIF  => imagegif($dst, $abs),
                        default        => null,
                    };
                    imagedestroy($src);
                    imagedestroy($dst);
                }
            }
        }
    } catch (\Throwable $e) {
    }

    $originalName = $file->getClientOriginalName();

    return response()->json([
        'path' => $path,
        'url' => Storage::disk('public')->url($path),
        'name' => $originalName,
    ]);
})->middleware(['auth', 'web'])->name('filament.admin.property.upload-attachment');
