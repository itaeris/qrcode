<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\PublicQrController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\QrShareController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShortLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware(['throttle:5,1', 'throttle:login-email']);
    Route::get('/login/google', [LoginController::class, 'google'])
        ->middleware('throttle:10,1')
        ->name('google.redirect');
    Route::get('/login/google/callback', [LoginController::class, 'googleCallback'])
        ->middleware('throttle:10,1')
        ->name('google.callback');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [GeneratorController::class, 'index'])->name('home');
    Route::get('/create', [GeneratorController::class, 'create'])->name('qr.create');
    Route::redirect('/library', '/')->name('library');
    Route::get('/manage/{code}/edit', [GeneratorController::class, 'edit'])
        ->where('code', '[a-z0-9]{8}')
        ->name('qr.edit');
    Route::get('/manage/{code}', [GeneratorController::class, 'manage'])
        ->where('code', '[a-z0-9]{8}')
        ->name('qr.manage');

    Route::post('/qr/preview', [QrCodeController::class, 'preview'])
        ->middleware('throttle:60,1')
        ->name('qr.preview');
    Route::post('/qr/owned', [QrCodeController::class, 'owned'])
        ->middleware('throttle:30,1')
        ->name('qr.owned');
    Route::post('/qr', [QrCodeController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('qr.store');
    Route::post('/qr/{code}', [QrCodeController::class, 'update'])
        ->where('code', '[a-z0-9]{8}')
        ->middleware('throttle:30,1')
        ->name('qr.update');
    Route::post('/qr/{code}/delete', [QrCodeController::class, 'destroy'])
        ->where('code', '[a-z0-9]{8}')
        ->middleware('throttle:30,1')
        ->name('qr.destroy');
    Route::post('/qr/{code}/open', [QrCodeController::class, 'open'])
        ->where('code', '[a-z0-9]{8}')
        ->middleware('throttle:30,1')
        ->name('qr.open');
    Route::post('/qr/{code}/shares', [QrShareController::class, 'store'])
        ->where('code', '[a-z0-9]{8}')
        ->middleware('throttle:30,1')
        ->name('qr.shares.store');
    Route::post('/qr/{code}/shares/{member}', [QrShareController::class, 'update'])
        ->where(['code' => '[a-z0-9]{8}', 'member' => '[0-9]+'])
        ->middleware('throttle:30,1')
        ->name('qr.shares.update');
    Route::post('/qr/{code}/shares/{member}/delete', [QrShareController::class, 'destroy'])
        ->where(['code' => '[a-z0-9]{8}', 'member' => '[0-9]+'])
        ->middleware('throttle:30,1')
        ->name('qr.shares.destroy');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])
        ->middleware('throttle:5,1')
        ->name('settings.password');
    Route::post('/settings/users', [SettingsController::class, 'storeUser'])
        ->middleware('throttle:10,1')
        ->name('settings.users.store');

    Route::get('/links', [ShortLinkController::class, 'index'])->name('short-links.index');
    Route::post('/links', [ShortLinkController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('short-links.store');
    Route::get('/links/{code}', [ShortLinkController::class, 'show'])
        ->where('code', '[a-z0-9]{3,16}')
        ->name('short-links.show');
    Route::put('/links/{code}', [ShortLinkController::class, 'update'])
        ->where('code', '[a-z0-9]{3,16}')
        ->middleware('throttle:30,1')
        ->name('short-links.update');
    Route::post('/links/{code}/delete', [ShortLinkController::class, 'destroy'])
        ->where('code', '[a-z0-9]{3,16}')
        ->middleware('throttle:30,1')
        ->name('short-links.destroy');
});

Route::get('/s/{code}', [ShortLinkController::class, 'visit'])
    ->where('code', '[a-z0-9]{3,16}')
    ->middleware('throttle:120,1')
    ->name('short.visit');

Route::get('/q/{code}', [PublicQrController::class, 'show'])
    ->where('code', '[a-z0-9]{8}')
    ->middleware('throttle:120,1')
    ->name('qr.show');
Route::get('/q/{code}/file', [PublicQrController::class, 'file'])
    ->where('code', '[a-z0-9]{8}')
    ->name('qr.file');
Route::get('/q/{code}/vcard', [PublicQrController::class, 'vcard'])
    ->where('code', '[a-z0-9]{8}')
    ->name('qr.vcard');
Route::get('/q/{code}/calendar', [PublicQrController::class, 'calendar'])
    ->where('code', '[a-z0-9]{8}')
    ->name('qr.calendar');
Route::post('/q/{code}/check-in', [PublicQrController::class, 'checkIn'])
    ->where('code', '[a-z0-9]{8}')
    ->middleware('throttle:10,1')
    ->name('qr.checkin');
