<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskFilterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sync_task_id' => $this->sync_task_id,
            'filter_type' => $this->filter_type,
            'filter_value' => $this->filter_value,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
