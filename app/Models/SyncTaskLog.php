<?php

namespace App\Models;

use App\Utilities\HelperUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncTaskLog extends Model
{
    use SoftDeletes;

    protected $table = 'sync_task_logs';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL = 'fail';

    protected $fillable = [
        'id',
        'sync_task_id',
        'db_connection_id',
        'status',
        'error_message',
        'duration_ms',
        'batch_id',
        'executed_at',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'executed_at' => 'datetime',
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
    }

    /**
     * Get the sync task that owns the log.
     */
    public function syncTask(): BelongsTo
    {
        return $this->belongsTo(SyncTask::class, 'sync_task_id');
    }

    /**
     * Get the database connection that this log belongs to.
     */
    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(DatabaseConnection::class, 'db_connection_id');
    }

    /**
     * Check if the log represents a successful execution.
     */
    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if the log represents a failed execution.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAIL;
    }

    /**
     * Scope a query to only include successful logs.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope a query to only include failed logs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAIL);
    }

    /**
     * Scope a query to filter by batch ID.
     */
    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Get duration in seconds (formatted).
     */
    public function getDurationInSeconds(): float
    {
        return $this->duration_ms / 1000;
    }
}
