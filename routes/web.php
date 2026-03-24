<?php

use App\Http\Controllers\PodcastController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PodcastController::class, 'index']);
Route::get('/history', [PodcastController::class, 'history'])->name('podcast.history');
Route::get('/voices', [PodcastController::class, 'voices'])->name('podcast.voices');
Route::post('/generate-script', [PodcastController::class, 'generateScript'])->name('podcast.generateScript');
Route::post('/generate-audio', [PodcastController::class, 'generateAudio'])->name('podcast.generateAudio');
