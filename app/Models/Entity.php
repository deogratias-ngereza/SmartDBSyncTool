<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Entity extends Model
{
    // The table associated with the model.
    protected $table = 'entities';

    // The primary key associated with the table.
    protected $primaryKey = 'id';

    // If the primary key is not an incrementing integer.
    public $incrementing = false;

    public $timestamps = false;

    // Indicates if the IDs are auto-incrementing.
    protected $keyType = 'string';

    // The attributes that are mass assignable.
    protected $fillable = [
        'id',
        'code',
        'name',
        'full_name',
        'first_name',
        'last_name',
        'phone1',
        'phone2',
        'email1',
        'email2',
        'tin',
        'vrn',
        'region',
        'address',
        'entity_type',
        'status',
        'created_date',
        'created_by',
        'updated_date',
        'updated_by',
        'deleted_date',
        'deleted_by',
        'deleted',
        'deleted_at',
        'default_init_module',
        'linked_uvdesk_id',
        'is_blocked',
        'block_reason',
        'subscription_package_code',
        'business_code',
        'subscription_exp_date',
        'subscription_amt',
        'subscription_currency',
        'primary_color',
        'date_format',
        'time_format',
        'timezone',
        'auto_detect_timezone',
        'locale',
        'currency_id'
    ];

    // Timestamps

    // Specify the created_at and updated_at columns if their names differ from the defaults.
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}

?>