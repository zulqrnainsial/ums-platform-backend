<?php

namespace App\Modules\Student\Models;

use Illuminate\Database\Eloquent\Model;

class StudentRequest extends Model
{
    protected $table = 'student_requests';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'student_enrollment_id',
        'request_no',
        'request_type',
        'title',
        'description',
        'requested_payload_json',
        'admin_decision_payload_json',
        'related_document_id',
        'related_course_registration_id',
        'related_curriculum_subject_id',
        'related_subject_id',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'student_remarks',
        'admin_remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'requested_payload_json' => 'array',
        'admin_decision_payload_json' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];
}