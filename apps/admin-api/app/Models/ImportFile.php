<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'file_path',
        'imported_by',
        'imported_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'import_file_id');
    }
}
