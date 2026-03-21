<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends BaseModel
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $table = 'payment_methods';

    protected $fillable = [
        'name',
        'image',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
