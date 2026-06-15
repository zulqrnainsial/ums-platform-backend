<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionAnswerKey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'question_id',
        'answer_text',
        'answer_number',
        'accepted_variants_json',
        'case_sensitive',
        'numeric_tolerance',
        'marks_percentage',
        'status_code',
    ];

    protected $casts = [
        'accepted_variants_json' => 'array',
        'case_sensitive' => 'boolean',
        'answer_number' => 'decimal:6',
        'numeric_tolerance' => 'decimal:6',
        'marks_percentage' => 'decimal:2',
    ];
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}