<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyncTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'description' => $this->description,
            'task_type' => $this->task_type,
            'up_query' => $this->up_query,
            'down_query' => $this->down_query,
            'execution_mode' => $this->execution_mode,
            'total_targets' => $this->total_targets,
            'success_count' => $this->success_count,
            'failure_count' => $this->failure_count,
            'status' => $this->status,
            'batch_id' => $this->batch_id,
            'error_message' => $this->error_message,
            'is_rollback' => $this->is_rollback,
            'parent_task_id' => $this->parent_task_id,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'metadata' => $this->metadata,
            'progress_percentage' => $this->progress_percentage,
            'success_rate' => $this->success_rate,
            'failure_rate' => $this->failure_rate,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'filters' => TaskFilterResource::collection($this->whenLoaded('filters')),
            'logs_count' => $this->whenLoaded('syncTaskLogs', fn() => $this->syncTaskLogs->count()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
