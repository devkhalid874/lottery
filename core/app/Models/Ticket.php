<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $casts = [
    'number' => 'array',    
];
     protected $fillable = [
        'user_id', 'game_id', 'number', 'ticket_id', 'amount'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function tickets()
{
    return $this->hasMany(Ticket::class);
}

public function getFormattedTicketIdAttribute()
{
    return getTicketId($this->id);
}


}
