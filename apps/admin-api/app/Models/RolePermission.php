<?php

namespace App\Models;

use Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RolePermission extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Traits
    |--------------------------------------------------------------------------
    */
    use HasFactory, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */
    protected $table = 'role_permissions';

    protected $fillable = ['role_id', 'permission_name'];

    /*
    |--------------------------------------------------------------------------
    | Booted / Model Events
    |--------------------------------------------------------------------------
    */
    protected static function booted()
    {
        static::created(function ($rolePermission) {
            Cache::forget('role_permissions_'.$rolePermission->role_id);
        });

        static::updated(function ($rolePermission) {
            Cache::forget('role_permissions_'.$rolePermission->role_id);
        });

        static::deleted(function ($rolePermission) {
            Cache::forget('role_permissions_'.$rolePermission->role_id);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }
}
