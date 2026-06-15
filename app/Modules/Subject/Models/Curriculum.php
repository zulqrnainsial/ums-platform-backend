<?php

namespace App\Modules\Subject\Models;

use App\Modules\Academic\Models\Department;
use App\Modules\Academic\Models\Faculty;
use App\Modules\Academic\Models\Institute;
use App\Modules\Academic\Models\Program;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Subject\Models\CurriculumSubject;
class Curriculum extends Model
{
    use BelongsToTenant;
    use SoftDeletes;
    protected $table = 'curriculums';
    protected $fillable = [
        'tenant_id',
        'faculty_id',
        'institute_id',
        'department_id',
        'program_id',
        'code',
        'name',
        'version',
        'effective_from',
        'effective_to',
        'is_current',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function curriculumSubjects(): HasMany
    {
        return $this->hasMany(CurriculumSubject::class);
    }
    public function electiveSubjects(): HasMany
{
    return $this->hasMany(CurriculumElectiveSubject::class);
}
}