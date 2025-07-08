<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use GlobalStatus;

    protected $casts = [
    'winning_numbers' => 'array',
];

    
    public function invests()
    {
        return $this->hasMany(Invest::class);
    }

    public function gameSetting()
    {
        return $this->belongsTo(GameSetting::class);
    }

    public function tickets()
{
    return $this->hasMany(Ticket::class);
}
}
