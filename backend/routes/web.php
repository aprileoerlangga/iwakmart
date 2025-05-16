<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Tambahkan route ini di routes/web.php
Route::get('/api-docs', function () {
    return view('api-documentation');
});