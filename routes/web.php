<?php

use App\Http\Controllers\SignupController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('signup.show'));

Route::get('/signup', [SignupController::class, 'show'])->name('signup.show');
Route::post('/signup', [SignupController::class, 'submit'])->name('signup.submit')
    ->middleware('throttle:5,1');
