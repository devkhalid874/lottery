<?php

namespace App\Http\Controllers;

use App\Models\Game;
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
        $game = Game::findOrFail($request->game_id);
        $ticketPrice = $game->ticket_price;
        $numbers = $request->numbers;

        if (count($numbers) === 0) {
            return response()->json(['success' => false, 'message' => 'No numbers selected']);
        }

        // ✅ Check if current time is within open and close time
        $now = now()->format('H:i:s'); // current server time in 24-hour format

        if ($now < $game->open_time || $now > $game->close_time) {
            return response()->json([
                'success' => false,
                'message' => "This game is only open between " . date('g:i A', strtotime($game->open_time)) . " and " . date('g:i A', strtotime($game->close_time)) . ". Please try during that time."
            ]);
        }

        $totalCost = $ticketPrice * count($numbers);

        if ($user->balance < $totalCost) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance']);
        }

        // Deduct balance once
        $user->balance -= $totalCost;
        $user->save();

        // Create one ticket per number
        foreach ($numbers as $number) {
            Ticket::create([
                'user_id'   => $user->id,
                'game_id'   => $game->id,
                'number'    => $number,
                'amount'    => $ticketPrice,
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
