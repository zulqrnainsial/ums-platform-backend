<?php

namespace App\Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionMeritList extends Model
{

    protected $fillable = [
        'tenant_id',
        'admission_session_id',
        'admission_preference_group_id',
        'offered_program_id',
        'program_quota_seat_id',
        'admission_merit_formula_id',
        'list_no',
        'title',
        'status_code',
        'list_type_code',
        'total_candidates',
        'selected_candidates',
        'waiting_candidates',
        'available_seats',
        'highest_merit_score',
        'lowest_merit_score',
        'generation_filters_json',
        'generation_summary_json',
        'generated_at',
        'generated_by',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'generation_filters_json' => 'array',
        'generation_summary_json' => 'array',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
        'highest_merit_score' => 'decimal:4',
        'lowest_merit_score' => 'decimal:4',
    ];

    public function applicants()
    {
        return $this->hasMany(AdmissionMeritListApplicant::class, 'admission_merit_list_id');
    }
}