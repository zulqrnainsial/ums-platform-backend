<?php

namespace App\Modules\Academic\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class Faculty extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'short_name',
        'description',
        'established_date',
        'head_employee_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'established_date' => 'date',
        ];
    }

    public function institutes(): HasMany
    {
        return $this->hasMany(Institute::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }
}