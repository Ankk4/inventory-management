<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReceiptImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');
    Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
    Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
    Route::post('/items/{item}/adjust', [ItemController::class, 'adjust'])->name('items.adjust');

    Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/receipts/image/{path}', [ReceiptController::class, 'image'])
        ->where('path', '.*')
        ->name('receipts.image');

    Route::get('/import', [ReceiptImportController::class, 'create'])->name('import.create');
    Route::post('/import/paste', [ReceiptImportController::class, 'parsePaste'])->name('import.paste');
    Route::post('/import/ollama', [ReceiptImportController::class, 'parseOllama'])->name('import.ollama');
    Route::post('/import/gemini', [ReceiptImportController::class, 'parseGemini'])->name('import.gemini');
    Route::post('/import/confirm', [ReceiptImportController::class, 'confirm'])->name('import.confirm');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
