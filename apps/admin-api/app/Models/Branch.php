<?php

namespace App\Models;

use App\Enums\GuardType;
use App\Enums\RoleValue;
use App\Models\Scopes\CustomCompanyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([CustomCompanyScope::class])]
class Branch extends BaseModel
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
    protected $table = 'branches';

    protected $fillable = [
        'company_id',
        'name',
        'location',
        'remark',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Booted / Model Events
    |--------------------------------------------------------------------------
    */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->generateCode($model, 'B');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function companyName()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id')->withTrashed();
    }

    public function username()
    {
        return $this->belongsTo(User::class, 'id', 'branch_id')->withTrashed();
    }

    public function adminUser()
    {
        return $this->hasOne(User::class, 'branch_id', 'id')
            ->where('role_id', RoleValue::BRANCH_ADMIN->value)
            ->withTrashed();
    }

    public function usernameWithDeleted()
    {
        return $this->hasMany(User::class, 'branch_id', 'id')
            ->where('guard_name', GuardType::API->value);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')->withTrashed();
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id')->withTrashed();
    }

    public function usersCount()
    {
        return $this->hasMany(User::class, 'branch_id')
            ->where('guard_name', GuardType::API->value);
    }
}
