<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAttemptActivityLog extends Model
{
    protected $table = 'assessment_attempt_activity_logs';

    protected $fillable = [
        'tenant_id',
        'assessment_attempt_id',
        'assessment_participant_id',
        'assessment_id',
        'assessment_schedule_id',
        'applicant_id',
        'event_code',
        'severity_code',
        'assessment_question_id',
        'question_id',
        'event_payload_json',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected $casts = [
        'event_payload_json' => 'array',
        'occurred_at' => 'datetime',
    ];
}