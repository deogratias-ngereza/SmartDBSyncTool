<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SyncTaskLogResource;
use App\Models\SyncTaskLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Response;

class SyncTaskLogController extends Controller
{
    /**
     * Display a listing of sync task logs.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = SyncTaskLog::query()
            ->whereHas('syncTask.project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            });

        // Filter by sync task
        if ($request->has('sync_task_id')) {
            $query->where('sync_task_id', $request->input('sync_task_id'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by database connection
        if ($request->has('db_connection_id')) {
            $query->where('db_connection_id', $request->input('db_connection_id'));
        }

        // Search in error messages
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('error_message', 'like', "%{$search}%")
                  ->orWhereHas('databaseConnection', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Order
        $orderBy = $request->input('order_by', 'executed_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        $logs = $query->with(['syncTask', 'databaseConnection'])
            ->paginate($request->input('per_page', 50));

        return SyncTaskLogResource::collection($logs);
    }

    /**
     * Display failed logs only.
     */
    public function failed(Request $request): AnonymousResourceCollection
    {
        $query = SyncTaskLog::query()
            ->whereHas('syncTask.project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            })
            ->where('status', 'failed');

        // Filter by sync task
        if ($request->has('sync_task_id')) {
            $query->where('sync_task_id', $request->input('sync_task_id'));
        }

        $logs = $query->with(['syncTask', 'databaseConnection'])
            ->latest('executed_at')
            ->paginate($request->input('per_page', 50));

        return SyncTaskLogResource::collection($logs);
    }

    /**
     * Export logs to CSV.
     */
    public function export(Request $request)
    {
        $query = SyncTaskLog::query()
            ->whereHas('syncTask.project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            });

        // Apply same filters as index
        if ($request->has('sync_task_id')) {
            $query->where('sync_task_id', $request->input('sync_task_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $logs = $query->with(['syncTask', 'databaseConnection'])
            ->orderBy('executed_at', 'desc')
            ->get();

        // Generate CSV
        $csv = "ID,Sync Task,Database,Status,Duration (ms),Error Message,Executed At\n";
        
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $log->id,
                $log->syncTask->name ?? 'N/A',
                $log->databaseConnection->name ?? 'N/A',
                $log->status,
                $log->duration_ms,
                str_replace(["\r", "\n", ","], [' ', ' ', ';'], $log->error_message ?? ''),
                $log->executed_at
            );
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sync-logs-' . date('Y-m-d-His') . '.csv"',
        ]);
    }

    /**
     * Get summary statistics for logs.
     */
    public function statistics(Request $request)
    {
        $query = SyncTaskLog::query()
            ->whereHas('syncTask.project', function ($q) use ($request) {
                $q->where('entity_id', $request->user()->entity_id);
            });

        // Filter by sync task if provided
        if ($request->has('sync_task_id')) {
            $query->where('sync_task_id', $request->input('sync_task_id'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('executed_at', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->where('executed_at', '<=', $request->input('to_date'));
        }

        $stats = [
            'total_executions' => $query->count(),
            'successful_executions' => (clone $query)->where('status', 'success')->count(),
            'failed_executions' => (clone $query)->where('status', 'failed')->count(),
            'average_duration_ms' => round((clone $query)->avg('duration_ms')),
            'total_duration_seconds' => round((clone $query)->sum('duration_ms') / 1000, 2),
        ];

        // Calculate success rate
        if ($stats['total_executions'] > 0) {
            $stats['success_rate'] = round(($stats['successful_executions'] / $stats['total_executions']) * 100, 2);
        } else {
            $stats['success_rate'] = 0;
        }

        return response()->json([
            'data' => $stats,
        ]);
    }
}
