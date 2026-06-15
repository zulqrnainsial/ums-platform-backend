<?php

namespace App\Modules\Lookup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LookupValue extends Model
{
    use SoftDeletes;
    protected $table = 'lookup_values';
    protected $fillable = [
        'tenant_id',
        'lookup_category_id',
        'parent_id',
        'code',
        'name',
        'short_name',
        'extra_json',
        'display_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'extra_json' => 'array',
            'display_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LookupCategory::class, 'lookup_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(LookupValue::class, 'parent_id');
    }
}