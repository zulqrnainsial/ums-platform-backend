<?php

namespace App\Modules\Admission\Models;

use App\Modules\Academic\Models\AcademicSession;
use App\Modules\Academic\Models\Campus;
use App\Modules\Academic\Models\Department;
use App\Modules\Academic\Models\Faculty;
use App\Modules\Academic\Models\Institute;
use App\Modules\Academic\Models\Program;
use App\Modules\Academic\Models\ProgramLevel;
use App\Modules\Lookup\Models\LookupValue;
use App\Modules\Student\Models\StudentBatch;
use App\Modules\Subject\Models\Curriculum;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferedProgram extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'offered_programs';

    protected $fillable = [
        'tenant_id',
        'admission_session_id',
        'academic_session_id',
        'campus_id',
        'faculty_id',
        'institute_id',
        'department_id',
        'program_level_id',
        'program_id',
        'curriculum_id',
        'student_batch_id',
        'code',
        'title',
        'shift_id',
        'shift_code',
        'application_fee',
        'admission_fee',
        'requires_test',
        'requires_interview',
        'requires_experience',
        'requires_research_profile',
        'is_published',
        'application_start_date',
        'application_end_date',
        'status_code',
        'description',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'application_fee' => 'decimal:2',
            'admission_fee' => 'decimal:2',
            'requires_test' => 'boolean',
            'requires_interview' => 'boolean',
            'requires_experience' => 'boolean',
            'requires_research_profile' => 'boolean',
            'is_published' => 'boolean',
            'application_start_date' => 'date',
            'application_end_date' => 'date',
        ];
    }

    public function admissionSession(): BelongsTo
    {
        return $this->belongsTo(AdmissionSession::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function programLevel(): BelongsTo
    {
        return $this->belongsTo(ProgramLevel::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function studentBatch(): BelongsTo
    {
        return $this->belongsTo(StudentBatch::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'shift_id');
    }

    public function quotaSeats(): HasMany
    {
        return $this->hasMany(ProgramQuotaSeat::class);
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