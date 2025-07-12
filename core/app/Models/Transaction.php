<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{

        protected $fillable = [
        'user_id',
        'amount',
        'post_balance',
        'trx_type',
        'trx',
        'details',
        'remark',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
