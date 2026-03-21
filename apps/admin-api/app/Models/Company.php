<?php

namespace App\Models;

use App\Enums\RoleValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'payment_method_id',
        'status',
        'place_order_weekend',
        'place_order_holiday',
        'order_limit_per_meal',
        'credit_refresh_period',
        'credit_refresh_value',
        'remark',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($model) => $model->generateCode($model, 'C'));
    }

    public function username()
    {
        return $this->hasOne(User::class, 'company_id', 'id')
            ->where('role_id', RoleValue::COMPANY_ADMIN->value)
            ->whereNull('branch_id')
            ->whereNull('restaurant_id')
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

    public function paymentMethods()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id')->withTrashed();
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'company_id', 'id');
    }

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class, 'company_id', 'id');
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class, 'company_id', 'id');
    }
}
