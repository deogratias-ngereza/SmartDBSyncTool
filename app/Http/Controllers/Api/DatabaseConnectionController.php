<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DatabaseConnectionResource;
use App\Models\DatabaseConnection;
use App\Models\Project;
use App\Services\ConnectionManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DatabaseConnectionController extends Controller
{
    public function __construct(
        protected ConnectionManager $connectionManager
    ) {}

    /**
     * Display a listing of database connections.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = DatabaseConnection::query()
            ->whereHas('project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            });

        // Filter by project if provided
        if ($request->has('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        // Filter by driver
        if ($request->has('driver')) {
            $query->where('driver', $request->input('driver'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $connections = $query->latest()->paginate($request->input('per_page', 15));

        return DatabaseConnectionResource::collection($connections);
    }

    /**
     * Store a newly created database connection.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|string|exists:projects,id',
            'name' => 'required|string|max:255',
            'driver' => 'required|string|in:mysql,mariadb,pgsql,sqlsrv,oracle,sqlite',
            'host' => 'required|string',
            'port' => 'required|integer|min:1|max:65535',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'is_controller' => 'boolean',
            'is_active' => 'boolean',
            'ssl_enabled' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Verify project belongs to user's entity
        $project = Project::where('entity_id', $request->user()->entity_id)
            ->findOrFail($validated['project_id']);

        // Encrypt password
        $validated['password'] = encrypt($validated['password']);

        $connection = DatabaseConnection::create($validated);

        return response()->json([
            'message' => 'Database connection created successfully',
            'data' => new DatabaseConnectionResource($connection),
        ], 201);
    }

    /**
     * Display the specified database connection.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $connection = DatabaseConnection::whereHas('project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            })
            ->with(['project'])
            ->findOrFail($id);

        return response()->json([
            'data' => new DatabaseConnectionResource($connection),
        ]);
    }

    /**
     * Update the specified database connection.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $connection = DatabaseConnection::whereHas('project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            })
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'driver' => 'sometimes|required|string|in:mysql,mariadb,pgsql,sqlsrv,oracle,sqlite',
            'host' => 'sometimes|required|string',
            'port' => 'sometimes|required|integer|min:1|max:65535',
            'database' => 'sometimes|required|string',
            'username' => 'sometimes|required|string',
            'password' => 'sometimes|required|string',
            'is_controller' => 'boolean',
            'is_active' => 'boolean',
            'ssl_enabled' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Encrypt password if provided
        if (isset($validated['password'])) {
            $validated['password'] = encrypt($validated['password']);
        }

        $connection->update($validated);

        return response()->json([
            'message' => 'Database connection updated successfully',
            'data' => new DatabaseConnectionResource($connection->fresh()),
        ]);
    }

    /**
     * Remove the specified database connection.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $connection = DatabaseConnection::whereHas('project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            })
            ->findOrFail($id);

        $connection->delete();

        return response()->json([
            'message' => 'Database connection deleted successfully',
        ]);
    }

    /**
     * Test the database connection.
     */
    public function test(Request $request, string $id): JsonResponse
    {
        $connection = DatabaseConnection::whereHas('project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            })
            ->findOrFail($id);

        $startTime = microtime(true);
        
        try {
            $success = $this->connectionManager->testConnection($connection);
            $duration = round((microtime(true) - $startTime) * 1000);

            if ($success) {
                return response()->json([
                    'message' => 'Connection successful',
                    'data' => [
                        'status' => 'success',
                        'duration_ms' => $duration,
                    ],
                ]);
            } else {
                return response()->json([
                    'message' => 'Connection failed',
                    'data' => [
                        'status' => 'failed',
                        'duration_ms' => $duration,
                    ],
                ], 400);
            }
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000);
            
            return response()->json([
                'message' => 'Connection test failed',
                'data' => [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'duration_ms' => $duration,
                ],
            ], 400);
        }
    }
}
