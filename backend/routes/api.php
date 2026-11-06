<?php

use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\QueueController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\WorkerController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::apiResource('queues', QueueController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::post('/queues/{queue}/pause', [QueueController::class, 'pause']);
Route::post('/queues/{queue}/resume', [QueueController::class, 'resume']);

Route::apiResource('jobs', JobController::class)->only(['index', 'store', 'show']);
Route::post('/jobs/{job}/retry', [JobController::class, 'retry']);
Route::post('/jobs/{job}/cancel', [JobController::class, 'cancel']);

Route::apiResource('workers', WorkerController::class)->only(['index', 'show']);

Route::get('/stats/overview', [StatsController::class, 'overview']);
Route::get('/stats/queues', [StatsController::class, 'queues']);
Route::get('/stats/timeseries', [StatsController::class, 'timeseries']);
