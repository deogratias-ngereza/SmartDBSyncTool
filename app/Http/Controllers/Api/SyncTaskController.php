<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SyncTaskResource;
use App\Models\Project;
use App\Models\SyncTask;
use App\Services\SyncOrchestrator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SyncTaskController extends Controller
{
    public function __construct(
        protected SyncOrchestrator $orchestrator
    ) {}

    /**
     * Display a listing of sync tasks.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = SyncTask::query()
            ->whereHas('project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            });

        // Filter by project
        if ($request->has('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by task type
        if ($request->has('task_type')) {
            $query->where('task_type', $request->input('task_type'));
        }

        $tasks = $query->with(['project'])
            ->latest()
            ->paginate($request->input('per_page', 15));

        return SyncTaskResource::collection($tasks);
    }

    /**
     * Store a newly created sync task.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|string|exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'task_type' => 'required|string|in:raw_query,migration,controller_sync,full_sync',
            'up_query' => 'required|string',
            'down_query' => 'nullable|string',
            'filters' => 'nullable|array',
            'filters.*.filter_type' => 'required|string|in:id_list,exclusion,property,all_targets',
            'filters.*.filter_value' => 'required|string',
        ]);

        // Verify project belongs to user's entity
        $project = Project::where('entity_id', $request->user()->entity_id)
            ->findOrFail($validated['project_id']);

        // Create sync task
        $task = SyncTask::create([
            'project_id' => $validated['project_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'task_type' => $validated['task_type'],
            'up_query' => $validated['up_query'],
            'down_query' => $validated['down_query'] ?? null,
            'status' => 'pending',
        ]);

        // Add filters if provided
        if (isset($validated['filters'])) {
            foreach ($validated['filters'] as $filter) {
                $task->filters()->create([
                    'filter_type' => $filter['filter_type'],
                    'filter_value' => $filter['filter_value'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Sync task created successfully',
            'data' => new SyncTaskResource($task->load('filters')),
        ], 201);
    }

    /**
     * Display the specified sync task.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $task = SyncTask::whereHas('project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            })
            ->with(['project', 'filters', 'syncTaskLogs'])
            ->findOrFail($id);

        return response()->json([
            'data' => new SyncTaskResource($task),
        ]);
    }

    /**
     * Execute the sync task.
     */
    public function execute(Request $request, string $id): JsonResponse
    {
        $task = SyncTask::whereHas('project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            })
            ->findOrFail($id);

        // Check if task is already running
        if ($task->status === 'running') {
            return response()->json([
                'message' => 'Task is already running',
            ], 400);
        }

        try {
            $batch = $this->orchestrator->executeSyncTask($task);

            return response()->json([
                'message' => 'Sync task dispatched successfully',
                'data' => [
                    'batch_id' => $batch->id,
                    'total_jobs' => $batch->totalJobs,
                    'task' => new SyncTaskResource($task->fresh()),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to execute sync task',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get batch progress for a sync task.
     */
    public function progress(Request $request, string $id): JsonResponse
    {
        $task = SyncTask::whereHas('project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            })
            ->findOrFail($id);

        $batchProgress = $this->orchestrator->getBatchProgress($task);

        if (!$batchProgress) {
            return response()->json([
                'message' => 'No batch information available',
                'data' => [
                    'task_status' => $task->status,
                    'success_count' => $task->success_count,
                    'failure_count' => $task->failure_count,
                    'total_targets' => $task->total_targets,
                ],
            ]);
        }

        return response()->json([
            'data' => array_merge($batchProgress, [
                'task_status' => $task->status,
                'success_count' => $task->success_count,
                'failure_count' => $task->failure_count,
                'total_targets' => $task->total_targets,
            ]),
        ]);
    }

    /**
     * Cancel a running sync task.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $task = SyncTask::whereHas('project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            })
            ->findOrFail($id);

        if ($task->status !== 'running') {
            return response()->json([
                'message' => 'Task is not running',
            ], 400);
        }

        $cancelled = $this->orchestrator->cancelBatch($task);

        if ($cancelled) {
            return response()->json([
                'message' => 'Sync task cancelled successfully',
                'data' => new SyncTaskResource($task->fresh()),
            ]);
        } else {
            return response()->json([
                'message' => 'Failed to cancel sync task',
            ], 500);
        }
    }

    /**
     * Execute rollback for a sync task.
     */
    public function rollback(Request $request, string $id): JsonResponse
    {
        $task = SyncTask::whereHas('project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            })
            ->findOrFail($id);

        if (!$task->down_query) {
            return response()->json([
                'message' => 'No rollback query defined for this task',
            ], 400);
        }

        try {
            $batch = $this->orchestrator->executeRollback($task);

            return response()->json([
                'message' => 'Rollback task dispatched successfully',
                'data' => [
                    'batch_id' => $batch->id,
                    'total_jobs' => $batch->totalJobs,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to execute rollback',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete the specified sync task.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $task = SyncTask::whereHas('project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            })
            ->findOrFail($id);

        // Prevent deletion of running tasks
        if ($task->status === 'running') {
            return response()->json([
                'message' => 'Cannot delete a running task. Please cancel it first.',
            ], 400);
        }

        $task->delete();

        return response()->json([
            'message' => 'Sync task deleted successfully',
        ]);
    }
}
