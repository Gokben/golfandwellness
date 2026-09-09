<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GolfLoginController;
use App\Http\Middleware\GolfApiAccess;

Route::get('/', [GolfLoginController::class, 'form']);
Route::get('/login', [GolfLoginController::class, 'form'])->name('login');
Route::post('/login', [GolfLoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [GolfLoginController::class, 'logout'])->name('logout');
Route::get('/desktop', [GolfLoginController::class, 'desktop'])->name('home');
Route::get('/desktop.html', [GolfLoginController::class, 'desktop']);
Route::get('/index.html', [GolfLoginController::class, 'form']);
Route::get('/login.html', [GolfLoginController::class, 'form']);
Route::prefix('api')->middleware(GolfApiAccess::class)->group(base_path('routes/api.php'));
Route::redirect('/preview', '/golf/')->name('preview');
