<?php

namespace App\Models;

use App\Utilities\HelperUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskFilter extends Model
{
    use SoftDeletes;

    protected $table = 'task_filters';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    const TYPE_ID_LIST = 'id_list';
    const TYPE_EXCLUSION = 'exclusion';
    const TYPE_PROPERTY = 'property';
    const TYPE_SQL = 'sql';

    protected $fillable = [
        'id',
        'sync_task_id',
        'filter_type',
        'filter_data',
    ];

    protected $casts = [
        'filter_data' => 'array',
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
     * Get the sync task that owns the filter.
     */
    public function syncTask(): BelongsTo
    {
        return $this->belongsTo(SyncTask::class, 'sync_task_id');
    }

    /**
     * Scope a query to only include filters of specific type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('filter_type', $type);
    }

    /**
     * Check if filter is ID list type.
     */
    public function isIdList(): bool
    {
        return $this->filter_type === self::TYPE_ID_LIST;
    }

    /**
     * Check if filter is exclusion type.
     */
    public function isExclusion(): bool
    {
        return $this->filter_type === self::TYPE_EXCLUSION;
    }

    /**
     * Check if filter is property type.
     */
    public function isProperty(): bool
    {
        return $this->filter_type === self::TYPE_PROPERTY;
    }

    /**
     * Check if filter is SQL type.
     */
    public function isSql(): bool
    {
        return $this->filter_type === self::TYPE_SQL;
    }
}
