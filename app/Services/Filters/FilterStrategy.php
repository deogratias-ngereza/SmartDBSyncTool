<?php

namespace App\Services\Filters;

use App\Models\Project;
use Illuminate\Support\Collection;

interface FilterStrategy
{
    /**
     * Apply the filter and return collection of database connection IDs.
     * 
     * @param Project $project
     * @param array $filterData
     * @return Collection
     */
    public function apply(Project $project, array $filterData): Collection;

    /**
     * Validate the filter data.
     * 
     * @param array $filterData
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate(array $filterData): array;
}
