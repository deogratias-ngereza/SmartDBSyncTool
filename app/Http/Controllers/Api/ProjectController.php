<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = Project::where('entity_id', $request->user()->entity_id)
            ->with(['databaseConnections'])
            ->latest()
            ->paginate($request->input('per_page', 15));

        return ProjectResource::collection($projects);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['entity_id'] = $request->user()->entity_id;

        $project = Project::create($validated);

        return response()->json([
            'message' => 'Project created successfully',
            'data' => new ProjectResource($project),
        ], 201);
    }

    /**
     * Display the specified project.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $project = Project::where('entity_id', $request->user()->entity_id)
            ->with(['databaseConnections', 'syncTasks'])
            ->findOrFail($id);

        return response()->json([
            'data' => new ProjectResource($project),
        ]);
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $project = Project::where('entity_id', $request->user()->entity_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $project->update($validated);

        return response()->json([
            'message' => 'Project updated successfully',
            'data' => new ProjectResource($project->fresh()),
        ]);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $project = Project::where('entity_id', $request->user()->entity_id)
            ->findOrFail($id);

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }

    /**
     * Get project statistics.
     */
    public function statistics(Request $request, string $id): JsonResponse
    {
        $project = Project::where('entity_id', $request->user()->entity_id)
            ->findOrFail($id);

        $stats = [
            'total_databases' => $project->databaseConnections()->count(),
            'active_databases' => $project->databaseConnections()->where('is_active', true)->count(),
            'controller_database' => $project->databaseConnections()->where('is_controller', true)->first()?->name,
            'total_sync_tasks' => $project->syncTasks()->count(),
            'pending_tasks' => $project->syncTasks()->where('status', 'pending')->count(),
            'running_tasks' => $project->syncTasks()->where('status', 'running')->count(),
            'completed_tasks' => $project->syncTasks()->where('status', 'completed')->count(),
            'failed_tasks' => $project->syncTasks()->where('status', 'failed')->count(),
        ];

        return response()->json([
            'data' => $stats,
        ]);
    }
}
