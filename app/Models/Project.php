<?php

namespace App\Models;

use App\Utilities\HelperUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use SoftDeletes;

    protected $table = 'projects';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'entity_id',
        'name',
        'slug',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
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

        // Auto soft-delete related records when project is soft-deleted
        static::deleting(function ($project) {
            if ($project->isForceDeleting()) {
                return;
            }
            
            // Soft delete all database connections
            $project->databaseConnections()->delete();
            
            // Soft delete all sync tasks
            $project->syncTasks()->delete();
        });
    }

    /**
     * Get the entity that owns the project.
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'entity_id');
    }

    /**
     * Get all database connections for the project.
     */
    public function databaseConnections(): HasMany
    {
        return $this->hasMany(DatabaseConnection::class, 'project_id');
    }

    /**
     * Get all sync tasks for the project.
     */
    public function syncTasks(): HasMany
    {
        return $this->hasMany(SyncTask::class, 'project_id');
    }

    /**
     * Get the controller database for this project.
     */
    public function controllerDatabase()
    {
        return $this->databaseConnections()->where('is_controller', true)->first();
    }

    /**
     * Get all target databases (non-controller) for this project.
     */
    public function targetDatabases()
    {
        return $this->databaseConnections()->where('is_controller', false)->get();
    }
}
