<?php

namespace App\Models;

use App\Models\Scopes\CustomCompanyScope;
use App\Models\Scopes\CustomRestaurantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([CustomRestaurantScope::class, CustomCompanyScope::class])]
class MenuItem extends BaseModel
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
    protected $table = 'menu_items';

    protected $fillable = [
        'company_id',
        'code',
        'restaurant_id',
        'menu_category_id',
        'name',
        'meal_time',
        'unit_price',
        'available_quantity',
        'add_on',
        'selection_type',
        'image',
        'remark',
        'status',
        'is_veg',
        'contain_egg',
        'contain_dairy',
        'contain_onion_garlic',
        'contain_chili',
        'import_file_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function booted()
    {
        static::deleting(function ($model) {

            $model->addons()->each(function ($addon) {
                $addon->options()->delete();
            });
            $model->addons()->delete();
        });
    }

    // protected $with = ['addons.options'];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function menuCategory()
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id', 'id')->withTrashed();
    }

    public function servicedDates()
    {
        return $this->belongsTo(MenuServedDate::class, 'menu_served_date_id', 'id');
    }

    public function mealTime()
    {
        return $this->belongsTo(Selection::class, 'meal_time', 'id');
    }

    // Addons
    public function addons()
    {
        return $this->hasMany(MenuItemAddOn::class, 'menu_item_id', 'id');
    }

    public function importFile()
    {
        return $this->belongsTo(ImportFile::class, 'import_file_id', 'id');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id', 'id');
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
