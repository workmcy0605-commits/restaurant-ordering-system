<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'permissions';

    protected $fillable = ['name', 'is_branchadmin', 'created_by', 'updated_by', 'deleted_by'];
}
