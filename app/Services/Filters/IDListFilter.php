<?php

namespace App\Services\Filters;

use App\Models\Project;
use App\Models\DatabaseConnection;
use Illuminate\Support\Collection;

class IDListFilter implements FilterStrategy
{
    /**
     * Apply ID list filter - run on specific database IDs only.
     * 
     * Filter data format: ['ids' => ['id1', 'id2', 'id3']]
     * 
     * @param Project $project
     * @param array $filterData
     * @return Collection
     */
    public function apply(Project $project, array $filterData): Collection
    {
        $ids = $filterData['ids'] ?? [];
        
        return DatabaseConnection::where('project_id', $project->id)
            ->whereIn('id', $ids)
            ->where('is_controller', false) // Exclude controller by default
            ->pluck('id');
    }

    /**
     * Validate ID list filter data.
     * 
     * @param array $filterData
     * @return array
     */
    public function validate(array $filterData): array
    {
        $errors = [];

        if (!isset($filterData['ids'])) {
            $errors[] = 'IDs array is required';
        } elseif (!is_array($filterData['ids'])) {
            $errors[] = 'IDs must be an array';
        } elseif (empty($filterData['ids'])) {
            $errors[] = 'At least one database ID is required';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
