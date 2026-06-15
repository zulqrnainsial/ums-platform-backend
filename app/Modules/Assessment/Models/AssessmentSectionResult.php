<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentSectionResult extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'assessment_result_id',
        'assessment_section_id',
        'assessment_subject_id',
        'total_marks',
        'obtained_marks',
        'negative_marks',
        'final_marks',
        'percentage',
        'is_passed',
        'topic_analysis_json',
        'difficulty_analysis_json',
        'question_type_analysis_json',
    ];

    protected $casts = [
        'topic_analysis_json' => 'array',
        'difficulty_analysis_json' => 'array',
        'question_type_analysis_json' => 'array',
        'is_passed' => 'boolean',
        'total_marks' => 'decimal:2',
        'obtained_marks' => 'decimal:2',
        'negative_marks' => 'decimal:2',
        'final_marks' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    public function result()
    {
        return $this->belongsTo(AssessmentResult::class, 'assessment_result_id');
    }
}