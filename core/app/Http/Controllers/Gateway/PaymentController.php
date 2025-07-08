<?php

namespace App\Http\Controllers\Gateway;

use App\Lib\HyipLab;
use App\Models\Plan;
use App\Models\User;
use App\Models\Deposit;
use App\Constants\Status;
use App\Lib\FormProcessor;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\GatewayCurrency;
use App\Models\AdminNotification;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    public function deposit()
    {

        // $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
        //     $gate->where('status', Status::ENABLE);
        // })->with('method')->orderby('name')->get();
        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE)
                ->whereIn('alias', ['stripe', 'stripejs', 'stripev3']); // ✅ Add all supported stripe aliases here
        })->with('method')->orderby('name')->get();

        $pageTitle = 'Deposit Methods';
        return view('Template::user.payment.deposit', compact('gatewayCurrency', 'pageTitle'));
    }

    public function depositInsert(Request $request)
    {
        $request->validate([
            'amount'   => 'required|numeric|gt:0',
            'gateway'  => 'required',
            'currency' => 'required',
        ]);

        $gate = GatewayCurrency::where('method_code', 103)
            ->where('currency', $request->currency)
            ->first();

        if (!$gate) {
            return back()->withErrors(['Invalid gateway']);
        }

        if ($gate->min_amount > $request->amount || $gate->max_amount < $request->amount) {
            return back()->withErrors(['Please follow deposit limit']);
        }

        self::insertDeposit($gate, $request->amount);

        return back()->with('success', 'Balance added to your account.');
    }


    public static function insertDeposit($gateway, $amount, $investPlan = null, $compoundTimes = 0)
    {
        $user        = auth()->user();
        $charge      = $gateway->fixed_charge + ($amount * $gateway->percent_charge / 100);
        $payable     = $amount + $charge;
        $finalAmount = $payable * $gateway->rate;

        $data = new Deposit();
        if ($investPlan) {
            $data->plan_id = $investPlan->id;
        }
        $data->user_id         = $user->id;
        $data->method_code     = $gateway->method_code;
        $data->method_currency = strtoupper($gateway->currency);
        $data->amount          = $amount;
        $data->charge          = $charge;
        $data->rate            = $gateway->rate;
        $data->final_amount    = $finalAmount;
        $data->btc_amount      = 0;
        $data->btc_wallet      = "";
        $data->trx             = getTrx();
        $data->status          = Status::PAYMENT_SUCCESS; // Mark as completed
        $data->success_url = route('user.deposit.history');
        $data->failed_url = route('user.deposit.history');
        $data->compound_times  = $compoundTimes ?? 0;

        $data->save();
        self::userDataUpdate($data);
        return $data;
    }

    public function appDepositConfirm($hash)
    {
        try {
            $id = decrypt($hash);
        } catch (\Exception $ex) {
            abort(404);
        }
        $data = Deposit::where('id', $id)->where('status', Status::PAYMENT_INITIATE)->orderBy('id', 'DESC')->firstOrFail();
        $user = User::findOrFail($data->user_id);
        auth()->login($user);
        session()->put('Track', $data->trx);
        return to_route('user.deposit.confirm');
    }

    public function depositConfirm()
    {
        $track = session()->get('Track');

        $deposit = Deposit::where('trx', $track)
            ->where('status', Status::PAYMENT_INITIATE)
            ->orderBy('id', 'DESC')
            ->with('gateway')
            ->firstOrFail();

        if ($deposit->method_code >= 1000) {
            return to_route('user.deposit.manual.confirm');
        }

        $dirName = $deposit->gateway->alias;
        $new     = __NAMESPACE__ . '\\' . $dirName . '\\ProcessController';

        $data = $new::process($deposit);
        $data = json_decode($data);

        if (isset($data->error)) {
            $notify[] = ['error', $data->message];
            return back()->withNotify($notify);
        }

        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        // ✅ Stripe session save (if exists)
        if (@$data->session) {
            $deposit->btc_wallet = $data->session->id;
            $deposit->save();
        }


        $pageTitle = 'Payment Confirm';
        return view("Template::$data->view", compact('data', 'pageTitle', 'deposit'));
    }



    public static function userDataUpdate($deposit, $isManual = null)
    {
        if ($deposit->status == Status::PAYMENT_INITIATE || $deposit->status == Status::PAYMENT_PENDING || $deposit->status == Status::PAYMENT_SUCCESS) {
            // ✅ Avoid updating wallet again if already processed
            if (!$deposit->wallet_added) { // Optional: Add this check if you want to prevent double updates
                $user = User::find($deposit->user_id);
                $user->balance += $deposit->amount;
                $user->save();

                $deposit->status = Status::PAYMENT_SUCCESS;
                $deposit->save();

                // Save transaction
                $methodName = $deposit->methodName();

                $transaction               = new Transaction();
                $transaction->user_id      = $deposit->user_id;
                $transaction->amount       = $deposit->amount;
                $transaction->post_balance = $user->balance;
                $transaction->charge       = $deposit->charge;
                $transaction->trx_type     = '+';
                $transaction->details      = 'Deposit Via ' . $methodName;
                $transaction->trx          = $deposit->trx;
                $transaction->wallet_type  = 'balance';
                $transaction->remark       = 'deposit';
                $transaction->save();

                // Notify admin
                if (!$isManual) {
                    $adminNotification            = new AdminNotification();
                    $adminNotification->user_id   = $user->id;
                    $adminNotification->title     = 'Deposit successful via ' . $methodName;
                    $adminNotification->click_url = urlPath('admin.deposit.successful');
                    $adminNotification->save();
                }

                // Commission
                $general = gs();
                if ($general->deposit_commission) {
                    HyipLab::levelCommission($user, $deposit->amount, 'deposit_commission', $deposit->trx, $general);
                }

                // Plan logic
                if ($deposit->plan_id) {
                    $plan = Plan::with('timeSetting')->whereHas('timeSetting', function ($time) {
                        $time->where('status', Status::ENABLE);
                    })->where('status', Status::ENABLE)->findOrFail($deposit->plan_id);
                    $hyip = new HyipLab($user, $plan);
                    $hyip->invest($deposit->amount, 'balance', $deposit->compound_times);
                }

                notify($user, $isManual ? 'DEPOSIT_APPROVE' : 'DEPOSIT_COMPLETE', [
                    'method_name'     => $methodName,
                    'method_currency' => $deposit->method_currency,
                    'method_amount'   => showAmount($deposit->final_amount, currencyFormat: false),
                    'amount'          => showAmount($deposit->amount, currencyFormat: false),
                    'charge'          => showAmount($deposit->charge, currencyFormat: false),
                    'rate'            => showAmount($deposit->rate, currencyFormat: false),
                    'trx'             => $deposit->trx,
                    'post_balance'    => showAmount($user->balance, currencyFormat: false),
                ]);
            }
        }
    }


    public function manualDepositConfirm()
    {
        $track = session()->get('Track');
        $data  = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
        abort_if(!$data, 404);
        if ($data->method_code > 999) {
            $pageTitle = 'Confirm Deposit';
            $method    = $data->gatewayCurrency();
            $gateway   = $method->method;
            return view('Template::user.payment.manual', compact('data', 'pageTitle', 'method', 'gateway'));
        }
        abort(404);
    }

    public function manualDepositUpdate(Request $request)
    {
        $track = session()->get('Track');
        $data  = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
        abort_if(!$data, 404);
        $gatewayCurrency = $data->gatewayCurrency();
        $gateway         = $gatewayCurrency->method;
        $formData        = $gateway->form->form_data;

        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $userData = $formProcessor->processFormData($request, $formData);

        $data->detail = $userData;
        $data->status = Status::PAYMENT_PENDING;
        $data->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $data->user->id;
        $adminNotification->title     = 'Deposit request from ' . $data->user->username;
        $adminNotification->click_url = urlPath('admin.deposit.details', $data->id);
        $adminNotification->save();

        notify($data->user, 'DEPOSIT_REQUEST', [
            'method_name'     => $data->gatewayCurrency()->name,
            'method_currency' => $data->method_currency,
            'method_amount'   => showAmount($data->final_amount, currencyFormat: false),
            'amount'          => showAmount($data->amount, currencyFormat: false),
            'charge'          => showAmount($data->charge, currencyFormat: false),
            'rate'            => showAmount($data->rate, currencyFormat: false),
            'trx'             => $data->trx,
        ]);

        $notify[] = ['success', 'You have deposit request has been taken'];
        return to_route('user.deposit.history')->withNotify($notify);
    }
}
