<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Lib\HyipLab;
use App\Models\AdminNotification;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\WithdrawMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WithdrawController extends Controller
{
    public function withdrawMethod()
    {
        $withdrawMethod = WithdrawMethod::active()->get();

        $isHoliday            = HyipLab::isHoliDay(now()->toDateTimeString(), gs());
        $nextWorkingDay       = now()->toDateString();
        $nextWorkingDayRemSec = 0;

        if ($isHoliday && !gs()->holiday_withdraw) {
            $nextWorkingDay       = HyipLab::nextWorkingDay(24);
            $nextWorkingDay       = Carbon::parse($nextWorkingDay)->toDateString();
            $nextWorkingDayRemSec = abs(Carbon::parse($nextWorkingDay)->diffInSeconds());
        }

        $notify[] = 'Withdrawals methods';
        return responseSuccess('withdraw_methods', $notify, [
            'next_working_day'         => $nextWorkingDay,
            'is_holiday'               => $isHoliday,
            'next_working_day_rem_sec' => $nextWorkingDayRemSec,
            'withdrawMethod'           => $withdrawMethod,
            'imagePath' => getFilePath('withdrawMethod')
        ]);
    }

    public function withdrawStore(Request $request)
    {
        $isHoliday = HyipLab::isHoliDay(now()->toDateTimeString(), gs());
        if ($isHoliday && !gs()->holiday_withdraw) {
            $notify[] = 'Today is holiday. You\'re unable to withdraw today';
            return response()->json([
                'remark'  => 'holiday',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'method_code' => 'required',
            'amount'      => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $method = WithdrawMethod::where('id', $request->method_code)->active()->first();
        if (!$method) {
            $notify[] = 'Withdraw method not found.';
            return responseError('validation_error', $notify);
        }

        $user = auth()->user();
        if ($request->amount < $method->min_limit) {
            $notify[] = 'Your requested amount is smaller than minimum amount';
            return responseError('validation_error', $notify);
        }
        if ($request->amount > $method->max_limit) {
            $notify[] = 'Your requested amount is larger than maximum amount';
            return responseError('validation_error', $notify);
        }

        if ($request->amount > $user->interest_wallet) {
            $notify[] = 'You do not have sufficient balance for withdraw.';
            return responseError('validation_error', $notify);
        }

        $charge      = $method->fixed_charge + ($request->amount * $method->percent_charge / 100);
        $afterCharge = $request->amount - $charge;

        if ($afterCharge <= 0) {
            $notify[] = 'Withdraw amount must be sufficient for charges';
            return responseError('validation_error', $notify);
        }

        $finalAmount = $afterCharge * $method->rate;

        $withdraw               = new Withdrawal();
        $withdraw->method_id    = $method->id; // wallet method ID
        $withdraw->user_id      = $user->id;
        $withdraw->amount       = $request->amount;
        $withdraw->currency     = $method->currency;
        $withdraw->rate         = $method->rate;
        $withdraw->charge       = $charge;
        $withdraw->final_amount = $finalAmount;
        $withdraw->after_charge = $afterCharge;
        $withdraw->trx          = getTrx();
        $withdraw->save();

        $notify[] = 'Withdraw request created';
        return responseSuccess('withdraw_request_created', $notify, [
            'trx'           => $withdraw->trx,
            'withdraw_data' => $withdraw,
            'form'          => $method->form->form_data
        ]);
    }

    public function withdrawSubmit(Request $request) 
    {
        $isHoliday = HyipLab::isHoliDay(now()->toDateTimeString(), gs());
        if ($isHoliday && !gs()->holiday_withdraw) {
            $notify[] = 'Today is holiday. You\'re unable to withdraw today';
            return responseError('holiday', $notify);
        }
        
        $validator = Validator::make($request->all(), [
            'trx' => 'required',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $withdraw = Withdrawal::with('method', 'user')->where('trx', $request->trx)->where('status', Status::PAYMENT_INITIATE)->orderBy('id', 'desc')->first();
        if (!$withdraw) {
            $notify[] = 'Withdrawal request not found';
            return responseError('validation_error', $notify);
        }

        $method = $withdraw->method;

        if ($method->status == Status::DISABLE) {
            $notify[] = 'Withdraw method not found.';
            return responseError('validation_error', $notify);
        }

        $formData = $method->form->form_data;

        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);

        $validator = Validator::make($request->all(), $validationRule);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $userData = $formProcessor->processFormData($request, $formData);

        $user = auth()->user();
        if ($user->ts) {
            if (!$request->authenticator_code) {
                $notify[] = 'Google authentication is required';
                return responseError('validation_error', $notify);
            }
            $response = verifyG2fa($user, $request->authenticator_code);
            if (!$response) {
                $notify[] = 'Wrong verification code';
                return responseError('validation_error', $notify);
            }
        }

        if ($withdraw->amount > $user->interest_wallet) {
            $notify[] = 'Your request amount is larger then your current balance';
            return responseError('validation_error', $notify);
        }

        $withdraw->status               = Status::PAYMENT_PENDING;
        $withdraw->withdraw_information = $userData;
        $withdraw->save();
        $user->interest_wallet -= $withdraw->amount;
        $user->save();

        $transaction               = new Transaction();
        $transaction->user_id      = $withdraw->user_id;
        $transaction->amount       = $withdraw->amount;
        $transaction->post_balance = $user->interest_wallet;
        $transaction->charge       = $withdraw->charge;
        $transaction->trx_type     = '-';
        $transaction->details      = 'Withdraw request via ' . $withdraw->method->name;
        $transaction->trx          = $withdraw->trx;
        $transaction->remark       = 'withdraw';
        $transaction->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $user->id;
        $adminNotification->title     = 'New withdraw request from ' . $user->username;
        $adminNotification->click_url = urlPath('admin.withdraw.data.details', $withdraw->id);
        $adminNotification->save();

        notify($user, 'WITHDRAW_REQUEST', [
            'method_name'     => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount'   => showAmount($withdraw->final_amount, currencyFormat: false),
            'amount'          => showAmount($withdraw->amount, currencyFormat: false),
            'charge'          => showAmount($withdraw->charge, currencyFormat: false),
            'rate'            => showAmount($withdraw->rate, currencyFormat: false),
            'trx'             => $withdraw->trx,
            'post_balance'    => showAmount($user->interest_wallet, currencyFormat: false),
        ]);

        $notify[] = 'Withdraw request sent successfully';
        return responseSuccess('withdraw_confirmed', $notify);
    }

    public function withdrawLog(Request $request)
    {
        $withdraws = Withdrawal::where('user_id', auth()->id());
        if ($request->search) {
            $withdraws = $withdraws->where('trx', $request->search);
        }
        $withdraws = $withdraws->where('status', '!=', Status::PAYMENT_INITIATE)->with('method')->orderBy('id', 'desc')->apiQuery();

        $notify[] = 'Withdrawals';
        return responseSuccess('withdrawals', $notify, [
            'withdrawals' => $withdraws
        ]);
    }
}
