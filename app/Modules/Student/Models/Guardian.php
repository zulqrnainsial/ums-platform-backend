<?php

namespace App\Modules\Student\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardian extends Model
{
    use BelongsToTenant;
    use SoftDeletes;
    protected $table = 'guardians';
    protected $fillable = [
        'tenant_id',
        'name',
        'cnic',
        'phone',
        'alternate_phone',
        'email',
        'occupation',
        'monthly_income',
        'address',
        'country_id',
        'province_id',
        'city_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'monthly_income' => 'decimal:2',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'country_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'province_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'city_id');
    }

    public function studentGuardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class);
    }
}