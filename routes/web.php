<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FirmaController;
use App\Http\Controllers\KiriController;
use App\Http\Controllers\GmailAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Avalik
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Autenditud kasutajatele
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/vaheta-firma', [DashboardController::class, 'vahetaFirma'])->name('vaheta-firma');
    
    // Gmail ühendus
    Route::get('/gmail/auth', [GmailAuthController::class, 'redirect'])->name('gmail.auth');
    Route::get('/gmail/callback', [GmailAuthController::class, 'callback'])->name('gmail.callback');
    Route::get('/gmail/status', [GmailAuthController::class, 'status'])->name('gmail.status');
    Route::post('/gmail/disconnect', [GmailAuthController::class, 'disconnect'])->name('gmail.disconnect');
    
    // Firmad
    Route::resource('firma', FirmaController::class);
    Route::post('/firma/{firma}/sync', [FirmaController::class, 'sync'])->name('firma.sync');
    
    // Kirjad
    Route::get('/kirjad', [KiriController::class, 'index'])->name('kiri.index');
    Route::get('/kiri/{kiri}', [KiriController::class, 'show'])->name('kiri.show');
    Route::get('/kiri/{kiri}/reply', [KiriController::class, 'reply'])->name('kiri.reply');
    Route::post('/kiri/{kiri}/reply', [KiriController::class, 'sendReply'])->name('kiri.sendReply');
    Route::patch('/kiri/{kiri}/staatus', [KiriController::class, 'updateStaatus'])->name('kiri.staatus');
    Route::post('/kiri/{kiri}/markus', [KiriController::class, 'lisaMarkus'])->name('kiri.markus');
    Route::post('/kiri/{kiri}/teisalda', [KiriController::class, 'teisalda'])->name('kiri.teisalda');

    // Manused
    Route::get('/manus/{manus}', [KiriController::class, 'showManus'])->name('manus.show');
    Route::patch('/manus/{manus}/tyyp', [KiriController::class, 'updateManusTyyp'])->name('manus.tyyp');
    Route::get('/manus/{manus}/parse', [KiriController::class, 'parseManus'])->name('manus.parse');
    Route::post('/manus/{manus}/parse', [KiriController::class, 'parseManusWithAI'])->name('manus.parse.ai');

});

// Laravel Breeze autentimine
require __DIR__.'/auth.php';
