<?php

namespace App\Modules\Admission\Models;

use App\Modules\Subject\Models\Subject;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantQualificationSubject extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'applicant_qualification_subjects';

    protected $fillable = [
        'tenant_id',
        'applicant_qualification_id',
        'subject_id',
        'subject_code',
        'subject_name',
        'total_marks',
        'obtained_marks',
        'percentage',
        'grade',
        'result_status_code',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'total_marks' => 'decimal:2',
            'obtained_marks' => 'decimal:2',
            'percentage' => 'decimal:2',
        ];
    }

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(ApplicantQualification::class, 'applicant_qualification_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}