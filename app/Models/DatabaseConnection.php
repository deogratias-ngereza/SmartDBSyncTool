<?php

namespace App\Models;

use App\Utilities\HelperUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatabaseConnection extends Model
{
    use SoftDeletes;

    protected $table = 'db_connections';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'project_id',
        'name',
        'driver',
        'host',
        'port',
        'database',
        'username',
        'encrypted_password',
        'is_controller',
        'current_version_id',
        'metadata',
    ];

    protected $casts = [
        'port' => 'integer',
        'is_controller' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'encrypted_password',
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
     * Get the project that owns the database connection.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get all sync task logs for this database connection.
     */
    public function syncTaskLogs(): HasMany
    {
        return $this->hasMany(SyncTaskLog::class, 'db_connection_id');
    }

    /**
     * Get the decrypted password.
     */
    public function getDecryptedPassword(): string
    {
        return HelperUtil::aes_decrypt($this->encrypted_password);
    }

    /**
     * Set encrypted password.
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['encrypted_password'] = HelperUtil::aes_encrypt($value);
    }

    /**
     * Scope a query to only include controller databases.
     */
    public function scopeController($query)
    {
        return $query->where('is_controller', true);
    }

    /**
     * Scope a query to only include target databases (non-controller).
     */
    public function scopeTarget($query)
    {
        return $query->where('is_controller', false);
    }

    /**
     * Scope a query to filter by driver.
     */
    public function scopeByDriver($query, $driver)
    {
        return $query->where('driver', $driver);
    }

    /**
     * Get connection configuration array for Laravel DB.
     */
    public function getConnectionConfig(): array
    {
        return [
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->getDecryptedPassword(),
            'charset' => 'utf8mb4',
            'collation' => $this->driver === 'pgsql' ? null : 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
            'options' => [
                \PDO::ATTR_TIMEOUT => 10, // 10 second connection timeout
            ],
        ];
    }
}
