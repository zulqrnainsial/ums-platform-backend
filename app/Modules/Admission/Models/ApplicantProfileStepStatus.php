<?php

namespace App\Modules\Admission\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantProfileStepStatus extends Model
{
    use BelongsToTenant;

    protected $table = 'applicant_profile_step_statuses';

    protected $fillable = [
        'tenant_id',
        'applicant_id',
        'step_code',
        'step_title',
        'status_code',
        'display_order',
        'started_at',
        'completed_at',
        'verified_at',
        'verified_by',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }
}