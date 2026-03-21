<?php

namespace App\Models;

use App\Traits\Action\TracksUserActions;
use App\Traits\GenerateCode;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use GenerateCode, TracksUserActions;
}
