<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemSettingType extends BaseModel
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $table = 'system_setting_types';

    protected $fillable = ['uuid', 'name', 'data_type', 'is_branchadmin', 'created_by', 'updated_by', 'deleted_by'];

    public function systemSettings()
    {
        return $this->hasMany(SystemSetting::class, 'system_setting_type_uuid', 'uuid');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')->withTrashed();
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id')->withTrashed();
    }
}
