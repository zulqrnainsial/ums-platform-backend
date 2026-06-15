<?php

namespace App\Modules\Admission\Models;

use App\Modules\Academic\Models\AcademicSession;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionSession extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'admission_sessions';

    protected $fillable = [
        'tenant_id',
        'academic_session_id',
        'code',
        'name',
        'application_start_date',
        'application_end_date',
        'document_submission_deadline',
        'test_start_date',
        'test_end_date',
        'merit_list_start_date',
        'is_current',
        'admission_mode_code',
        'status_code',
        'description',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'application_start_date' => 'date',
            'application_end_date' => 'date',
            'document_submission_deadline' => 'date',
            'test_start_date' => 'date',
            'test_end_date' => 'date',
            'merit_list_start_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }
    
    public function offeredPrograms(): HasMany
    {
        return $this->hasMany(OfferedProgram::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ApplicantProgramApplication::class);
    }
}