<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentAttemptAnswer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'assessment_attempt_id',
        'assessment_question_id',
        'question_id',
        'selected_option_ids_json',
        'answer_text',
        'answer_number',
        'uploaded_file_path',
        'is_correct',
        'marks_awarded',
        'negative_marks_applied',
        'manual_marks',
        'answered_at',
        'time_spent_seconds',
        'marked_by',
        'marked_at',
        'marking_remarks',
    ];

    protected $casts = [
    'selected_option_ids_json' => 'array',
    'answered_at' => 'datetime',
    'marked_at' => 'datetime',
    'is_correct' => 'boolean',
    'marks_awarded' => 'decimal:2',
    'negative_marks_applied' => 'decimal:2',
    'manual_marks' => 'decimal:2',
];

    public function attempt()
    {
        return $this->belongsTo(AssessmentAttempt::class, 'assessment_attempt_id');
    }

    public function assessmentQuestion()
    {
        return $this->belongsTo(AssessmentQuestion::class, 'assessment_question_id');
    }
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}