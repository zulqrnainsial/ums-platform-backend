<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentSubject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'status_code',
        'display_order',
        'created_by',
        'updated_by',
    ];

    public function topics()
    {
        return $this->hasMany(AssessmentTopic::class, 'assessment_subject_id');
    }
}
