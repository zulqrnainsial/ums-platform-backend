<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'assessment_category_id',
        'assessment_type_id',
        'admission_session_id',
        'purpose_code',
        'mode_code',
        'code',
        'title',
        'description',
        'instructions_html',
        'total_marks',
        'passing_marks',
        'duration_minutes',
        'allow_negative_marking',
        'negative_marking_type_code',
        'attempt_limit',
        'shuffle_questions',
        'shuffle_options',
        'show_result_immediately',
        'show_correct_answers',
        'allow_review_before_submit',
        'status_code',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'allow_negative_marking' => 'boolean',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'show_result_immediately' => 'boolean',
        'show_correct_answers' => 'boolean',
        'allow_review_before_submit' => 'boolean',
        'total_marks' => 'decimal:2',
        'passing_marks' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(AssessmentCategory::class, 'assessment_category_id');
    }

    public function sections()
    {
        return $this->hasMany(AssessmentSection::class, 'assessment_id')->orderBy('display_order');
    }

    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class, 'assessment_id')->orderBy('display_order');
    }

    public function schedules()
    {
        return $this->hasMany(AssessmentSchedule::class, 'assessment_id');
    }

    public function participants()
    {
        return $this->hasMany(AssessmentParticipant::class, 'assessment_id');
    }
}