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

    public function gameList(Request $request)
    {
        $pageTitle = "Game List";
        $allGames = Game::all();

        $query = Game::with(['tickets' => function ($q) use ($request) {
            if ($request->date) {
                $q->whereDate('created_at', $request->date);
            }
        }, 'tickets.user']);

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
            'winning_numbers' => 'required|array|min:1|max:6',
        ]);

        $game = Game::with('tickets.user')->findOrFail($id);

        // Normalize winning numbers
        $winningNumbers = array_map(function ($num) {
            $num = ltrim((string) $num, '0');
            return str_pad($num === '' ? '0' : $num, 2, '0', STR_PAD_LEFT);
        }, $request->winning_numbers);

        $game->winning_numbers = $winningNumbers;
        $game->save();

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

            $matched = count(array_intersect($winningNumbers, $ticketNumbers)) > 0;

            logger()->debug('Checking Ticket:', [
                'ticket_id' => $ticket->id,
                'raw_ticket_number' => $ticket->number,
                'decoded_ticket_numbers' => $ticketNumbers,
                'winning_numbers' => $winningNumbers,
                'intersect' => array_intersect($winningNumbers, $ticketNumbers),
                'matched' => $matched,
            ]);

            $ticket->is_winner = $matched ? 1 : 0;
            $ticket->save();
        }


        $notify[] = ['success', 'Winning numbers announced and winning tickets marked!'];
        return back()->withNotify($notify);
    }
}
