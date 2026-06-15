<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionOption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'question_id',
        'option_key',
        'option_text',
        'option_html',
        'option_image_path',
        'is_correct',
        'correct_order',
        'match_key',
        'correct_match_key',
        'marks_percentage',
        'display_order',
        'source_code',
        'import_batch_no',
        'external_ref_no',
    ];
    protected $casts = [
        'is_correct' => 'boolean',
        'marks_percentage' => 'decimal:2',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}