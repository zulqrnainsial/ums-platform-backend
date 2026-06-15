<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'assessment_id',
        'schedule_code',
        'title',
        'start_at',
        'end_at',
        'reporting_time',
        'timezone',
        'mode_code',
        'venue_name',
        'campus_id',
        'building_id',
        'room_id',
        'max_candidates',
        'status_code',
        'instructions',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'reporting_time' => 'datetime',
        'max_candidates' => 'integer',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }
}