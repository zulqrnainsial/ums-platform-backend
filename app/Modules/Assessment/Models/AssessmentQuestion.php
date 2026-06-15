<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentQuestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'assessment_id',
        'assessment_section_id',
        'question_id',
        'marks',
        'negative_marks',
        'time_seconds',
        'display_order',
        'is_mandatory',
        'status_code',
    ];

    protected $casts = [
        'marks' => 'decimal:2',
        'negative_marks' => 'decimal:2',
        'is_mandatory' => 'boolean',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function section()
    {
        return $this->belongsTo(AssessmentSection::class, 'assessment_section_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}