<?php

use App\Http\Controllers\Api\CrmPropertyFeedController;
use App\Http\Controllers\Api\GdprController;
use App\Http\Controllers\PublicSiteLeadController;
use Illuminate\Support\Facades\Route;

Route::post('/gdpr/request-export', [GdprController::class, 'requestExport']);
Route::get('/gdpr/export/{email}', [GdprController::class, 'export'])->name('gdpr.export');
Route::post('/gdpr/request-erase', [GdprController::class, 'requestErase']);
Route::get('/gdpr/erase/{email}', [GdprController::class, 'erase'])->name('gdpr.erase');

// ── CRM Property Feed (WordPress pulls from here) ─────────────
Route::get('/crm/properties', [CrmPropertyFeedController::class, 'index']);
Route::post('/crm/properties/link', [CrmPropertyFeedController::class, 'link']);
Route::get('/crm/properties/{id}', [CrmPropertyFeedController::class, 'show']);
Route::get('/crm/agents', [CrmPropertyFeedController::class, 'agents']);

// Public feed for the static frontend (/new/). Read-only, no key: the site
// merges the live listings over its bundled snapshot so properties added or
// changed in the CRM show up without a rebuild.
Route::get('/public/properties', [CrmPropertyFeedController::class, 'publicProperties']);

// Public slug -> status map. Kept for older cached bundles of the /new/ site.
Route::get('/public/property-statuses', [CrmPropertyFeedController::class, 'publicStatuses']);

// Valuation/contact form from /new/. Cross-origin JSON with reCAPTCHA.
Route::post('/public/leads', [PublicSiteLeadController::class, 'storeApi'])
    ->middleware('throttle:10,1');
