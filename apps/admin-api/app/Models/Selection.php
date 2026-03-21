<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Selection extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['category', 'value', 'created_by', 'updated_by', 'deleted_by'];

    protected $dates = ['created_at', 'updated_at'];
}
