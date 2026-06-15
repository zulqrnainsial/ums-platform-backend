<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAttempt extends Model
{

    protected $fillable = [
        'tenant_id',
        'assessment_participant_id',
        'attempt_no',
        'started_at',
        'submitted_at',
        'auto_submitted_at',
        'duration_seconds',
        'ip_address',
        'user_agent',
        'tab_switch_count',
        'warning_count',
        'status_code',
        'obtained_marks',
        'negative_marks',
        'final_marks',
        'percentage',
    ];

    protected $casts = [
    'started_at' => 'datetime',
    'submitted_at' => 'datetime',
    'auto_submitted_at' => 'datetime',
    'duration_seconds' => 'integer',
    'obtained_marks' => 'decimal:2',
    'negative_marks' => 'decimal:2',
    'final_marks' => 'decimal:2',
    'percentage' => 'decimal:2',
];

    public function participant()
    {
        return $this->belongsTo(AssessmentParticipant::class, 'assessment_participant_id');
    }
    public function answers()
    {
        return $this->hasMany(AssessmentAttemptAnswer::class, 'assessment_attempt_id');
    }

}