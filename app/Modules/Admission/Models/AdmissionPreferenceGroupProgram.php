<?php

namespace App\Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionPreferenceGroupProgram extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'admission_preference_group_id',
        'offered_program_id',
        'display_order',
        'status_code',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    public function preferenceGroup()
    {
        return $this->belongsTo(AdmissionPreferenceGroup::class, 'admission_preference_group_id');
    }

    public function offeredProgram()
    {
        return $this->belongsTo(OfferedProgram::class, 'offered_program_id');
    }
}