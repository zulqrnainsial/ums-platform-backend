<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionBank extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'assessment_category_id',
        'assessment_subject_id',
        'code',
        'name',
        'description',
        'status_code',
        'display_order',
        'created_by',
        'updated_by',
    ];
    public function category()
    {
        return $this->belongsTo(AssessmentCategory::class, 'assessment_category_id');
    }

    public function subject()
    {
        return $this->belongsTo(AssessmentSubject::class, 'assessment_subject_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'question_bank_id');
    }
}