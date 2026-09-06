<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReceiptImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('home')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/home', HomeController::class)->name('home');
    Route::get('/dashboard', fn () => redirect()->route('home'))->name('dashboard');

    Route::post('/inventories', [InventoryController::class, 'store'])->name('inventories.store');

    Route::prefix('/inventories/{inventory}')->group(function () {
        Route::get('/', [InventoryController::class, 'show'])->name('inventories.show');
        Route::get('/ask', [InventoryController::class, 'ask'])->name('inventories.ask');
        Route::post('/ask', [InventoryController::class, 'askStore'])->name('inventories.ask.store');

        Route::get('/scan', [ReceiptImportController::class, 'create'])->name('inventories.scan');
        Route::post('/scan/paste', [ReceiptImportController::class, 'parsePaste'])->name('inventories.scan.paste');
        Route::post('/scan/ollama', [ReceiptImportController::class, 'parseOllama'])->name('inventories.scan.ollama');
        Route::post('/scan/gemini', [ReceiptImportController::class, 'parseGemini'])->name('inventories.scan.gemini');
        Route::post('/scan/confirm', [ReceiptImportController::class, 'confirm'])->name('inventories.scan.confirm');

        Route::get('/items/create', [InventoryController::class, 'createItem'])->name('inventories.items.create');
        Route::post('/items', [InventoryController::class, 'storeItem'])->name('inventories.items.store');
        Route::get('/items/{item}', [InventoryController::class, 'showItem'])->name('inventories.items.show');
        Route::get('/items/{item}/edit', [InventoryController::class, 'editItem'])->name('inventories.items.edit');
        Route::put('/items/{item}', [InventoryController::class, 'updateItem'])->name('inventories.items.update');
        Route::post('/items/{item}/adjust', [InventoryController::class, 'adjustItem'])->name('inventories.items.adjust');
    });

    // Legacy redirects
    Route::get('/items', fn () => redirect()->route('home'))->name('items.index');
    Route::get('/import', fn () => redirect()->route('home'))->name('import.create');

    Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/receipts/image/{path}', [ReceiptController::class, 'image'])
        ->where('path', '.*')
        ->name('receipts.image');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
