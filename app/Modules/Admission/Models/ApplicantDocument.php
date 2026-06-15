<?php

namespace App\Modules\Admission\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantDocument extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'applicant_documents';

    protected $fillable = [
        'tenant_id',
        'applicant_id',
        'applicant_program_application_id',
        'document_type_id',
        'document_title',
        'related_table',
        'related_id',
        'file_path',
        'original_file_name',
        'stored_file_name',
        'mime_type',
        'file_size',
        'verification_status_code',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'verified_at' => 'datetime',
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

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'document_type_id');
    }
}