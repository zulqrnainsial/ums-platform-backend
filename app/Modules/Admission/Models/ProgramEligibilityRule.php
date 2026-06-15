<?php

namespace App\Modules\Admission\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramEligibilityRule extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'program_eligibility_rules';

    protected $fillable = [
        'tenant_id',
        'offered_program_id',
        'program_quota_seat_id',
        'eligibility_rule_type_id',
        'rule_code',
        'rule_group',
        'rule_title',
        'operator',
        'value_text',
        'value_number',
        'value_date',
        'value_lookup_id',
        'value_json',
        'target_qualification_level_id',
        'target_subject_group_id',
        'target_document_type_id',
        'target_test_code',
        'is_mandatory',
        'is_active',
        'failure_message',
        'description',
        'display_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'value_number' => 'decimal:4',
            'value_date' => 'date',
            'value_json' => 'array',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function offeredProgram(): BelongsTo
    {
        return $this->belongsTo(OfferedProgram::class);
    }

    public function programQuotaSeat(): BelongsTo
    {
        return $this->belongsTo(ProgramQuotaSeat::class);
    }

    public function eligibilityRuleType(): BelongsTo
    {
        return $this->belongsTo(EligibilityRuleType::class);
    }

    public function valueLookup(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'value_lookup_id');
    }

    public function targetQualificationLevel(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'target_qualification_level_id');
    }

    public function targetSubjectGroup(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'target_subject_group_id');
    }

    public function targetDocumentType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'target_document_type_id');
    }
}