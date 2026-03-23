<?php

namespace App\Jobs;

use App\Models\DatabaseConnection;
use App\Models\SyncTask;
use App\Models\SyncTaskLog;
use App\Services\ConnectionManager;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExecuteSyncTaskJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * The database connection ID to sync.
     */
    public string $databaseConnectionId;

    /**
     * The sync task ID.
     */
    public string $syncTaskId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $databaseConnectionId, string $syncTaskId)
    {
        $this->databaseConnectionId = $databaseConnectionId;
        $this->syncTaskId = $syncTaskId;
    }

    /**
     * Execute the job.
     */
    public function handle(ConnectionManager $connectionManager): void
    {
        // Skip if batch was cancelled
        if ($this->batch()?->cancelled()) {
            return;
        }

        $startTime = microtime(true);
        $connectionName = null;

        try {
            // Load models
            $dbConnection = DatabaseConnection::findOrFail($this->databaseConnectionId);
            $syncTask = SyncTask::findOrFail($this->syncTaskId);

            // Create database connection
            $connectionName = $connectionManager->createConnection($dbConnection);

            // Test connection before executing
            if (!$connectionManager->testConnection($dbConnection)) {
                throw new \Exception('Failed to connect to target database');
            }

            // Execute the sync query within a transaction
            DB::connection($connectionName)->transaction(function () use ($connectionName, $syncTask) {
                // Execute the UP query
                DB::connection($connectionName)->statement($syncTask->up_query);
            });

            // Calculate duration
            $duration = round((microtime(true) - $startTime) * 1000); // in milliseconds

            // Log success
            $this->logSuccess($dbConnection, $syncTask, $duration);

            // Increment success counter on sync task
            $syncTask->increment('success_count');

        } catch (Throwable $e) {
            // Calculate duration
            $duration = round((microtime(true) - $startTime) * 1000);

            // Log failure
            $this->logFailure($e->getMessage(), $duration);

            // Increment failure counter on sync task
            $syncTask = SyncTask::find($this->syncTaskId);
            if ($syncTask) {
                $syncTask->increment('failure_count');
                
                // Check circuit breaker threshold (25%)
                $this->checkCircuitBreaker($syncTask);
            }

            // Re-throw to mark job as failed
            throw $e;

        } finally {
            // Clean up database connection
            if ($connectionName) {
                $connectionManager->removeConnection($connectionName);
            }
        }
    }

    /**
     * Log successful execution.
     */
    protected function logSuccess(DatabaseConnection $dbConnection, SyncTask $syncTask, int $duration): void
    {
        SyncTaskLog::create([
            'sync_task_id' => $syncTask->id,
            'db_connection_id' => $dbConnection->id,
            'status' => 'success',
            'executed_query' => $syncTask->up_query,
            'error_message' => null,
            'duration_ms' => $duration,
            'executed_at' => now(),
        ]);
    }

    /**
     * Log failed execution.
     */
    protected function logFailure(string $errorMessage, int $duration): void
    {
        try {
            $syncTask = SyncTask::find($this->syncTaskId);
            
            SyncTaskLog::create([
                'sync_task_id' => $this->syncTaskId,
                'db_connection_id' => $this->databaseConnectionId,
                'status' => 'failed',
                'executed_query' => $syncTask?->up_query,
                'error_message' => $errorMessage,
                'duration_ms' => $duration,
                'executed_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log sync task failure', [
                'sync_task_id' => $this->syncTaskId,
                'db_connection_id' => $this->databaseConnectionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if circuit breaker threshold is reached and cancel batch if needed.
     */
    protected function checkCircuitBreaker(SyncTask $syncTask): void
    {
        $totalProcessed = $syncTask->success_count + $syncTask->failure_count;
        
        // Only check after processing at least 20 databases
        if ($totalProcessed < 20) {
            return;
        }

        // Calculate failure rate
        $failureRate = $syncTask->failure_count / $totalProcessed;

        // If failure rate exceeds 25%, cancel the batch
        if ($failureRate > 0.25) {
            Log::warning('Circuit breaker triggered for sync task', [
                'sync_task_id' => $syncTask->id,
                'failure_rate' => round($failureRate * 100, 2) . '%',
                'success_count' => $syncTask->success_count,
                'failure_count' => $syncTask->failure_count,
            ]);

            // Cancel the batch if available
            if ($this->batch()) {
                $this->batch()->cancel();
            }

            // Update sync task status
            $syncTask->update([
                'status' => 'failed',
                'error_message' => 'Circuit breaker triggered: Failure rate exceeded 25%',
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('ExecuteSyncTaskJob failed permanently', [
            'sync_task_id' => $this->syncTaskId,
            'db_connection_id' => $this->databaseConnectionId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
