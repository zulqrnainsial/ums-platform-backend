<?php

namespace App\Modules\Admission\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantTestResult extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'applicant_test_results';

    protected $fillable = [
        'tenant_id',
        'applicant_id',
        'applicant_program_application_id',
        'test_type_id',
        'test_source_code',
        'test_code',
        'test_name',
        'roll_no',
        'test_date',
        'total_marks',
        'obtained_marks',
        'percentage',
        'percentile',
        'result_status_code',
        'is_verified',
        'document_id',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'test_date' => 'date',
            'total_marks' => 'decimal:2',
            'obtained_marks' => 'decimal:2',
            'percentage' => 'decimal:2',
            'percentile' => 'decimal:2',
            'is_verified' => 'boolean',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ApplicantProgramApplication::class, 'applicant_program_application_id');
    }

    public function testType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'test_type_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ApplicantDocument::class, 'document_id');
    }
}