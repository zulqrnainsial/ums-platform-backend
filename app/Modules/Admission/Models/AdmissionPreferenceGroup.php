<?php

namespace App\Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionPreferenceGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'admission_session_id',
        'code',
        'name',
        'description',
        'min_preferences',
        'max_preferences',
        'is_default',
        'status_code',
        'display_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'min_preferences' => 'integer',
        'max_preferences' => 'integer',
        'display_order' => 'integer',
    ];

    public function admissionSession()
    {
        return $this->belongsTo(AdmissionSession::class, 'admission_session_id');
    }
    
    public function programs()
    {
        return $this->hasMany(AdmissionPreferenceGroupProgram::class, 'admission_preference_group_id');
    }
}