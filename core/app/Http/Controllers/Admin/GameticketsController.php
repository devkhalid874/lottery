<?php

namespace App\Http\Controllers\Admin;

use App\Models\Game;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GameticketsController extends Controller
{
public function gameticket($id){
    $pageTitle = "Tickets for Game ID: $id";
    $game = Game::with(['tickets.user'])->findOrFail($id); // ✅ fetch the game with tickets + user

    return view('admin.gameticket.maindash', compact('pageTitle', 'game'));
}


    public function gameList()
{
    $pageTitle = "Game List";
    $games = Game::withCount('tickets')->latest()->get();

    return view('admin.gameticket.list', compact('pageTitle', 'games'));
}

public function setWinner(Request $request, $id)
{
    $request->validate([
        'winning_numbers' => 'required|array|min:1|max:6',
    ]);

    $game = Game::with('tickets')->findOrFail($id);

    // Convert winning numbers to strings
    $winningNumbers = array_map('strval', $request->winning_numbers);
    $game->winning_numbers = json_encode($winningNumbers);
    $game->save();

    
    foreach ($game->tickets as $ticket) {
        $ticketNumbers = json_decode($ticket->number);

        // Convert ticket numbers to strings (safety check)
        $ticketNumbers = array_map('strval', $ticketNumbers);

        // Match numbers
        $matches = array_intersect($ticketNumbers, $winningNumbers);

        // If all winning numbers are in the ticket
        if (count($matches) === count($winningNumbers)) {
            $ticket->is_winner = 1;
            $ticket->save();
        }
    }

    $notify[] = ['success', 'Winning numbers announced and winning tickets marked!'];
    return back()->withNotify($notify);
}



}
