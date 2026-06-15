<?php

namespace App\Modules\Lookup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LookupCategory extends Model
{
    use SoftDeletes;

    protected $table = 'lookup_categories';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_system',
        'is_tenant_editable',
        'display_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_tenant_editable' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(LookupValue::class, 'lookup_category_id');
    }
}