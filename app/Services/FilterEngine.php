<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TaskFilter;
use App\Services\Filters\FilterStrategy;
use App\Services\Filters\IDListFilter;
use App\Services\Filters\ExclusionFilter;
use App\Services\Filters\PropertyFilter;
use App\Services\Filters\AllTargetsFilter;
use Illuminate\Support\Collection;
use Exception;

class FilterEngine
{
    protected array $strategies = [];

    public function __construct()
    {
        // Register available filter strategies
        $this->strategies = [
            TaskFilter::TYPE_ID_LIST => new IDListFilter(),
            TaskFilter::TYPE_EXCLUSION => new ExclusionFilter(),
            TaskFilter::TYPE_PROPERTY => new PropertyFilter(),
            'all_targets' => new AllTargetsFilter(),
        ];
    }

    /**
     * Apply a filter and get the resulting database connection IDs.
     * 
     * @param Project $project
     * @param string $filterType
     * @param array $filterData
     * @return Collection
     * @throws Exception
     */
    public function applyFilter(Project $project, string $filterType, array $filterData): Collection
    {
        $strategy = $this->getStrategy($filterType);
        
        // Validate filter data
        $validation = $strategy->validate($filterData);
        if (!$validation['valid']) {
            throw new Exception('Invalid filter data: ' . implode(', ', $validation['errors']));
        }

        return $strategy->apply($project, $filterData);
    }

    /**
     * Apply multiple filters with AND logic (intersection).
     * 
     * @param Project $project
     * @param array $filters Each filter: ['type' => string, 'data' => array]
     * @return Collection
     */
    public function applyMultipleFilters(Project $project, array $filters): Collection
    {
        if (empty($filters)) {
            // No filters = all targets
            return $this->applyFilter($project, 'all_targets', []);
        }

        $results = null;

        foreach ($filters as $filter) {
            $filterType = $filter['type'] ?? null;
            $filterData = $filter['data'] ?? [];

            if (!$filterType) {
                continue;
            }

            $ids = $this->applyFilter($project, $filterType, $filterData);

            // Intersection of results (AND logic)
            if ($results === null) {
                $results = $ids;
            } else {
                $results = $results->intersect($ids);
            }
        }

        return $results ?? collect([]);
    }

    /**
     * Get a filter strategy by type.
     * 
     * @param string $filterType
     * @return FilterStrategy
     * @throws Exception
     */
    protected function getStrategy(string $filterType): FilterStrategy
    {
        if (!isset($this->strategies[$filterType])) {
            throw new Exception("Unknown filter type: {$filterType}");
        }

        return $this->strategies[$filterType];
    }

    /**
     * Validate filter data without applying it.
     * 
     * @param string $filterType
     * @param array $filterData
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateFilter(string $filterType, array $filterData): array
    {
        try {
            $strategy = $this->getStrategy($filterType);
            return $strategy->validate($filterData);
        } catch (Exception $e) {
            return [
                'valid' => false,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Get available filter types.
     * 
     * @return array
     */
    public function getAvailableFilterTypes(): array
    {
        return array_keys($this->strategies);
    }

    /**
     * Register a custom filter strategy.
     * 
     * @param string $type
     * @param FilterStrategy $strategy
     * @return void
     */
    public function registerStrategy(string $type, FilterStrategy $strategy): void
    {
        $this->strategies[$type] = $strategy;
    }
}
