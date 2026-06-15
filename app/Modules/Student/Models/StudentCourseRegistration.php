<?php

namespace App\Modules\Student\Models;

use Illuminate\Database\Eloquent\Model;

class StudentCourseRegistration extends Model
{
    protected $table = 'student_course_registrations';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'student_enrollment_id',
        'program_id',
        'academic_session_id',
        'academic_term_id',
        'term_id',
        'curriculum_id',
        'curriculum_subject_id',
        'subject_id',
        'course_code',
        'course_title',
        'credit_hours',
        'subject_type_code',
        'registration_type',
        'status',
        'is_locked',
        'registered_at',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'credit_hours' => 'decimal:2',
        'is_locked' => 'boolean',
        'registered_at' => 'datetime',
    ];
}