<?php

namespace App\Modules\Student\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentDocument extends Model
{
    use BelongsToTenant;
    use SoftDeletes;
    protected $table = 'student_documents';
    protected $fillable = [
        'tenant_id',
        'student_id',
        'document_type_id',
        'document_title',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'verification_status',
        'verified_at',
        'verified_by',
        'remarks',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'document_type_id');
    }
}