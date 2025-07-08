<?php

namespace App\Http\Controllers\Admin;

use App\Lib\HyipLab;
use App\Models\Game;
use App\Models\Invest;
use App\Models\Ticket;
use App\Constants\Status;
use App\Models\GameSetting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class GameController extends Controller
{
    public function index()
    {
        $pageTitle = "Game";
        $plans     = Game::with('gameSetting')->orderBy('id', 'desc')->get();
        $times     = GameSetting::active()->get();
        return view('admin.game.index', compact('pageTitle', 'plans', 'times'));
    }

    public function store(Request $request)
    {
        $this->validation($request);

        $plan = new Game();
        $this->saveData($plan, $request);

        $notify[] = ['success', 'Plan added successfully'];
        return back()->withNotify($notify);
    }

    public function update(Request $request, $id)
    {
        $this->validation($request);
        $plan = Game::findOrFail($id);
        $this->saveData($plan, $request);

        $notify[] = ['success', 'Plan updated successfully'];
        return back()->withNotify($notify);
    }

    protected function saveData($plan, $request)
    {
        $plan->name              = $request->name;
        // $plan->minimum           = $request->minimum ?? 0;
        // $plan->maximum           = $request->maximum ?? 0;
        // $plan->fixed_amount      = $request->amount ?? 0;
        // $plan->interest          = $request->interest;  
        // $plan->interest_type     = $request->interest_type == 1 ? 1 : 0;
        // $plan->time_setting_id   = $request->time;
        // $plan->capital_back      = $request->capital_back ?? 0;
        // $plan->lifetime          = $request->return_type == 1 ? 1 : 0;
        // $plan->repeat_time       = $request->repeat_time ?? 0;
        // $plan->compound_interest = $request->compound_interest ? Status::YES : Status::NO;
        // $plan->hold_capital      = $request->hold_capital ? Status::YES : Status::NO;

        $plan->ticket_price      = $request->ticket_price;
        $plan->range_start       = $request->range_start;
        $plan->range_end         = $request->range_end;
        $plan->open_time         = $request->open_time;
        $plan->close_time        = $request->close_time;
        $plan->auto_close        = $request->auto_close ? 1 : 0;
        $plan->featured          = $request->featured ? Status::YES : Status::NO;


        $plan->save();
    }

    protected function validation($request)
    {
        $request->validate([
            'name'          => 'required',
            // 'invest_type'   => 'required|in:1,2',
            // 'interest_type' => 'required|in:1,2',
            // 'interest'      => 'required|numeric|gt:0',
            // 'time'          => 'required|integer|gt:0',
            // 'return_type'   => 'required|integer|in:1,0',
            // 'minimum'       => 'nullable|required_if:invest_type,1|gt:0',
            // 'maximum'       => 'nullable|required_if:invest_type,1|gt:minimum',
            // 'amount'        => 'nullable|required_if:invest_type,2|gt:0',
            // 'repeat_time'   => 'nullable|required_if:return_type,2|integer|gt:0',
            // 'capital_back'  => 'nullable|required_if:return_type,2|in:1,0',

            // new fields 
            'ticket_price'  => 'required|numeric|gt:0',
            'range_start' => 'required|string|regex:/^\d{2}$/',
            'range_end' => 'required|string|regex:/^\d{2}$/|gte:range_start',
            'open_time'     => 'required|date_format:H:i',
            'close_time'    => 'required|date_format:H:i',
            'auto_close'    => 'nullable|in:1',
            'featured'     => 'nullable|boolean',
        ]);

        if ($request->compound_interest && ((!$request->capital_back && !$request->return_type) || $request->interest_type == 2)) {
            throw ValidationException::withMessages(['error' => 'For compound interest, a lifetime plan or capital return and a percentage-based interest rate are required.']);
        }

        if ($request->hold_capital && !$request->capital_back) {
            throw ValidationException::withMessages(['error' => 'When hold capital is enabled, capital back is required.']);
        }
    }

     // Make Winner

    public function setWinner(Request $request, $gameId)
    {
        $request->validate([
            'winning_numbers' => 'required|array|min:1|max:6',
        ]);

        $game = Game::findOrFail($gameId);
        $game->winning_numbers = json_encode($request->winning_numbers);
        $game->save();

        $winningNumbers = $request->winning_numbers;

        // Fetch all matching tickets
        $tickets = Ticket::where('game_id', $gameId)->get();

        foreach ($tickets as $ticket) {
            foreach ($ticket->number as $num) {
                if (in_array($num, $winningNumbers)) {
                    // Mark as winner
                    $ticket->is_winner = true;
                    $ticket->save();

                    // Credit wallet
                    $ticket->user->wallet_balance += 100; // customize amount
                    $ticket->user->save();

                    // Send notification
                    // $ticket->user->notify(new WinnerAnnounced($ticket));
                    break; // no need to check further if one number matches
                }
            }
        }

        return back()->with('success', 'Winners have been processed and credited.');
    }


    public function status($id)
    {
        return Game::changeStatus($id);
    }

    public function cancelInvest(Request $request)
    {
        $request->validate([
            'invest_id' => 'required|integer',
            'action'    => 'required|in:1,2,3,4',
        ]);

        $invest = Invest::with('user')->where('status', Status::INVEST_RUNNING)->findOrFail($request->invest_id);

        if ($request->action == 1 || $request->action == 2) {
            HyipLab::capitalReturn($invest, $invest->wallet_type);
        }

        if ($request->action == 2 || $request->action == 4) {
            $this->interestBack($invest);
        }

        $invest->status = Status::INVEST_CANCELED;
        $invest->save();

        $notify[] = ['success', 'Investment canceled successfully'];
        return back()->withNotify($notify);
    }


    private function interestBack($invest)
    {
        $user = $invest->user;
        $totalPaid = $invest->paid;

        if ($totalPaid <= $user->balance) {
            $user->balance -= $totalPaid;
            $this->createTransaction($user->id, $totalPaid, $user->balance, 'balance');
        } elseif ($totalPaid <= $user->balance + $user->balance) {
            $user->balance -= ($totalPaid - $user->balance);
            $this->createTransaction($user->id, $totalPaid - $user->balance, $user->balance, 'balance');
            $this->createTransaction($user->id, $user->balance, 0, 'balance');
            $user->balance = 0;
        } else {
            $user->balance -= ($totalPaid - $user->balance);
            $this->createTransaction($user->id, $totalPaid - $user->balance, $user->balance, 'balance');
            $this->createTransaction($user->id, $user->balance, 0, 'balance');
            $user->balance = 0;
        }
    }

    private function createTransaction($userId, $amount, $postBalance, $wallet)
    {
        $transaction               = new Transaction();
        $transaction->user_id      = $userId;
        $transaction->amount       = $amount;
        $transaction->post_balance = $postBalance;
        $transaction->charge       = 0;
        $transaction->trx_type     = '-';
        $transaction->details      = 'Interest return for investment canceled';
        $transaction->trx          = getTrx();
        $transaction->wallet_type  = $wallet;
        $transaction->remark       = 'interest_return';
        $transaction->save();
    }
}
