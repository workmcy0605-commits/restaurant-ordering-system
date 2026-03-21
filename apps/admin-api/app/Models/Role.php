<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'roles';

    protected $fillable = ['name', 'status', 'role_type', 'created_by', 'updated_by', 'deleted_by'];

    public function rolePermission()
    {
        return $this->hasMany(RolePermission::class);
    }

    public function permissions()
    {
        return $this->hasMany(RolePermission::class, 'role_id', 'id');
    }
}
