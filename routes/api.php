<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{AuthController, ThesisController, StageController,
    SubmissionController, CommentController, DashboardController};

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/supervisors', [AuthController::class, 'supervisors']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/activities', [DashboardController::class, 'activities']);

    Route::get('/theses', [ThesisController::class, 'index']);
    Route::get('/theses/{thesis}', [ThesisController::class, 'show']);
    Route::get('/theses/{thesis}/stages', [StageController::class, 'index']);
    Route::put('/stages/{stage}', [StageController::class, 'update']);

    Route::get('/stages/{stage}/submissions', [SubmissionController::class, 'index']);
    Route::post('/submissions/{submission}/comments', [CommentController::class, 'store']);

    Route::middleware('role:mahasiswa')->group(function () {
        Route::post('/theses', [ThesisController::class, 'store']);
        Route::put('/theses/{thesis}', [ThesisController::class, 'update']);
        Route::delete('/theses/{thesis}', [ThesisController::class, 'destroy']);
        Route::post('/stages/{stage}/submissions', [SubmissionController::class, 'store']);
        Route::post('/submissions/bulk-delete', [SubmissionController::class, 'bulkDestroy']);
        Route::delete('/submissions/{submission}', [SubmissionController::class, 'destroy']);
    });

    Route::middleware('role:dosen')->group(function () {
        Route::patch('/submissions/{submission}/status', [SubmissionController::class, 'updateStatus']);
    });

    Route::get('/stages/{stage}', [StageController::class, 'show']);
});