<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'question_bank_id',
        'assessment_subject_id',
        'assessment_topic_id',
        'question_type_code',
        'difficulty_code',
        'cognitive_level_code',
        'question_text',
        'question_html',
        'question_image_path',
        'question_audio_path',
        'question_video_path',
        'default_marks',
        'default_negative_marks',
        'default_time_seconds',
        'explanation',
        'explanation_html',
        'approval_status_code',
        'is_active',
        'source_code',
        'import_batch_no',
        'external_ref_no',
        'created_by',
        'reviewed_by',
        'approved_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_marks' => 'decimal:2',
        'default_negative_marks' => 'decimal:2',
    ];

    public function bank()
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function subject()
    {
        return $this->belongsTo(AssessmentSubject::class, 'assessment_subject_id');
    }

    public function topic()
    {
        return $this->belongsTo(AssessmentTopic::class, 'assessment_topic_id');
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class, 'question_id')->orderBy('display_order');
    }
    public function answerKeys()
    {
        return $this->hasMany(QuestionAnswerKey::class, 'question_id');
    }
}