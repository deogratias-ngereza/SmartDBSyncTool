<?php

namespace App\Services\Filters;

use App\Models\Project;
use App\Models\DatabaseConnection;
use Illuminate\Support\Collection;

class AllTargetsFilter implements FilterStrategy
{
    /**
     * Apply "all targets" filter - run on all non-controller databases.
     * 
     * Filter data format: [] (no data needed)
     * 
     * @param Project $project
     * @param array $filterData
     * @return Collection
     */
    public function apply(Project $project, array $filterData): Collection
    {
        return DatabaseConnection::where('project_id', $project->id)
            ->where('is_controller', false)
            ->pluck('id');
    }

    /**
     * Validate all targets filter (always valid).
     * 
     * @param array $filterData
     * @return array
     */
    public function validate(array $filterData): array
    {
        return [
            'valid' => true,
            'errors' => [],
        ];
    }
}
