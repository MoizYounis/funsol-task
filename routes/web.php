<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VideoProjectController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoExportController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Video Editor Routes
    Route::prefix('video-editor')->name('video-editor.')->group(function () {
        Route::get('/projects', [VideoProjectController::class, 'index'])->name('projects');
        Route::get('/projects/create', [VideoProjectController::class, 'create'])->name('create');
        Route::post('/projects', [VideoProjectController::class, 'store'])->name('store');
        Route::get('/projects/{project}/edit', [VideoProjectController::class, 'edit'])->name('edit');
        Route::patch('/projects/{project}', [VideoProjectController::class, 'update'])->name('update');
        Route::delete('/projects/{project}', [VideoProjectController::class, 'destroy'])->name('destroy');

        // Video management
        Route::post('/projects/{project}/videos', [VideoController::class, 'store'])->name('videos.store');
        Route::patch('/videos/{video}', [VideoController::class, 'update'])->name('videos.update');
        Route::post('/videos/{video}/delete', [VideoController::class, 'destroy'])->name('videos.destroy');
        Route::post('/projects/{project}/videos/reorder', [VideoController::class, 'reorder'])->name('videos.reorder');
        Route::get('/videos/{video}/stream', [VideoController::class, 'stream'])->name('videos.stream');

        // Video export
        Route::post('/projects/{project}/export', [VideoExportController::class, 'export'])->name('export');
        Route::get('/projects/{project}/download', [VideoExportController::class, 'download'])->name('download');
        Route::get('/projects/{project}/status', [VideoExportController::class, 'status'])->name('status');
    });
});

require __DIR__ . '/auth.php';
