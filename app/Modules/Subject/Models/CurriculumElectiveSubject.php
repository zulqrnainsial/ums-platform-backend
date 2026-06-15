<?php

namespace App\Modules\Subject\Models;

use App\Modules\Academic\Models\AcademicTerm;
use App\Modules\Academic\Models\Program;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurriculumElectiveSubject extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'curriculum_id',
        'program_id',
        'academic_term_id',
        'elective_group_code',
        'elective_group_name',
        'subject_id',
        'display_order',
        'remarks',
        'status',
        'created_by',
        'updated_by',
        'subject_code',
'subject_name',
'subject_nature',
'credit_hours',
'theory_hours',
'practical_hours',
'tutorial_hours',
'total_marks',
'passing_marks',
'is_compulsory',
'is_credit_subject',
    ];

    protected function casts(): array
{
    return [
        'credit_hours' => 'decimal:2',
        'theory_hours' => 'integer',
        'practical_hours' => 'integer',
        'tutorial_hours' => 'integer',
        'total_marks' => 'integer',
        'passing_marks' => 'integer',
        'is_compulsory' => 'boolean',
        'is_credit_subject' => 'boolean',
        'display_order' => 'integer',
    ];
}

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}