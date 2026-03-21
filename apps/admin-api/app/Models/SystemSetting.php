<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemSetting extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'system_settings';

    protected $fillable = ['system_setting_type_uuid', 'role_type', 'table_primary_id', 'value', 'status', 'created_by', 'updated_by', 'deleted_by'];

    public function systemSettingType()
    {
        return $this->belongsTo(SystemSettingType::class, 'system_setting_type_uuid', 'uuid');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'table_primary_id', 'id')->withTrashed();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')->withTrashed();
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id')->withTrashed();
    }

    public function showUsername($role_type, $table_primary_id)
    {
        $role = Role::where('name', $role_type)->first();

        if ($role) {
            $admin = User::find($table_primary_id);
            if ($admin) {
                return $admin->name;
            }

            return 'Admin not found';
        }

        return 'Role not found';
    }
}
