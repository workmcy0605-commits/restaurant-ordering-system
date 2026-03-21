<?php

namespace App\Models;

use App\Models\Scopes\CustomCompanyScope;
use App\Models\Scopes\CustomRestaurantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([CustomRestaurantScope::class, CustomCompanyScope::class])]
class MenuCategory extends BaseModel
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
    protected $table = 'menu_categories';

    protected $fillable = [
        'company_id',
        'code',
        'restaurant_id',
        'name',
        'repeat',
        'repeat_by',
        'remark',
        'start_time',
        'end_time',
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
            $model->generateCode($model, 'M');
        });

        static::deleting(function ($model) {

            $model->menuItems()->each(function ($menuItem) {
                $menuItem->addons()->each(function ($addon) {
                    $addon->options()->delete();
                });
                $menuItem->addons()->delete();
            });

            $model->servicedDates()->delete();
            $model->menuItems()->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id', 'id')->withTrashed();
    }

    public function servicedDates()
    {
        return $this->hasMany(MenuServedDate::class, 'menu_category_id', 'id');
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'menu_category_id', 'id');
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
