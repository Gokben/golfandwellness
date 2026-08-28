<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'app')->name('home');
Route::view('/login', 'auth.login')->name('login');
Route::redirect('/preview', '/')->name('preview');
