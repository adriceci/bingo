<?php

use App\Http\Controllers\BingoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('bingo.home');
    }
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/bingo', [BingoController::class, 'create'])->name('bingo.home');
    Route::post('/bingo/create', [BingoController::class, 'store'])->name('bingo.create');
    Route::get('/bingo/{game}', [BingoController::class, 'show'])->name('bingo.show');
    Route::post('/bingo/{game}/activate', [BingoController::class, 'activate'])->name('bingo.activate');
    Route::post('/bingo/{game}/draw', [BingoController::class, 'draw'])->name('bingo.draw');
    Route::post('/bingo/{game}/cards', [BingoController::class, 'generateCards'])->name('bingo.cards');
    Route::post('/bingo/{game}/reset', [BingoController::class, 'reset'])->name('bingo.reset');
    Route::post('/bingo/{game}/close', [BingoController::class, 'close'])->name('bingo.close');
});

require __DIR__ . '/auth.php';
