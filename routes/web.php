<?php

use App\Models\Deal;
use App\Models\Task;
use App\Models\WpformEntry;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/api/badges', function () {
    return response()->json([
        'deals' => Deal::whereNotIn('stage', ['closed_won', 'closed_lost'])->count(),
        'tasks' => Task::whereNull('completed_at')->count(),
        'wpforms' => WpformEntry::where('status', 'new')->count(),
    ]);
})->middleware('auth');
