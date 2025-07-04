<?php

namespace App\Models;

use App\Traits\ApiQuery;
use Illuminate\Database\Eloquent\Model;

class PoolInvest extends Model
{
    use ApiQuery;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pool()
    {
        return $this->belongsTo(Pool::class);
    }
}
