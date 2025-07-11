<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Game;
use App\Models\Ticket;
use App\Models\Winner;
use App\Models\Leaderboard;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GameticketsController extends Controller
{
  public function gameticket(Request $request, $id)
{
    $pageTitle = "Tickets for Game ID: $id";
    $game = Game::with('winner')->findOrFail($id); // if you need winner data

    $ticketsQuery = $game->tickets()->with('user');

    $ticketsQuery->where(function ($query) use ($request) {
        if ($request->day) {
            $query->whereRaw("DAYNAME(created_at) = ?", [$request->day]);
        }

        if ($request->start_time) {
            $query->whereTime('created_at', '>=', $request->start_time);
        }

        if ($request->end_time) {
            $query->whereTime('created_at', '<=', $request->end_time);
        }
    });

    $filteredTickets = $ticketsQuery->get();

    return view('admin.gameticket.maindash', compact('pageTitle', 'game', 'filteredTickets'));
}


    public function gameList(Request $request)
    {
        $pageTitle = "Game List";
        $allGames = Game::all();
$query = Game::with([
    'tickets' => function ($q) use ($request) {
        if ($request->date) {
            $q->whereDate('created_at', $request->date);
        }
    },
    'tickets.user',
    'winner' // ✅ Added
]);


        if ($request->game_id) {
            $query->where('id', $request->game_id);
        }

        $games = $query->latest()->get();

        $numberStats = [];

        foreach ($games as $game) {
            $numbersCount = [];

            foreach ($game->tickets as $ticket) {
                $amount = $ticket->amount ?? 0;

                // 🛠 Fix: Handle multiple formats of ticket numbers
                $numbers = json_decode($ticket->number, true);

                if (!is_array($numbers)) {
                    if (str_contains($ticket->number, ',')) {
                        $numbers = explode(',', $ticket->number);
                    } else {
                        $numbers = [$ticket->number]; // single string
                    }
                }

                $numbers = array_map('trim', $numbers); // remove any whitespace

                foreach ($numbers as $num) {
                    $num = ltrim((string) $num, '0');

                    // If becomes empty after ltrim (e.g., original was "00"), restore "0"
                    if ($num === '') {
                        $num = '0';
                    }

                    $num = str_pad($num, 2, '0', STR_PAD_LEFT); // final padded number

                    if (!isset($numbersCount[$num])) {
                        $numbersCount[$num] = [
                            'number' => $num,
                            'user_count' => 0,
                            'total_amount' => 0,
                            'users' => [],
                            'game_name' => $game->name,
                            'game_id' => $game->id,
                        ];
                    }

                    $numbersCount[$num]['user_count']++;
                    $numbersCount[$num]['total_amount'] += $amount;

                    $numbersCount[$num]['users'][] = (object)[
                        'fullname' => $ticket->user->fullname,
                        'email' => $ticket->user->email,
                        'ticket_number' => $num,
                        'id' => $ticket->id,
                        'amount' => $amount,
                        'game_name' => $game->name,
                    ];
                }
            }

            $numberStats = array_merge($numberStats, array_values($numbersCount));
        }

        return view('admin.gameticket.list', compact('pageTitle', 'numberStats', 'allGames'));
    }

public function setWinner(Request $request, $id)
{
    $request->validate([
        'winning_numbers' => 'required|array|size:1',
    ]);

    $game = Game::with('tickets.user', 'winner')->findOrFail($id);

    // Prevent duplicate winner entry
    if ($game->winner) {
        return back()->withErrors(['error' => 'Winner already set for this game.']);
    }

    $winningNumber = str_pad($request->winning_numbers[0], 2, '0', STR_PAD_LEFT);

    $winnerCreated = false;

    foreach ($game->tickets as $ticket) {
        $ticketNumbers = json_decode($ticket->number, true);

        if (is_string($ticketNumbers)) {
            $ticketNumbers = json_decode($ticketNumbers, true);
        }

        if (!is_array($ticketNumbers)) {
            $ticketNumbers = [$ticketNumbers];
        }

        $ticketNumbers = array_map(function ($num) {
            $num = preg_replace('/\D/', '', $num);
            return str_pad($num, 2, '0', STR_PAD_LEFT);
        }, $ticketNumbers);

        $matched = in_array($winningNumber, $ticketNumbers);

        $ticket->is_winner = $matched ? 1 : 0;
        $ticket->save();

        if ($matched) {
            Winner::create([
                'game_id'        => $game->id,
                'ticket_id'      => $ticket->id,
                'user_id'        => $ticket->user_id,
                'winning_numbers' => $winningNumber,
                'winning_prize'  => $game->winning_amount,
            ]);
        }
    }

    $notify[] = ['success', 'Winner saved and tickets marked successfully!'];
    return back()->withNotify($notify);
}

}
