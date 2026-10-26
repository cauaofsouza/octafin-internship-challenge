<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'simulation');
Route::view('/offers', 'offers')->name('offers');
