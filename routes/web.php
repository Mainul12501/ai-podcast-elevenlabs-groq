<?php

use App\Http\Controllers\PodcastController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PodcastController::class, 'index']);
Route::get('/voices', [PodcastController::class, 'voices'])->name('podcast.voices');
Route::post('/generate', [PodcastController::class, 'generate'])->name('podcast.generate');
