<?php

namespace App\Models;

use App\Utilities\HelperUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncTask extends Model
{
    use SoftDeletes;

    protected $table = 'sync_tasks';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PARTIAL_FAILURE = 'partial_failure';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_RAW_QUERY = 'raw_query';
    const TYPE_MIGRATION = 'migration';
    const TYPE_CONTROLLER_SYNC = 'controller_sync';
    const TYPE_FULL_SYNC = 'full_sync';

    protected $fillable = [
        'id',
        'project_id',
        'name',
        'description',
        'task_type',
        'up_query',
        'down_query',
        'execution_mode',
        'total_targets',
        'success_count',
        'failure_count',
        'status',
        'batch_id',
        'error_message',
        'is_rollback',
        'parent_task_id',
        'started_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'total_targets' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model and generate ID on creating.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = HelperUtil::generateRandomString(20);
            }
        });

        // Auto soft-delete related records when task is soft-deleted
        static::deleting(function ($task) {
            if ($task->isForceDeleting()) {
                return;
            }
            
            // Soft delete all task filters
            $task->taskFilters()->delete();
            
            // Soft delete all task logs
            $task->syncTaskLogs()->delete();
        });
    }

    /**
     * Get the project that owns the sync task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get all task filters for this sync task.
     */
    public function taskFilters(): HasMany
    {
        return $this->hasMany(TaskFilter::class, 'sync_task_id');
    }

    /**
     * Alias for taskFilters() - used by SyncOrchestrator.
     */
    public function filters(): HasMany
    {
        return $this->taskFilters();
    }

    /**
     * Get all sync task logs for this task.
     */
    public function syncTaskLogs(): HasMany
    {
        return $this->hasMany(SyncTaskLog::class, 'sync_task_id');
    }

    /**
     * Get failed logs only.
     */
    public function failedLogs()
    {
        return $this->syncTaskLogs()->where('status', 'fail');
    }

    /**
     * Get successful logs only.
     */
    public function successfulLogs()
    {
        return $this->syncTaskLogs()->where('status', 'success');
    }

    /**
     * Calculate failure rate percentage.
     */
    public function getFailureRateAttribute(): float
    {
        if ($this->total_targets === 0) {
            return 0;
        }
        return ($this->failure_count / $this->total_targets) * 100;
    }

    /**
     * Calculate success rate percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_targets === 0) {
            return 0;
        }
        return ($this->success_count / $this->total_targets) * 100;
    }

    /**
     * Get progress percentage (completed / total).
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_targets === 0) {
            return 0;
        }
        $completed = $this->success_count + $this->failure_count;
        return ($completed / $this->total_targets) * 100;
    }

    /**
     * Check if task is complete.
     */
    public function isComplete(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_PARTIAL_FAILURE, self::STATUS_CANCELLED]);
    }

    /**
     * Scope a query to only include tasks with specific status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include tasks of specific type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('task_type', $type);
    }
}
