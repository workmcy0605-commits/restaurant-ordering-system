<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItemAddOnOption extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'menu_item_add_on_options';

    protected $fillable = [
        'menu_item_add_on_id',
        'name',
        'surcharge',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function addon()
    {
        return $this->belongsTo(MenuItemAddOn::class, 'menu_item_add_on_id');
    }
}
