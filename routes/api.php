<?php

use Illuminate\Support\Facades\Route;
use Lupennat\NestedMany\Http\Controllers\NestedActionController;
use Lupennat\NestedMany\Http\Controllers\NestedController;

Route::get('/{resource}/detail-resources', [NestedController::class, 'detailResources']);
Route::get('/{resource}/edit-resources', [NestedController::class, 'editResources']);
Route::post('/{resource}/default-resources', [NestedController::class, 'defaultResources']);
Route::post('/{resource}/update-resources', [NestedController::class, 'updateResources']);

// Actions...
Route::get('/{resource}/actions', [NestedActionController::class, 'edit']);
Route::post('/{resource}/action', [NestedActionController::class, 'store']);
Route::patch('/{resource}/action', [NestedActionController::class, 'sync']);
