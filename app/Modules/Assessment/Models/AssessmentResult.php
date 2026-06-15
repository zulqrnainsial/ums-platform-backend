<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentResult extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'assessment_id',
        'assessment_participant_id',
        'assessment_attempt_id',
        'total_marks',
        'obtained_marks',
        'negative_marks',
        'final_marks',
        'percentage',
        'passing_marks',
        'is_passed',
        'rank',
        'percentile',
        'grade_code',
        'strengths_json',
        'weaknesses_json',
        'analysis_json',
        'result_status_code',
        'generated_at',
        'approved_by',
        'approved_at',
        'published_at',
        'remarks',
    ];

    protected $casts = [
        'strengths_json' => 'array',
        'weaknesses_json' => 'array',
        'analysis_json' => 'array',
        'is_passed' => 'boolean',
        'generated_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'total_marks' => 'decimal:2',
        'obtained_marks' => 'decimal:2',
        'negative_marks' => 'decimal:2',
        'final_marks' => 'decimal:2',
        'percentage' => 'decimal:2',
        'passing_marks' => 'decimal:2',
        'percentile' => 'decimal:2',
    ];

    public function participant()
    {
        return $this->belongsTo(AssessmentParticipant::class, 'assessment_participant_id');
    }
public function attempt()
{
    return $this->belongsTo(AssessmentAttempt::class, 'assessment_attempt_id');
}
    public function sectionResults()
    {
        return $this->hasMany(AssessmentSectionResult::class, 'assessment_result_id');
    }
}