<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccessControl extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'access_controls';

    protected $fillable = ['name', 'type', 'status', 'created_by', 'updated_by', 'deleted_by'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
