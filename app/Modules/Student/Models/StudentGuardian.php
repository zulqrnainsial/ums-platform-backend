<?php

namespace App\Modules\Student\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentGuardian extends Model
{
    use BelongsToTenant;
    use SoftDeletes;
    protected $table = 'student_guardians';
    protected $fillable = [
        'tenant_id',
        'student_id',
        'guardian_id',
        'relationship_type_id',
        'is_primary',
        'is_emergency_contact',
        'can_pick_student',
        'remarks',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_emergency_contact' => 'boolean',
            'can_pick_student' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function relationshipType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'relationship_type_id');
    }
}