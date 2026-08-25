<?php

use App\Http\Controllers\Api\NoteController;
use Illuminate\Support\Facades\Route;

Route::get('notes/search', [NoteController::class, 'search'])
    ->middleware('throttle:ai')
    ->name('notes.search');

Route::post('notes/{note}/summary', [NoteController::class, 'summary'])
    ->middleware('throttle:ai')
    ->name('notes.summary');

Route::apiResource('notes', NoteController::class);
