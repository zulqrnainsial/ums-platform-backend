<?php

namespace App\Modules\Admission\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantApplicationGroup extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'applicant_application_groups';

    protected $fillable = [
        'tenant_id',
        'admission_session_id',
        'applicant_id',
        'application_group_no',
        'status_code',
        'submitted_at',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function admissionSession(): BelongsTo
    {
        return $this->belongsTo(AdmissionSession::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(
            ApplicantProgramApplication::class,
            'applicant_application_group_id'
        );
    }
}
