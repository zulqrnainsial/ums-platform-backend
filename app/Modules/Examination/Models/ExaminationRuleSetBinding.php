<?php

namespace App\Modules\Examination\Models;

use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Modules\Academic\Models\Program;
use App\Modules\Student\Models\StudentBatch;
use App\Modules\Subject\Models\Curriculum;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExaminationRuleSetBinding extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'examination_rule_set_bindings';

    protected $fillable = [
        'tenant_id',
        'examination_rule_set_id',
        'program_id',
        'curriculum_id',
        'student_batch_id',
        'academic_session_id',
        'academic_term_id',
        'effective_from',
        'effective_to',
        'is_active',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function ruleSet()
    {
        return $this->belongsTo(ExaminationRuleSet::class, 'examination_rule_set_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function studentBatch()
    {
        return $this->belongsTo(StudentBatch::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }
    
    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }
}