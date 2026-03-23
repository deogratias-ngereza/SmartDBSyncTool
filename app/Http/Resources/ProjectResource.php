<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_id' => $this->entity_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'database_connections_count' => $this->whenLoaded('databaseConnections', fn() => $this->databaseConnections->count()),
            'sync_tasks_count' => $this->whenLoaded('syncTasks', fn() => $this->syncTasks->count()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
