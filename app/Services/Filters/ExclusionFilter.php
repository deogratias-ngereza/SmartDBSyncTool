<?php

namespace App\Services\Filters;

use App\Models\Project;
use App\Models\DatabaseConnection;
use Illuminate\Support\Collection;

class ExclusionFilter implements FilterStrategy
{
    /**
     * Apply exclusion filter - run on all databases except specified IDs.
     * 
     * Filter data format: ['exclude_ids' => ['id1', 'id2']]
     * 
     * @param Project $project
     * @param array $filterData
     * @return Collection
     */
    public function apply(Project $project, array $filterData): Collection
    {
        $excludeIds = $filterData['exclude_ids'] ?? [];
        
        return DatabaseConnection::where('project_id', $project->id)
            ->whereNotIn('id', $excludeIds)
            ->where('is_controller', false) // Exclude controller by default
            ->pluck('id');
    }

    /**
     * Validate exclusion filter data.
     * 
     * @param array $filterData
     * @return array
     */
    public function validate(array $filterData): array
    {
        $errors = [];

        if (!isset($filterData['exclude_ids'])) {
            $errors[] = 'Exclude IDs array is required';
        } elseif (!is_array($filterData['exclude_ids'])) {
            $errors[] = 'Exclude IDs must be an array';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
