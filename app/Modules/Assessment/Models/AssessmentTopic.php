<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentTopic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'assessment_subject_id',
        'parent_id',
        'code',
        'name',
        'description',
        'status_code',
        'display_order',
    ];

    public function subject()
    {
        return $this->belongsTo(AssessmentSubject::class, 'assessment_subject_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}