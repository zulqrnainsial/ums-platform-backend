<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentSection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'assessment_id',
        'assessment_subject_id',
        'section_code',
        'section_title',
        'instructions',
        'total_questions',
        'total_marks',
        'passing_marks',
        'duration_minutes',
        'question_selection_mode_code',
        'shuffle_questions',
        'display_order',
        'status_code',
    ];

    protected $casts = [
        'shuffle_questions' => 'boolean',
        'total_marks' => 'decimal:2',
        'passing_marks' => 'decimal:2',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function subject()
    {
        return $this->belongsTo(AssessmentSubject::class, 'assessment_subject_id');
    }

    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class, 'assessment_section_id')->orderBy('display_order');
    }
}
