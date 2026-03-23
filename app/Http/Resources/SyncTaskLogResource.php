<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyncTaskLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sync_task_id' => $this->sync_task_id,
            'db_connection_id' => $this->db_connection_id,
            'status' => $this->status,
            'executed_query' => $this->executed_query,
            'error_message' => $this->error_message,
            'duration_ms' => $this->duration_ms,
            'executed_at' => $this->executed_at?->toISOString(),
            'sync_task' => new SyncTaskResource($this->whenLoaded('syncTask')),
            'database_connection' => new DatabaseConnectionResource($this->whenLoaded('databaseConnection')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
