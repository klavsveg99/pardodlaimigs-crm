<?php

use App\Http\Controllers\CalendarQuickAddController;
use App\Http\Controllers\ClientAttachmentEmailController;
use App\Http\Controllers\PropertyAttachmentEmailController;
use App\Http\Controllers\PropertyGalleryDownloadController;
use App\Http\Controllers\PropertyMarketingController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\PublicSiteLeadController;
use App\Http\Controllers\RecordAttachmentEmailController;
use App\Http\Controllers\TaskNotificationController;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Trigger the Laravel scheduler over HTTP (no system cron on shared hosting).
// Called by the WordPress mu-plugin on every 5-minute sync tick.
Route::get('/cron-schedule', function () {
    if (request()->header('X-CRM-API-Key') !== config('wp-bridge.wordpress.api_key')) {
        abort(403);
    }
    Artisan::call('schedule:run');

    // Nav pastāvīga queue worker'a koplietotajā hostingā — iztukšojam rindu
    // ar katru cron piezvaniem (maks 45s no request laika).
    Artisan::call('queue:work', [
        '--stop-when-empty' => true,
        '--max-time' => 45,
        '--tries' => 1,
        '--sleep' => 1,
    ]);

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

    if (! $file || ! $file->isValid()) {
        $errorCode = $file?->getError() ?? ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);

        Log::warning('Attachment upload received no valid file', [
            'route' => 'property.upload-attachment',
            'upload_error_code' => $errorCode,
            'content_length' => (int) request()->server('CONTENT_LENGTH', 0),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ]);

        return response()->json([
            'message' => 'Fails netika saņemts - '.ClientAttachmentEmailController::uploadErrorMessage($errorCode).'.',
        ], 422);
    }

    $acceptedTypes = config('attachments.accepted_file_types', []);
    $maxSize = (int) config('attachments.max_size_kb', 10240);

    if (! empty($acceptedTypes) && ! in_array($file->getMimeType(), $acceptedTypes, true)) {
        Log::info('Attachment upload rejected: file type', [
            'route' => 'property.upload-attachment',
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'Šāda faila tipa augšupielāde nav atļauta: '.$file->getMimeType()
                .'. Atļautie tipi: '.implode(', ', config('attachments.accepted_mimes', [])).'.',
        ], 422);
    }

    if ($file->getSize() > $maxSize * 1024) {
        Log::info('Attachment upload rejected: file too large', [
            'route' => 'property.upload-attachment',
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'limit_kb' => $maxSize,
        ]);

        return response()->json([
            'message' => 'Fails ir pārāk liels: '.ClientAttachmentEmailController::sizeLabel((int) $file->getSize())
                .' (maksimums '.ClientAttachmentEmailController::sizeLabel($maxSize * 1024).').',
        ], 422);
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

    try {
        $path = $file->storeAs($dir, basename($candidate), 'public');

        if (! $path) {
            throw new RuntimeException('storeAs returned false for '.basename($candidate));
        }
    } catch (Throwable $e) {
        Log::error('Attachment upload failed', [
            'route' => 'property.upload-attachment',
            'name' => $originalName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'exception' => $e,
        ]);

        return response()->json(['message' => 'Neizdevās saglabāt failu - '.$e->getMessage().'.'], 500);
    }

    try {
        $abs = Storage::disk('public')->path($path);
        if (is_file($abs) && str_starts_with((string) $file->getMimeType(), 'image/')) {
            app(ImageOptimizer::class)->optimize($abs);
        }
    } catch (Throwable $e) {
        Log::warning('Attachment image optimization failed', [
            'route' => 'property.upload-attachment',
            'path' => $path,
            'exception' => $e,
        ]);
    }

    return response()->json([
        'path' => $path,
        'url' => Storage::disk('public')->url($path),
        'name' => $originalName,
    ]);
})->middleware(['auth', 'web'])->name('filament.admin.property.upload-attachment');

// ── Client attachments: upload + "Nosūtīt" email popup ────────
Route::post('/clients/{clientSlug}/attachments/upload',
    [ClientAttachmentEmailController::class, 'upload']
)->middleware(['auth', 'web'])->name('clients.attachments.upload');

Route::post('/clients/{clientSlug}/attachments/send-email',
    [ClientAttachmentEmailController::class, 'send']
)->middleware(['auth', 'web'])->name('clients.attachments.send-email');

Route::delete('/clients/{clientSlug}/attachments/{attachment}',
    [ClientAttachmentEmailController::class, 'destroy']
)->middleware(['auth', 'web'])->name('clients.attachments.destroy');

// ── Property attachments: upload + "Nosūtīt" email (associated client) ──
Route::post('/properties/{propertySlug}/attachments/upload',
    [PropertyAttachmentEmailController::class, 'upload']
)->middleware(['auth', 'web'])->name('properties.attachments.upload');

Route::post('/properties/{propertySlug}/attachments/send-email',
    [PropertyAttachmentEmailController::class, 'send']
)->middleware(['auth', 'web'])->name('properties.attachments.send-email');

Route::delete('/properties/{propertySlug}/attachments/{attachment}',
    [PropertyAttachmentEmailController::class, 'destroy']
)->middleware(['auth', 'web'])->name('properties.attachments.destroy');

// ── Property gallery: "Lejupielādēt visus" ZIP ────────────────
Route::get('/properties/{propertySlug}/gallery/download',
    [PropertyGalleryDownloadController::class, 'download']
)->middleware(['auth', 'web'])->name('properties.gallery.download');

