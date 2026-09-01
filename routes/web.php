<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VoiceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/inicio');

Route::middleware('auth')->group(function () {
    Route::get('/inicio', [HomeController::class, 'index'])->name('home');

    Route::get('/conversacion', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversacion/nueva', [ConversationController::class, 'create'])->name('conversations.create');
    Route::get('/historial', [ConversationController::class, 'history'])->name('conversations.history');
    Route::get('/conversacion/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::delete('/conversacion/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');
    Route::post('/conversacion/mensajes', [ConversationController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('conversations.messages.store');
    Route::get('/voz', [VoiceController::class, 'index'])->name('voice.index');
    Route::post('/voz/mensajes', [VoiceController::class, 'store'])->name('voice.messages.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
