<?php

use App\Http\Controllers\ProfileController;
use App\Http\Middleware\CheckAuthToken;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Services\ParseService;

Route::get('/debug-parse', function (ParseService $parseService) {
    return response()->json($parseService->getAllObjects('HealthRecord'), 200, [], JSON_PRETTY_PRINT);
});

Route::get('/create-admin', function (ParseService $parseService) {
    $response = $parseService->createUser('admin@email.com', 'securepassword');

    return response()->json($response);
});


Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
