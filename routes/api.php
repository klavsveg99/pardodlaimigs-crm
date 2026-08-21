<?php

use App\Http\Controllers\Api\CrmPropertyFeedController;
use App\Http\Controllers\Api\GdprController;
use Illuminate\Support\Facades\Route;

Route::post('/gdpr/request-export', [GdprController::class, 'requestExport']);
Route::get('/gdpr/export/{email}', [GdprController::class, 'export'])->name('gdpr.export');
Route::post('/gdpr/request-erase', [GdprController::class, 'requestErase']);
Route::get('/gdpr/erase/{email}', [GdprController::class, 'erase'])->name('gdpr.erase');

// ── CRM Property Feed (WordPress pulls from here) ─────────────
Route::get('/crm/properties', [CrmPropertyFeedController::class, 'index']);
Route::get('/crm/properties/{id}', [CrmPropertyFeedController::class, 'show']);
Route::get('/crm/agents', [CrmPropertyFeedController::class, 'agents']);
