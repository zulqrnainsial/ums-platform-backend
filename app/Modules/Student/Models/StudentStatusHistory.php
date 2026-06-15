<?php

namespace App\Modules\Student\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentStatusHistory extends Model
{
    use BelongsToTenant;
    protected $table = 'student_status_histories';
    protected $fillable = [
        'tenant_id',
        'student_id',
        'from_status',
        'to_status',
        'effective_date',
        'reason',
        'remarks',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}