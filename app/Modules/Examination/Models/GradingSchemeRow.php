<?php

namespace App\Modules\Examination\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class GradingSchemeRow extends Model
{
    use BelongsToTenant;

    protected $table = 'grading_scheme_rows';

    protected $fillable = [
        'tenant_id',
        'grading_scheme_id',
        'sort_order',
        'grade_letter',
        'grade_point',
        'is_pass',
        'minimum_percentage',
        'maximum_percentage',
        'minimum_percentile',
        'maximum_percentile',
        'minimum_rank',
        'maximum_rank',
        'minimum_z_score',
        'maximum_z_score',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_pass' => 'boolean',
        'grade_point' => 'decimal:2',
        'minimum_percentage' => 'decimal:2',
        'maximum_percentage' => 'decimal:2',
        'minimum_percentile' => 'decimal:2',
        'maximum_percentile' => 'decimal:2',
        'minimum_z_score' => 'decimal:4',
        'maximum_z_score' => 'decimal:4',
    ];

    public function gradingScheme()
    {
        return $this->belongsTo(GradingScheme::class);
    }
}