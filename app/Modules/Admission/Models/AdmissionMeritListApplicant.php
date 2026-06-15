<?php

namespace App\Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionMeritListApplicant extends Model
{

    protected $fillable = [
        'tenant_id',
        'admission_merit_list_id',
        'applicant_id',
        'admission_application_id',
        'admission_applicant_merit_score_id',
        'admission_session_id',
        'admission_preference_group_id',
        'offered_program_id',
        'program_quota_seat_id',
        'merit_position',
        'preference_order',
        'final_merit_score',
        'is_eligible_for_merit',
        'selection_status_code',
        'offer_status_code',
        'offer_generated_at',
        'offer_expiry_at',
        'score_snapshot_json',
        'selection_reason_json',
    ];

    protected $casts = [
        'final_merit_score' => 'decimal:4',
        'is_eligible_for_merit' => 'boolean',
        'score_snapshot_json' => 'array',
        'selection_reason_json' => 'array',
        'offer_generated_at' => 'datetime',
        'offer_expiry_at' => 'datetime',
    ];

    public function meritList()
    {
        return $this->belongsTo(AdmissionMeritList::class, 'admission_merit_list_id');
    }
}