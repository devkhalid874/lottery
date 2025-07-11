<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Winner;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GameTicketController extends Controller
{

    public function buy(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:games,id',
            'numbers' => 'required|array|min:1|max:6',
        ]);

        $user = auth()->user();
        $game = Game::with('winner')->findOrFail($request->game_id); // eager load winner
        $ticketPrice = $game->ticket_price;
        $numbers = $request->numbers;

        if (count($numbers) === 0) {
            return response()->json(['success' => false, 'message' => 'No numbers selected']);
        }

        $now = now()->format('H:i:s');

        if ($now < $game->open_time || $now > $game->close_time) {
            return response()->json([
                'success' => false,
                'message' => "This game is only open between " . date('g:i A', strtotime($game->open_time)) . " To " . date('g:i A', strtotime($game->close_time)) . ". Please try during that time."
            ]);
        }

        $totalCost = $ticketPrice * count($numbers);

        if ($user->balance < $totalCost) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance']);
        }

        $user->balance -= $totalCost;
        $user->save();

        foreach ($numbers as $number) {
            $number = trim($number);
            $isWinner = 0;

            $normalizedTicketNumber = str_pad(preg_replace('/\D/', '', $number), 2, '0', STR_PAD_LEFT);

            if ($game->winner && $game->winner->winning_numbers === $normalizedTicketNumber) {
                $isWinner = 1;
            }

            Ticket::create([
                'user_id'   => $user->id,
                'game_id'   => $game->id,
                'number'    => $number,
                'amount'    => $ticketPrice,
                'is_winner' => $isWinner,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tickets purchased successfully',
        ]);
    }



    public function history()
    {
        $user = auth()->user();

        $tickets = Ticket::with('game')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);
        $pageTitle = "Ticket History";
        return view('Template::user.gametickets', compact('tickets', 'pageTitle'));
    }
}
