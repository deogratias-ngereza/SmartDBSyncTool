<?php

namespace App\Services\Filters;

use App\Models\Project;
use App\Models\DatabaseConnection;
use Illuminate\Support\Collection;

class PropertyFilter implements FilterStrategy
{
    /**
     * Apply property filter - filter by database properties (driver, metadata fields).
     * 
     * Filter data format: 
     * ['driver' => 'mysql'] or
     * ['metadata_key' => 'region', 'metadata_value' => 'us-east']
     * 
     * @param Project $project
     * @param array $filterData
     * @return Collection
     */
    public function apply(Project $project, array $filterData): Collection
    {
        $query = DatabaseConnection::where('project_id', $project->id)
            ->where('is_controller', false);

        // Filter by driver
        if (isset($filterData['driver'])) {
            $query->where('driver', $filterData['driver']);
        }

        // Filter by metadata JSON field
        if (isset($filterData['metadata_key']) && isset($filterData['metadata_value'])) {
            $key = $filterData['metadata_key'];
            $value = $filterData['metadata_value'];
            $query->whereRaw("JSON_EXTRACT(metadata, '$.{$key}') = ?", [$value]);
        }

        // Filter by is_controller
        if (isset($filterData['is_controller'])) {
            $query->where('is_controller', $filterData['is_controller']);
        }

        return $query->pluck('id');
    }

    /**
     * Validate property filter data.
     * 
     * @param array $filterData
     * @return array
     */
    public function validate(array $filterData): array
    {
        $errors = [];

        // Must have at least one filter criterion
        $hasDriver = isset($filterData['driver']);
        $hasMetadata = isset($filterData['metadata_key']) && isset($filterData['metadata_value']);
        $hasControllerFlag = isset($filterData['is_controller']);

        if (!$hasDriver && !$hasMetadata && !$hasControllerFlag) {
            $errors[] = 'At least one property filter criterion is required (driver, metadata, or is_controller)';
        }

        // Validate driver if provided
        if ($hasDriver) {
            $supportedDrivers = ['mysql', 'mariadb', 'pgsql'];
            if (!in_array($filterData['driver'], $supportedDrivers)) {
                $errors[] = 'Driver must be one of: ' . implode(', ', $supportedDrivers);
            }
        }

        // Validate metadata filter
        if (isset($filterData['metadata_key']) && !isset($filterData['metadata_value'])) {
            $errors[] = 'metadata_value is required when metadata_key is provided';
        }

        if (!isset($filterData['metadata_key']) && isset($filterData['metadata_value'])) {
            $errors[] = 'metadata_key is required when metadata_value is provided';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
