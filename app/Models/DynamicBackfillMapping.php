<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicBackfillMapping extends Model
{
    protected $fillable = [
        'module_code',
        'source_table',
        'source_column',
        'source_value',
        'target_table',
        'target_column',
        'target_id',
        'target_label',
        'is_approved',
        'status_code',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];
}