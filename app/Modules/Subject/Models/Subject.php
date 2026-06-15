<?php

namespace App\Modules\Subject\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Subject\Models\CurriculumSubject;
class Subject extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'subject_type_id',
        'subject_group_id',
        'code',
        'name',
        'short_name',
        'credit_hours',
        'theory_hours',
        'practical_hours',
        'tutorial_hours',
        'subject_nature',
        'grading_method',
        'total_marks',
        'passing_marks',
        'is_credit_subject',
        'is_compulsory',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'credit_hours' => 'decimal:2',
            'theory_hours' => 'integer',
            'practical_hours' => 'integer',
            'tutorial_hours' => 'integer',
            'total_marks' => 'integer',
            'passing_marks' => 'integer',
            'is_credit_subject' => 'boolean',
            'is_compulsory' => 'boolean',
        ];
    }

    public function subjectType(): BelongsTo
    {
        return $this->belongsTo(SubjectType::class);
    }

    public function subjectGroup(): BelongsTo
    {
        return $this->belongsTo(SubjectGroup::class);
    }

    public function curriculumSubjects(): HasMany
    {
        return $this->hasMany(CurriculumSubject::class);
    }

    public function prerequisites(): HasMany
    {
        return $this->hasMany(SubjectPrerequisite::class);
    }
}