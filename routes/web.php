<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\PublicQrController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShortLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [LoginController::class, 'create'])->name('login');
    Route::post('/masuk', [LoginController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/masuk/google', [LoginController::class, 'google'])
        ->middleware('throttle:10,1')
        ->name('google.redirect');
    Route::get('/masuk/google/callback', [LoginController::class, 'googleCallback'])
        ->middleware('throttle:10,1')
        ->name('google.callback');
});

Route::post('/keluar', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [GeneratorController::class, 'index'])->name('home');
    Route::get('/buat', [GeneratorController::class, 'create'])->name('qr.create');
    Route::redirect('/saya', '/')->name('library');
    Route::get('/kelola/{code}/ubah', [GeneratorController::class, 'edit'])
        ->where('code', '[a-z0-9]{8}')
        ->name('qr.edit');
    Route::get('/kelola/{code}', [GeneratorController::class, 'manage'])
        ->where('code', '[a-z0-9]{8}')
        ->name('qr.manage');

    Route::post('/qr/pratinjau', [QrCodeController::class, 'preview'])
        ->middleware('throttle:60,1')
        ->name('qr.preview');
    Route::post('/qr/milik', [QrCodeController::class, 'owned'])
        ->middleware('throttle:30,1')
        ->name('qr.owned');
    Route::post('/qr', [QrCodeController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('qr.store');
    Route::post('/qr/{code}', [QrCodeController::class, 'update'])
        ->where('code', '[a-z0-9]{8}')
        ->middleware('throttle:30,1')
        ->name('qr.update');
    Route::post('/qr/{code}/hapus', [QrCodeController::class, 'destroy'])
        ->where('code', '[a-z0-9]{8}')
        ->middleware('throttle:30,1')
        ->name('qr.destroy');
    Route::post('/qr/{code}/buka', [QrCodeController::class, 'open'])
        ->where('code', '[a-z0-9]{8}')
        ->middleware('throttle:30,1')
        ->name('qr.open');

    Route::get('/pengaturan', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/pengaturan/sandi', [SettingsController::class, 'updatePassword'])
        ->middleware('throttle:5,1')
        ->name('settings.password');
    Route::post('/pengaturan/pengguna', [SettingsController::class, 'storeUser'])
        ->middleware('throttle:10,1')
        ->name('settings.users.store');

    Route::get('/tautan', [ShortLinkController::class, 'index'])->name('short-links.index');
    Route::post('/tautan', [ShortLinkController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('short-links.store');
    Route::get('/tautan/{code}', [ShortLinkController::class, 'show'])
        ->where('code', '[a-z0-9]{3,16}')
        ->name('short-links.show');
    Route::put('/tautan/{code}', [ShortLinkController::class, 'update'])
        ->where('code', '[a-z0-9]{3,16}')
        ->middleware('throttle:30,1')
        ->name('short-links.update');
    Route::post('/tautan/{code}/hapus', [ShortLinkController::class, 'destroy'])
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
Route::get('/q/{code}/berkas', [PublicQrController::class, 'file'])
    ->where('code', '[a-z0-9]{8}')
    ->name('qr.file');
Route::get('/q/{code}/vcard', [PublicQrController::class, 'vcard'])
    ->where('code', '[a-z0-9]{8}')
    ->name('qr.vcard');
Route::get('/q/{code}/kalender', [PublicQrController::class, 'calendar'])
    ->where('code', '[a-z0-9]{8}')
    ->name('qr.calendar');
Route::post('/q/{code}/hadir', [PublicQrController::class, 'checkIn'])
    ->where('code', '[a-z0-9]{8}')
    ->middleware('throttle:10,1')
    ->name('qr.checkin');
