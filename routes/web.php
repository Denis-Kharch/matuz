<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/cases/{slug}', [PageController::class, 'case'])->name('cases.show');
Route::get('/events', [PageController::class, 'events'])->name('events.index');
