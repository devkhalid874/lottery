<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Winner extends Model
{
        protected $fillable = [
        'game_id',
        'ticket_id',
        'user_id',
        'winning_numbers',
        'winning_prize',
    ];

     // A winner belongs to a game
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    // A winner belongs to a ticket
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    // A winner belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
