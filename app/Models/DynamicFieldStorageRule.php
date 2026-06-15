<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicFieldStorageRule extends Model
{
    protected $fillable = [
        'module_code',
        'entity_key',
        'field_name',
        'field_label',
        'storage_mode',
        'option_source_key',
        'value_category',
        'is_business_critical',
        'is_required_for_rules',
        'is_system_locked',
        'status_code',
        'notes',
    ];

    protected $casts = [
        'is_business_critical' => 'boolean',
        'is_required_for_rules' => 'boolean',
        'is_system_locked' => 'boolean',
    ];
}