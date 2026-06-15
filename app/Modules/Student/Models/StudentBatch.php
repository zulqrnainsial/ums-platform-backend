<?php

namespace App\Modules\Student\Models;

use App\Modules\Academic\Models\AcademicSession;
use App\Modules\Academic\Models\Department;
use App\Modules\Academic\Models\Faculty;
use App\Modules\Academic\Models\Institute;
use App\Modules\Academic\Models\Program;
use App\Modules\Subject\Models\Curriculum;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentBatch extends Model
{
    use BelongsToTenant;
    use SoftDeletes;
    protected $table = 'student_batches';
    protected $fillable = [
        'tenant_id',
        'academic_session_id',
        'faculty_id',
        'institute_id',
        'department_id',
        'program_id',
        'curriculum_id',
        'code',
        'name',
        'start_date',
        'expected_end_date',
        'capacity',
        'shift',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expected_end_date' => 'date',
            'capacity' => 'integer',
        ];
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
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

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }
}