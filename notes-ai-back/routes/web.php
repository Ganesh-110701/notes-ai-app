<?php

use Illuminate\Support\Facades\Route;

// Simple AI Notes frontend (vanilla JS, talks to routes/api.php).
Route::get('/', function () {
    return view('notes');
});
