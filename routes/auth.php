<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetCodeController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('auth/google/redirect', [GoogleController::class, 'redirect'])
        ->name('auth.google.redirect');

    Route::get('auth/google/callback', [GoogleController::class, 'callback'])
        ->name('auth.google.callback');

    
    Route::get('forgot-password', [PasswordResetCodeController::class, 'showEmailForm'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetCodeController::class, 'sendCode'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('forgot-password/codigo', [PasswordResetCodeController::class, 'showCodeForm'])
        ->name('password.code');

    Route::post('forgot-password/codigo', [PasswordResetCodeController::class, 'verifyCode'])
        ->middleware('throttle:10,1')
        ->name('password.code.verify');

    Route::post('forgot-password/reenviar', [PasswordResetCodeController::class, 'resendCode'])
        ->middleware('throttle:3,1')
        ->name('password.code.resend');

    Route::get('forgot-password/nueva-clave', [PasswordResetCodeController::class, 'showResetForm'])
        ->name('password.reset.form');

    Route::post('forgot-password/nueva-clave', [PasswordResetCodeController::class, 'resetPassword'])
        ->middleware('throttle:6,1')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
