<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DatabaseConnectionController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SyncTaskController;
use App\Http\Controllers\Api\SyncTaskLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'Multi-DB Sync Tool API',
    ]);
});

// Authentication routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Authenticated routes - require Sanctum token
Route::middleware(['auth:sanctum'])->group(function () {
    
    // User info
    Route::get('/user', function (Request $request) {
        return response()->json([
            'data' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'entity_id' => $request->user()->entity_id,
            ],
        ]);
    });

    // Authentication management
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/auth/tokens', [AuthController::class, 'tokens']);
    Route::delete('/auth/tokens/{tokenId}', [AuthController::class, 'revokeToken']);

    // Projects
    Route::apiResource('projects', ProjectController::class);
    Route::get('/projects/{id}/statistics', [ProjectController::class, 'statistics']);

    // Database Connections
    Route::apiResource('database-connections', DatabaseConnectionController::class);
    Route::post('/database-connections/{id}/test', [DatabaseConnectionController::class, 'test']);

    // Sync Tasks
    Route::apiResource('sync-tasks', SyncTaskController::class)->except(['update']);
    Route::post('/sync-tasks/{id}/execute', [SyncTaskController::class, 'execute']);
    Route::get('/sync-tasks/{id}/progress', [SyncTaskController::class, 'progress']);
    Route::post('/sync-tasks/{id}/cancel', [SyncTaskController::class, 'cancel']);
    Route::post('/sync-tasks/{id}/rollback', [SyncTaskController::class, 'rollback']);

    // Sync Task Logs
    Route::get('/sync-task-logs', [SyncTaskLogController::class, 'index']);
    Route::get('/sync-task-logs/failed', [SyncTaskLogController::class, 'failed']);
    Route::get('/sync-task-logs/export', [SyncTaskLogController::class, 'export']);
    Route::get('/sync-task-logs/statistics', [SyncTaskLogController::class, 'statistics']);
});

// Rate-limited routes (stricter limits for resource-intensive operations)
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    // These endpoints can be resource-intensive
    Route::post('/sync-tasks/{id}/execute', [SyncTaskController::class, 'execute']);
    Route::post('/database-connections/{id}/test', [DatabaseConnectionController::class, 'test']);
});
