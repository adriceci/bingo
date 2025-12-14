<?php

use App\Http\Controllers\BingoController;
use App\Http\Controllers\ProfileController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/bingo', [BingoController::class, 'store'])->name('bingo.store');
    Route::get('/bingo', [BingoController::class, 'create'])->name('bingo.create');
    Route::get('/bingo/{game}', [BingoController::class, 'show'])->name('bingo.show');
    Route::post('/bingo/{game}/activate', [BingoController::class, 'activate'])->name('bingo.activate');
    Route::post('/bingo/{game}/draw', [BingoController::class, 'draw'])->name('bingo.draw');
    Route::post('/bingo/{game}/cards', [BingoController::class, 'generateCards'])->name('bingo.cards');
    Route::post('/bingo/{game}/reset', [BingoController::class, 'reset'])->name('bingo.reset');
    Route::post('/bingo/{game}/close', [BingoController::class, 'close'])->name('bingo.close');
});

require __DIR__ . '/auth.php';
