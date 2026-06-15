<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentParticipant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'assessment_id',
        'assessment_schedule_id',
        'participant_type_code',
        'participant_id',
        'applicant_id',
        'student_id',
        'employee_id',
        'hr_candidate_id',
        'roll_no',
        'seat_no',
        'attendance_status_code',
        'attempt_status_code',
        'result_status_code',
        'assigned_at',
        'started_at',
        'submitted_at',
        'evaluated_at',
        'obtained_marks',
        'percentage',
        'grade_code',
        'remarks',
        'import_batch_no',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'evaluated_at' => 'datetime',
        'obtained_marks' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function schedule()
    {
        return $this->belongsTo(AssessmentSchedule::class, 'assessment_schedule_id');
    }
    public function attempts()
    {
        return $this->hasMany(AssessmentAttempt::class, 'assessment_participant_id');
    }

    public function result()
    {
        return $this->hasOne(AssessmentResult::class, 'assessment_participant_id');
    }
}