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

        if (!is_array($numbers) || count($numbers) === 0) {
            return response()->json(['success' => false, 'message' => 'No numbers selected']);
        }

        $totalCost = $ticketPrice * count($numbers);

        if ($user->deposit_wallet < $totalCost) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance']);
        }

        $user->deposit_wallet -= $totalCost;
        $user->save();

      
         Ticket::create([
    'user_id'   => $user->id,
    'game_id'   => $game->id,
    'number'    => json_encode($numbers), // ✅ all numbers in one array
    'ticket_id' => strtoupper(Str::uuid()),
    'amount'    => $ticketPrice * count($numbers),
]);


        return response()->json(['success' => true, 'message' => 'Ticket(s) purchased successfully']);
    }

      public function history()
    {
        $user = auth()->user();

        $tickets = Ticket::with('game')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);
            $pageTitle = "Ticket History";
        return view('Template::user.gametickets', compact('tickets','pageTitle'));
    }
}
