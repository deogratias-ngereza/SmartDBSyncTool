<?php

namespace App\Services;

use App\Jobs\ExecuteSyncTaskJob;
use App\Models\DatabaseConnection;
use App\Models\Project;
use App\Models\SyncTask;
use Illuminate\Bus\Batch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncOrchestrator
{
    /**
     * Chunk size for batching database operations.
     */
    protected int $chunkSize = 500;

    /**
     * The filter engine service.
     */
    protected FilterEngine $filterEngine;

    /**
     * Create a new orchestrator instance.
     */
    public function __construct(FilterEngine $filterEngine)
    {
        $this->filterEngine = $filterEngine;
    }

    /**
     * Execute a sync task with filtering and batching.
     */
    public function executeSyncTask(SyncTask $syncTask): Batch
    {
        // Update task status to running
        $syncTask->update([
            'status' => 'running',
            'started_at' => now(),
            'error_message' => null,
        ]);

        try {
            // Get target databases after applying filters
            $targetDatabases = $this->getTargetDatabases($syncTask);

            if ($targetDatabases->isEmpty()) {
                $syncTask->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'total_targets' => 0,
                ]);

                throw new \Exception('No target databases found matching the filters');
            }

            // Update total targets
            $syncTask->update([
                'total_targets' => $targetDatabases->count(),
            ]);

            // Create and dispatch batch
            $batch = $this->dispatchBatch($syncTask, $targetDatabases);

            // Store batch ID in sync task
            $syncTask->update([
                'batch_id' => $batch->id,
            ]);

            Log::info('Sync task dispatched', [
                'sync_task_id' => $syncTask->id,
                'total_targets' => $targetDatabases->count(),
                'batch_id' => $batch->id,
            ]);

            return $batch;

        } catch (Throwable $e) {
            $syncTask->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Get target databases after applying filters.
     */
    public function getTargetDatabases(SyncTask $syncTask): Collection
    {
        // Load the project
        $project = Project::findOrFail($syncTask->project_id);

        // Get all database connections for the project
        $allDatabases = DatabaseConnection::where('project_id', $project->id)
            ->where('is_active', true)
            ->get();

        // If no filters defined, return all databases (except controller)
        $filters = $syncTask->filters;
        if ($filters->isEmpty()) {
            return $allDatabases->where('is_controller', false);
        }

        // Apply all filters sequentially (AND logic)
        $filteredDatabases = $allDatabases;
        foreach ($filters as $filter) {
            $filteredDatabases = $this->filterEngine->applyFilter(
                $filteredDatabases,
                $filter->filter_type,
                $filter->filter_value
            );
        }

        // Exclude controller database from targets
        return $filteredDatabases->where('is_controller', false);
    }

    /**
     * Create and dispatch batch jobs.
     */
    protected function dispatchBatch(SyncTask $syncTask, Collection $targetDatabases): Batch
    {
        // Create jobs for each database
        $jobs = $targetDatabases->map(function (DatabaseConnection $db) use ($syncTask) {
            return new ExecuteSyncTaskJob($db->id, $syncTask->id);
        })->all();

        // Dispatch batch with callbacks
        return Bus::batch($jobs)
            ->name("Sync Task: {$syncTask->name}")
            ->then(function (Batch $batch) use ($syncTask) {
                // Batch completed successfully
                $this->onBatchCompleted($syncTask, $batch);
            })
            ->catch(function (Batch $batch, Throwable $e) use ($syncTask) {
                // Batch failed
                $this->onBatchFailed($syncTask, $batch, $e);
            })
            ->finally(function (Batch $batch) use ($syncTask) {
                // Always executed
                $this->onBatchFinished($syncTask, $batch);
            })
            ->allowFailures()
            ->dispatch();
    }

    /**
     * Handle batch completion.
     */
    protected function onBatchCompleted(SyncTask $syncTask, Batch $batch): void
    {
        Log::info('Sync task batch completed', [
            'sync_task_id' => $syncTask->id,
            'batch_id' => $batch->id,
            'total_jobs' => $batch->totalJobs,
            'failed_jobs' => $batch->failedJobs,
        ]);

        // Determine final status based on failure count
        $status = $batch->failedJobs > 0 ? 'completed_with_errors' : 'completed';

        $syncTask->update([
            'status' => $status,
            'completed_at' => now(),
        ]);
    }

    /**
     * Handle batch failure.
     */
    protected function onBatchFailed(SyncTask $syncTask, Batch $batch, Throwable $e): void
    {
        Log::error('Sync task batch failed', [
            'sync_task_id' => $syncTask->id,
            'batch_id' => $batch->id,
            'error' => $e->getMessage(),
        ]);

        $syncTask->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'completed_at' => now(),
        ]);
    }

    /**
     * Handle batch finish (always executed).
     */
    protected function onBatchFinished(SyncTask $syncTask, Batch $batch): void
    {
        // Reload to get latest counts
        $syncTask->refresh();

        Log::info('Sync task batch finished', [
            'sync_task_id' => $syncTask->id,
            'batch_id' => $batch->id,
            'status' => $syncTask->status,
            'success_count' => $syncTask->success_count,
            'failure_count' => $syncTask->failure_count,
            'total_targets' => $syncTask->total_targets,
        ]);
    }

    /**
     * Get batch progress for a sync task.
     */
    public function getBatchProgress(SyncTask $syncTask): ?array
    {
        if (!$syncTask->batch_id) {
            return null;
        }

        try {
            $batch = Bus::findBatch($syncTask->batch_id);

            if (!$batch) {
                return null;
            }

            return [
                'batch_id' => $batch->id,
                'name' => $batch->name,
                'total_jobs' => $batch->totalJobs,
                'pending_jobs' => $batch->pendingJobs,
                'processed_jobs' => $batch->processedJobs(),
                'failed_jobs' => $batch->failedJobs,
                'progress' => $batch->progress(),
                'finished' => $batch->finished(),
                'cancelled' => $batch->cancelled(),
                'created_at' => $batch->createdAt,
                'finished_at' => $batch->finishedAt,
            ];
        } catch (Throwable $e) {
            Log::error('Failed to get batch progress', [
                'sync_task_id' => $syncTask->id,
                'batch_id' => $syncTask->batch_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Cancel a running batch.
     */
    public function cancelBatch(SyncTask $syncTask): bool
    {
        if (!$syncTask->batch_id) {
            return false;
        }

        try {
            $batch = Bus::findBatch($syncTask->batch_id);

            if (!$batch) {
                return false;
            }

            $batch->cancel();

            $syncTask->update([
                'status' => 'cancelled',
                'error_message' => 'Manually cancelled by user',
                'completed_at' => now(),
            ]);

            Log::info('Sync task batch cancelled', [
                'sync_task_id' => $syncTask->id,
                'batch_id' => $batch->id,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('Failed to cancel batch', [
                'sync_task_id' => $syncTask->id,
                'batch_id' => $syncTask->batch_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Set custom chunk size.
     */
    public function setChunkSize(int $chunkSize): self
    {
        $this->chunkSize = $chunkSize;
        return $this;
    }

    /**
     * Execute rollback for a sync task.
     */
    public function executeRollback(SyncTask $syncTask): Batch
    {
        if (!$syncTask->down_query) {
            throw new \Exception('No rollback query defined for this sync task');
        }

        // Create a new sync task for rollback
        $rollbackTask = SyncTask::create([
            'project_id' => $syncTask->project_id,
            'name' => "Rollback: {$syncTask->name}",
            'description' => "Rollback for sync task: {$syncTask->id}",
            'task_type' => 'rollback',
            'up_query' => $syncTask->down_query, // Use DOWN query as UP
            'down_query' => null,
            'status' => 'pending',
            'is_rollback' => true,
            'parent_task_id' => $syncTask->id,
        ]);

        // Copy filters from original task
        foreach ($syncTask->filters as $filter) {
            $rollbackTask->filters()->create([
                'filter_type' => $filter->filter_type,
                'filter_value' => $filter->filter_value,
            ]);
        }

        // Execute the rollback task
        return $this->executeSyncTask($rollbackTask);
    }
}
