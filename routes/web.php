<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\PublicQrController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [LoginController::class, 'create'])->name('login');
    Route::post('/masuk', [LoginController::class, 'store'])->middleware('throttle:5,1');
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
});

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