// ── Thank-you email to one associated client (no attachments) ──
Route::post('/properties/{propertySlug}/clients/{client}/send-email',
    [PropertyAttachmentEmailController::class, 'sendToClient']
)->middleware(['auth', 'web'])->name('properties.clients.send-email');

// ── "PDF mārketings": A4 lapa ar automātiski aizpildītiem īpašuma datiem ──
Route::get('/properties/{propertySlug}/marketing',
    [PropertyMarketingController::class, 'show']
)->middleware(['auth', 'web'])->name('properties.marketing');

Route::post('/properties/{propertySlug}/marketing/ai',
    [PropertyMarketingController::class, 'generateAi']
)->middleware(['auth', 'web'])->name('properties.marketing.ai');

Route::post('/properties/{propertySlug}/marketing/pdf-description',
    [PropertyMarketingController::class, 'savePdfDescription']
)->middleware(['auth', 'web'])->name('properties.marketing.pdf-description');

// ── Viewing / Task attachments (associated client recipient) ──
foreach (['viewings' => 'Viewing', 'tasks' => 'Task'] as $segment => $suffix) {
    Route::post("/{$segment}/{id}/attachments/upload",
        [RecordAttachmentEmailController::class, "upload{$suffix}"]
    )->middleware(['auth', 'web'])->name("{$segment}.attachments.upload");

    Route::delete("/{$segment}/{id}/attachments/{attachment}",
        [RecordAttachmentEmailController::class, "destroy{$suffix}"]
    )->middleware(['auth', 'web'])->name("{$segment}.attachments.destroy");

    Route::post("/{$segment}/{id}/attachments/send-email",
        [RecordAttachmentEmailController::class, "send{$suffix}"]
    )->middleware(['auth', 'web'])->name("{$segment}.attachments.send-email");
}

// ── Calendar quick-add (click a day/time cell → task or viewing) ──
Route::get('/calendar/quick-add/options', [CalendarQuickAddController::class, 'options'])
    ->middleware(['auth', 'web'])->name('calendar.quick-add.options');

Route::post('/calendar/quick-add', [CalendarQuickAddController::class, 'store'])
    ->middleware(['auth', 'web'])->name('calendar.quick-add.store');

// ── Task assignment notification (manual, from the task page section) ──
Route::post('/tasks/{id}/notify/agent',
    [TaskNotificationController::class, 'agent']
)->middleware(['auth', 'web'])->name('tasks.notify.agent');

Route::post('/tasks/{id}/notify/izpilditajs',
    [TaskNotificationController::class, 'izpilditajs']
)->middleware(['auth', 'web'])->name('tasks.notify.izpilditajs');

// Public pardodlaimigs.lv site. Registered only when the site is switched on
// (config('site.enabled'), i.e. PUBLIC_SITE_ENABLED=1) AND the public host is
// pointed at this app. Until then pardodlaimigs.lv stays on WordPress and the
// new site lives on the static preview at /new/, so these routes stay dormant
// and cannot affect the live domain.
if (config('site.enabled')) {
    Route::domain(config('wp-bridge.public_host'))->group(function (): void {
        Route::get('/', [PublicSiteController::class, 'home'])->name('site.home');
        Route::get('/sitemap.xml', [PublicSiteController::class, 'sitemap'])->name('site.sitemap');

        Route::get('/properties', [PublicSiteController::class, 'properties'])->name('site.properties');
        Route::get('/{locale}/properties', [PublicSiteController::class, 'localizedProperties'])
            ->where('locale', 'en|ru|de')->name('site.properties.locale');
        Route::get('/blog', [PublicSiteController::class, 'blog'])->name('site.blog');
        Route::get('/{locale}/blog', [PublicSiteController::class, 'localizedBlog'])
            ->where('locale', 'en|ru|de')->name('site.blog.locale');

        Route::get('/ipasums/{slug}', [PublicSiteController::class, 'property'])->name('site.property');
        Route::get('/{locale}/ipasums/{slug}', [PublicSiteController::class, 'localizedProperty'])
            ->where('locale', 'en|ru|de')->name('site.property.locale');
        Route::get('/agent/{slug}', [PublicSiteController::class, 'agent'])->name('site.agent');
        Route::get('/{locale}/agent/{slug}', [PublicSiteController::class, 'localizedAgent'])
            ->where('locale', 'en|ru|de')->name('site.agent.locale');

        Route::post('/contact/valuation', [PublicSiteLeadController::class, 'store'])
            ->middleware('throttle:10,1')->name('site.contact.store');
        Route::post('/{locale}/contact/valuation', [PublicSiteLeadController::class, 'localizedStore'])
            ->where('locale', 'en|ru|de')->middleware('throttle:10,1')->name('site.contact.store.locale');

        Route::get('/{locale}', [PublicSiteController::class, 'localizedHome'])
            ->where('locale', 'en|ru|de')->name('site.home.locale');
        Route::get('/{locale}/{slug}', [PublicSiteController::class, 'localizedPage'])
            ->where(['locale' => 'en|ru|de', 'slug' => '[A-Za-z0-9-]+'])->name('site.page.locale');
        Route::get('/{slug}', [PublicSiteController::class, 'page'])
            ->where('slug', '(?!admin$|api$|up$|cron-schedule$)[A-Za-z0-9-]+')->name('site.page');
    });
}
