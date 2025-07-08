<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Game;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GameticketsController extends Controller
{
   public function gameticket(Request $request, $id)
{
    $pageTitle = "Tickets for Game ID: $id";
    $game = Game::findOrFail($id);

    $ticketsQuery = $game->tickets()->with('user');

    if ($request->day || ($request->start_time && $request->end_time)) {
        $ticketsQuery->where(function ($query) use ($request) {
            if ($request->day) {
                $query->whereDay('created_at', '>=', 1); // Dummy to start nested query
                $query->whereRaw("DAYNAME(created_at) = ?", [$request->day]);
            }

            if ($request->start_time && $request->end_time) {
                $query->whereTime('created_at', '>=', $request->start_time)
                      ->whereTime('created_at', '<=', $request->end_time);
            }
        });
    }

    $filteredTickets = $ticketsQuery->get();

    return view('admin.gameticket.maindash', compact('pageTitle', 'game', 'filteredTickets'));
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

        // Converting winning numbers to strings
        $winningNumbers = array_map('strval', $request->winning_numbers);
        $game->winning_numbers = $winningNumbers;
        $game->save();


        foreach ($game->tickets as $ticket) {
            $ticketNumbers = json_decode($ticket->number);
            $ticketNumbers = array_map('strval', $ticketNumbers);

            if (count(array_intersect($winningNumbers, $ticketNumbers)) === count($winningNumbers)) {
                $ticket->is_winner = 1;
                $ticket->save();
            }
        }

        $notify[] = ['success', 'Winning numbers announced and winning tickets marked!'];
        return back()->withNotify($notify);
    }
}
