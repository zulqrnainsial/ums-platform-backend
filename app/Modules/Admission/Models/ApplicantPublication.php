<?php

namespace App\Modules\Admission\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantPublication extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'applicant_publications';

    protected $fillable = [
        'tenant_id',
        'applicant_id',
        'publication_type_id',
        'indexing_type_id',
        'title',
        'journal_conference_name',
        'publisher',
        'publication_year',
        'doi',
        'url',
        'status_code',
        'is_verified',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function publicationType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'publication_type_id');
    }

    public function indexingType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'indexing_type_id');
    }
}