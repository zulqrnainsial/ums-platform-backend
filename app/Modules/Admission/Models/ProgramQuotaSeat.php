<?php

namespace App\Modules\Admission\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramQuotaSeat extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'program_quota_seats';

    protected $fillable = [
        'tenant_id',
        'offered_program_id',
        'quota_type_id',
        'quota_code',
        'quota_name',
        'allocated_seats',
        'filled_seats',
        'available_seats',
        'application_fee',
        'admission_fee',
        'is_default',
        'is_active',
        'display_order',
        'eligibility_notes',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'allocated_seats' => 'integer',
            'filled_seats' => 'integer',
            'available_seats' => 'integer',
            'application_fee' => 'decimal:2',
            'admission_fee' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProgramQuotaSeat $seat) {
            $seat->available_seats = max(
                0,
                (int) $seat->allocated_seats - (int) $seat->filled_seats
            );
        });
    }
    
    public function offeredProgram(): BelongsTo
    {
        return $this->belongsTo(OfferedProgram::class);
    }

    public function quotaType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'quota_type_id');
    }

    public function eligibilityRules(): HasMany
    {
        return $this->hasMany(ProgramEligibilityRule::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ApplicantProgramApplication::class);
    }
}