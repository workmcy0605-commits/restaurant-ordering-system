<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuServedDate extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'menu_served_dates';

    protected $fillable = [

        'menu_category_id',
        'start_date',
        'end_date',
        'select_day',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function menuCategory()
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id', 'id')->withTrashed();
    }
}
