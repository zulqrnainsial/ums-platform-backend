<?php

namespace App\Modules\Student\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use BelongsToTenant;
    use SoftDeletes;
    protected $table = 'students';
    protected $fillable = [
        'tenant_id',
        'student_no',
        'admission_no',
        'first_name',
        'last_name',
        'full_name',
        'father_name',
        'mother_name',
        'cnic_bform',
        'passport_no',
        'date_of_birth',
        'gender',
        'blood_group_id',
        'religion_id',
        'nationality_id',
        'phone',
        'alternate_phone',
        'email',
        'current_address',
        'permanent_address',
        'country_id',
        'province_id',
        'city_id',
        'photo_path',
        'admission_date',
        'student_status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'admission_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Student $student) {
            $student->full_name = trim(
                $student->first_name . ' ' . ($student->last_name ?? '')
            );
        });
    }

    public function bloodGroup(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'blood_group_id');
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'religion_id');
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'nationality_id');
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

    public function guardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class);
    }

    public function previousEducations(): HasMany
    {
        return $this->hasMany(StudentPreviousEducation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(StudentStatusHistory::class);
    }
}