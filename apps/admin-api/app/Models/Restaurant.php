<?php

namespace App\Models;

use App\Enums\RoleValue;
use App\Models\Scopes\CustomCompanyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([CustomCompanyScope::class])]
class Restaurant extends BaseModel
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
    protected $table = 'restaurants';

    protected $fillable = [
        'company_id',
        'name',
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
            $model->generateCode($model, 'R');
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
        return $this->belongsTo(User::class, 'id', 'restaurant_id')->withTrashed();
    }

    public function adminUser()
    {
        return $this->hasOne(User::class, 'restaurant_id', 'id')
            ->where('role_id', RoleValue::RESTAURANT_ADMIN->value)
            ->withTrashed();
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
