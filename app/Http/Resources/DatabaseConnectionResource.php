<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DatabaseConnectionResource extends JsonResource
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
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            // Password is never returned
            'is_controller' => $this->is_controller,
            'is_active' => $this->is_active,
            'ssl_enabled' => $this->ssl_enabled,
            'description' => $this->description,
            'current_version_id' => $this->current_version_id,
            'last_synced_at' => $this->last_synced_at?->toISOString(),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
