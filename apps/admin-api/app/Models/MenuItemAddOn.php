<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItemAddOn extends BaseModel
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
    protected $table = 'menu_item_add_ons';

    protected $fillable = [
        'menu_item_id',
        'name',
        'type',
        'min',
        'max',
        'add_on_required',
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
        static::deleting(function (MenuItemAddOn $addon) {

            $addon->options->each(function ($option) {
                $option->delete();
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id', 'id');
    }

    public function options()
    {
        return $this->hasMany(MenuItemAddOnOption::class, 'menu_item_add_on_id', 'id');
    }
}
