<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'ticket_id',
        'user_id',
        'prize_amount',
        'is_winner',
        'result_details',   
    ];

     public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
