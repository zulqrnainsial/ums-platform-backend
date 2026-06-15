<?php

namespace App\Modules\Student\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentPreviousEducation extends Model
{
    use BelongsToTenant;
    use SoftDeletes;
    protected $table = 'student_previous_educations';
    protected $fillable = [
        'tenant_id',
        'student_id',
        'qualification_level_id',
        'education_board_id',
        'external_institution_id',
        'degree_class_name',
        'roll_no',
        'registration_no',
        'passing_year',
        'total_marks',
        'obtained_marks',
        'percentage',
        'grade',
        'cgpa',
        'document_path',
        'remarks',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_marks' => 'integer',
            'obtained_marks' => 'integer',
            'percentage' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function qualificationLevel(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'qualification_level_id');
    }

    public function educationBoard(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'education_board_id');
    }

    public function externalInstitution(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'external_institution_id');
    }
}