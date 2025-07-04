<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Controller;
use App\Lib\HyipLab;
use App\Models\GatewayCurrency;
use App\Models\Invest;
use App\Models\Plan;
use App\Models\Pool;
use App\Models\PoolInvest;
use App\Models\ScheduleInvest;
use App\Models\Staking;
use App\Models\StakingInvest;
use App\Models\Transaction;
use App\Models\UserRanking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvestController extends Controller
{
    public function invest(Request $request)
    {
        $myInvests      = Invest::with('plan')->where('user_id', auth()->id());
        $notify         = 'My Invests';
        $modifiedInvest = [];

        if (request()->type == 'active') {
            $myInvests = $myInvests->where('status', Status::INVEST_RUNNING);
            $notify    = 'My Active Invests';
        } elseif (request()->type == 'closed') {
            $myInvests = $myInvests->where('status', Status::INVEST_CLOSED);
            $notify    = 'My Closed Invests';
        }

        if($request->is_web){
            return response()->json([
                'remark'  => 'my_invest',
                'status'  => 'success',
                'message' => ['success' => $notify],
                'data'    => [
                    'invests' => $myInvests->paginate(getPaginate()),
                ],
            ]);
        }

        $myInvests = $myInvests->apiQuery();

        if (!request()->calc) {
            $modifyInvest = [];

            foreach ($myInvests as $invest) {

                if ($invest->last_time) {
                    $start = $invest->last_time;
                } else {
                    $start = $invest->created_at;
                }

                $modifyInvest[] = [
                    'id'                => $invest->id,
                    'user_id'           => $invest->user_id,
                    'plan_id'           => $invest->plan_id,
                    'amount'            => $invest->amount,
                    'interest'          => $invest->interest,
                    'should_pay'        => $invest->should_pay,
                    'paid'              => $invest->paid,
                    'period'            => $invest->period,
                    'hours'             => $invest->hours,
                    'time_name'         => $invest->time_name,
                    'return_rec_time'   => $invest->return_rec_time,
                    'next_time'         => $invest->next_time,
                    'next_time_percent' => getAmount(diffDatePercent($start, $invest->next_time)),
                    'status'            => $invest->status,
                    'capital_status'    => $invest->capital_status,
                    'capital_back'      => $invest->capital_back,
                    'wallet_type'       => $invest->wallet_type,
                    'plan'              => $invest->plan,

                    'diffDatePercent'              => $invest->diffDatePercent,
                    'diffInSeconds'              => $invest->diffInSeconds,
                    'isShowDiffInSeconds'              => $invest->isShowDiffInSeconds,
                    'isEligibleCapitalBack'              => $invest->isEligibleCapitalBack,
                ];
            }

            if (request()->take) {
                $modifiedInvest = [
                    'data' => $modifyInvest,
                ];
            } else {
                $modifiedInvest = [
                    'data'      => $modifyInvest,
                    'next_page' => $myInvests->nextPageUrl(),
                ];
            }

        } else {
            $modifiedInvest = $myInvests;
        }

        return response()->json([
            'remark'  => 'my_invest',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'invests' => $modifiedInvest,
            ],
        ]);
    }

    public function details($id)
    {
        $invest = Invest::with('user', 'plan')->where('user_id', auth()->id())->find($id);

        if (!$invest) {
            $notify[] = 'Investment not found';
            return response()->json([
                'remark'  => 'not_found',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $transactions = Transaction::where('invest_id', $invest->id)->orderBy('id', 'desc')->paginate(getPaginate());

        $notify[] = 'Investment details';
        return response()->json([
            'remark'  => 'investment_details',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'invest'       => $invest,
                'transactions' => $transactions,
                'eligible_capital_back' => $invest->eligibleCapitalBack(),
            ],
        ]);
    }

    public function storeInvest(Request $request)
    {
        $validator = $this->validation($request);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $amount = $request->amount;
        $wallet = $request->wallet;
        $user   = auth()->user();

        $plan = Plan::with('timeSetting')->whereHas('timeSetting', function ($time) {
            $time->where('status', Status::ENABLE);
        })->where('status', Status::ENABLE)->find($request->plan_id);

        if (!$plan) {
            $notify[] = 'Plan not found';
            return response()->json([
                'remark'  => 'not_found',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $planValidation = $this->planInfoValidation($plan, $request);

        if (is_array($planValidation)) {
            $notify[] = current($planValidation);
            return response()->json([
                'remark'  => key($planValidation),
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        if ($request->invest_time == 'schedule' && gs('schedule_invest')) {
            $request->merge(['wallet_type'=> $request->wallet]);
            HyipLab::saveScheduleInvest($request);
            $notify[] = 'Invest scheduled successfully'; 
            return response()->json([
                'remark'  => 'invest_scheduled',
                'status'  => 'success',
                'message' => ['success' => $notify],
            ]);
        }

        if ($wallet != 'deposit_wallet' && $wallet != 'interest_wallet') {
            $gate = GatewayCurrency::whereHas('method', function ($gate) {
                $gate->where('status', Status::ENABLE);
            })->find($wallet);

            if (!$gate) {
                $notify[] = 'Gateway not found';
                return response()->json([
                    'remark'  => 'not_found',
                    'status'  => 'error',
                    'message' => ['error' => $notify],
                ]);
            }

            if ($gate->min_amount > $amount || $gate->max_amount < $amount) {
                $notify[] = 'Please follow deposit limit';
                return response()->json([
                    'remark'  => 'limit_error',
                    'status'  => 'error',
                    'message' => ['error' => $notify],
                ]);
            }

            $deposit = PaymentController::insertDeposit($gate, $amount, $plan, $request->compound_interest);
            $notify[] = 'Invest deposit successfully';

            if ($request->is_web && $deposit->gateway->code < 1000) {
                $dirName = $deposit->gateway->alias;
                $new = 'App\\Http\\Controllers\\Gateway\\' . $dirName . '\\ProcessController';
    
                $gatewayData = $new::process($deposit);
                $gatewayData = json_decode($gatewayData);
    
                // for Stripe V3
                if (@$deposit->session) {
                    $deposit->btc_wallet = $gatewayData->session->id;
                    $deposit->save();
                }
    
                return responseSuccess('deposit_inserted', $notify, [
                    'deposit' => $deposit,
                    'gateway_data' => $gatewayData
                ]);
            }else{
                return response()->json([
                    'remark'  => 'deposit_success',
                    'status'  => 'success',
                    'message' => ['success' => $notify],
                    'data'    => [
                        'redirect_url' => route('deposit.app.confirm', encrypt($deposit->id)),
                        'deposit' => $deposit
                    ],
                ]); 
            }

        }

        if ($user->$wallet < $amount) {
            $notify[] = 'Insufficient balance';
            return response()->json([
                'remark'  => 'insufficient_balance',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $hyip = new HyipLab($user, $plan);
        $hyip->invest($amount, $wallet, $request->compound_interest);

        $notify[] = 'Invested to plan successfully';
        return response()->json([
            'remark'  => 'invested',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }

    private function validation($request)
    {
        $validationRule = [
            'amount'            => 'required|numeric|gt:0',
            'plan_id'           => 'required|integer',
            'wallet'            => 'required',
            'compound_interest' => 'nullable|numeric|min:0',
        ];

        $general = gs();

        if ($general->schedule_invest) {
            $validationRule['invest_time'] = 'required|in:invest_now,schedule';
        }

        if ($request->invest_time == 'schedule') {
            $validationRule['wallet']         = 'required|in:deposit_wallet,interest_wallet';
            $validationRule['schedule_times'] = 'required|integer|min:1';
            $validationRule['hours']          = 'required|integer|min:1';
        }

        $validator = Validator::make($request->all(), $validationRule, [
            'wallet.in' => 'For schedule invest wallet must be deposit wallet or interest wallet',
        ]);

        return $validator;
    }

    private function planInfoValidation($plan, $request)
    {
        if ($request->compound_interest) {
            if (!$plan->compound_interest) {
                return ['not_available' => 'Compound interest optional is not available for this plan.'];
            }

            if ($plan->repeat_time && $plan->repeat_time <= $request->compound_interest) {
                return ['limit_exceeded' => 'Compound interest times must be fewer than repeat times.'];
            }
        }

        if ($plan->fixed_amount > 0) {
            if ($request->amount != $plan->fixed_amount) {
                return ['limit_error' => 'Please check the investment limit'];
            }
        } else {
            if ($request->amount < $plan->minimum || $request->amount > $plan->maximum) {
                return ['limit_error' => 'Please check the investment limit'];
            }
        }
        return 'no_plan_validation_error_found';
    }

    public function manageCapital(Request $request)
    {
        $request->validate([
            'invest_id' => 'required|integer',
            'capital'   => 'required|in:reinvest,capital_back',
        ]);

        $user   = auth()->user();
        $invest = Invest::with('user')->where('user_id', $user->id)->where('capital_status', 1)->where('capital_back', 0)->where('status', 0)->find($request->invest_id);

        if (!$invest) {
            $notify[] = 'Investment not found';
            return response()->json([
                'remark'  => 'not_found',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        if ($request->capital == 'capital_back') {
            HyipLab::capitalReturn($invest);
            $notify[] = 'Capital added to your wallet successfully';
            return response()->json([
                'remark'  => 'capital_added',
                'status'  => 'success',
                'message' => ['success' => $notify],
            ]);
        }

        $plan = Plan::whereHas('timeSetting', function ($timeSetting) {
            $timeSetting->where('status', Status::ENABLE);
        })->where('status', Status::ENABLE)->find($invest->plan_id);

        if (!$plan) {
            $notify[] = 'This plan currently unavailable';
            return response()->json([
                'remark'  => 'not_available',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        HyipLab::capitalReturn($invest);
        $hyip = new HyipLab($user, $plan);
        $hyip->invest($invest->amount, 'interest_wallet', $invest->compound_times);

        $notify[] = 'Reinvested to plan successfully';
        return response()->json([
            'remark'  => 'reinvest_success',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }

    public function allPlans(Request $request)
    {
        $plans = Plan::with('timeSetting')->whereHas('timeSetting', function ($time) {
            $time->where('status', Status::ENABLE);
        })->where('status', Status::ENABLE)->get();
        $modifiedPlans = [];
        $general       = gs();

        foreach ($plans as $plan) {
            if ($plan->lifetime == 0) {
                $totalReturn = 'Total ' . $plan->interest * $plan->repeat_time . ' ' . ($plan->interest_type == 1 ? '%' : $general->cur_text);

                if($request->is_web == Status::YES){
                    $totalReturn = $plan->capital_back == 1 ? $totalReturn . ' + <span class="badge badge--success">Capital</span>' : $totalReturn;
                }else{
                    $totalReturn = $plan->capital_back == 1 ? $totalReturn . ' + Capital' : $totalReturn;
                }

                $repeatTime       = 'For ' . $plan->repeat_time . ' ' . $plan->timeSetting->name;
                $interestValidity = 'Per ' . $plan->timeSetting->time . ' hours for ' . $plan->repeat_time . ' times';
            } else {
                $totalReturn      = 'Lifetime Earning';
                $repeatTime       = 'For Lifetime';
                $interestValidity = 'Per ' . $plan->timeSetting->time . ' hours for lifetime';
            }

            $modifiedPlans[] = [
                'id'                => $plan->id,
                'name'              => $plan->name,
                'minimum'           => $plan->minimum,
                'maximum'           => $plan->maximum,
                'fixed_amount'      => $plan->fixed_amount,
                'return'            => showAmount($plan->interest, currencyFormat: false) . ' ' . ($plan->interest_type == 1 ? '%' : $general->cur_text),
                'interest_duration' => 'Every ' . $plan->timeSetting->name,
                'repeat_time'       => $repeatTime,
                'total_return'      => $totalReturn,
                'interest_validity' => $interestValidity,
                'hold_capital'      => $plan->hold_capital,
                'compound_interest' => $plan->compound_interest,

                'interest' => $plan->interest,
                'interest_type' => $plan->interest_type,
                'raw_interest_type' => $plan->repeat_time,
                'capital_back' => $plan->capital_back,
            ];
        }

        $notify[] = 'All Plans';

        return response()->json([
            'remark'  => 'plan_data',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'plans' => $modifiedPlans,
            ],
        ]);
    }

    public function scheduleInvests()
    {
        $general = gs();
        if (!$general->schedule_invest) {
            $notify[] = 'Schedule invest currently not available.';
            return response()->json([
                'remark'  => 'not_available',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
        $scheduleInvests = ScheduleInvest::with('plan.timeSetting')->where('user_id', auth()->id())->orderBy('id', 'desc')->apiQuery();

        $scheduleInvests->transform(function ($scheduleInvest) use ($general) {
            $plan = $scheduleInvest['plan'];
            if ($plan->lifetime == 0) {
                $totalReturn = 'Total ' . $plan->interest * $plan->repeat_time . ' ' . ($plan->interest_type == 1 ? '%' : $general->cur_text);
                $totalReturn = $plan->capital_back == 1 ? $totalReturn . ' + Capital' : $totalReturn;

                $repeatTime       = 'For ' . $plan->repeat_time . ' ' . $plan->timeSetting->name;
                $interestValidity = 'Per ' . $plan->timeSetting->time . ' hours, ' . ' Per ' . $plan->repeat_time . ' ' . $plan->timeSetting->name;

            } else {
                $totalReturn      = 'Lifetime Earning';
                $repeatTime       = 'For Lifetime';
                $interestValidity = 'Per ' . $plan->timeSetting->time . ' hours, lifetime';
            }

            $scheduleInvest['plan']['return']            = showAmount($plan->interest, currencyFormat: false) . ' ' . ($plan->interest_type == 1 ? '%' : $general->cur_text);
            $scheduleInvest['plan']['interest_duration'] = 'Every ' . $plan->timeSetting->name;
            $scheduleInvest['plan']['total_time']        = $repeatTime;
            $scheduleInvest['plan']['total_return']      = $totalReturn;
            $scheduleInvest['plan']['interest_validity'] = $interestValidity;

            $interest                 = $plan->interest_type == 1 ? ($scheduleInvest->amount * $plan->interest) / 100 : $plan->interest;
            $scheduleReturn           = showAmount($interest) . ' every ' . $plan->timeSetting->name . ' for ' . ($plan->lifetime ? 'Lifetime' : $plan->repeat_time . ' ' . $plan->timeSetting->name) . ($plan->capital_back ? ' + Capital' : '');
            $scheduleInvest['return'] = $scheduleReturn;

            return $scheduleInvest;
        });

        $notify[] = 'Schedule Invests';

        return response()->json([
            'remark'  => 'schedule_invest',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => ['schedule_invests' => $scheduleInvests],
        ]);

    }

    public function scheduleStatus($id)
    {
        $scheduleInvest = ScheduleInvest::where('user_id', auth()->id())->find($id);
        if (!$scheduleInvest) {
            $notify[] = 'Schedule invest not found';
            return response()->json([
                'remark'  => 'not_found',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $scheduleInvest->status = !$scheduleInvest->status;
        $scheduleInvest->save();
        $notification = $scheduleInvest->status ? 'enabled' : 'disabled';

        $notify[] = "Schedule invest $notification successfully";
        return response()->json([
            'remark'  => 'status_changed',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }

    public function staking()
    {
        if (!gs('staking_option')) {
            $notify[] = 'Staking currently not available';
            return response()->json([
                'remark'  => 'not_available',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $stakings   = Staking::active()->get();
        $myStakings = StakingInvest::where('user_id', auth()->id())->orderBy('id', 'desc')->apiQuery();

        $notify[] = 'Staking List';

        return response()->json([
            'remark'  => 'staking',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'staking'     => $stakings,
                'my_stakings' => $myStakings,
            ],
        ]);

    }

    public function saveStaking(Request $request)
    {
        if (!gs('staking_option')) {
            $notify[] = 'Staking currently not available';
            return response()->json([
                'remark'  => 'not_available',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $min = getAmount(gs('staking_min_amount'));
        $max = getAmount(gs('staking_max_amount'));

        $validator = Validator::make($request->all(), [
            'duration' => 'required|integer|min:1',
            'amount'   => "required|numeric|between:$min,$max",
            'wallet'   => 'required|in:deposit_wallet,interest_wallet',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $user   = auth()->user();
        $wallet = $request->wallet;

        if ($user->$wallet < $request->amount) {
            $notify[] = 'Insufficient balance';
            return response()->json([
                'remark'  => 'insufficient_balance',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);

        }

        $staking = Staking::active()->find($request->duration);

        if (!$staking) {
            $notify[] = 'Staking not found';
            return response()->json([
                'remark'  => 'not_found',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $interest = $request->amount * $staking->interest_percent / 100;

        $stakingInvest                = new StakingInvest();
        $stakingInvest->user_id       = auth()->id();
        $stakingInvest->staking_id    = $staking->id;
        $stakingInvest->invest_amount = $request->amount;
        $stakingInvest->interest      = $interest;
        $stakingInvest->end_at        = now()->addDays($staking->days);
        $stakingInvest->save();

        $user->$wallet -= $request->amount;
        $user->save();

        $transaction               = new Transaction();
        $transaction->user_id      = $user->id;
        $transaction->amount       = $request->amount;
        $transaction->post_balance = $user->$wallet;
        $transaction->charge       = 0;
        $transaction->trx_type     = '-';
        $transaction->details      = 'Staking investment';
        $transaction->trx          = getTrx();
        $transaction->wallet_type  = $wallet;
        $transaction->remark       = 'staking_invest';
        $transaction->save();

        $notify[] = 'Staking investment added successfully';
        return response()->json([
            'remark'  => 'staking_save',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);

    }

    public function pools()
    {
        if (!gs('pool_option')) {
            $notify[] = 'Pool currently not available.';
            return response()->json([
                'remark'  => 'not_available',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $pools = Pool::active()->where('share_interest', Status::NO)->get();

        $notify[] = 'Pool List';
        return response()->json([
            'remark'  => 'pools',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => ['pools' => $pools],
        ]);

    }

    public function poolInvests()
    {
        if (!gs('pool_option')) {
            $notify[] = 'Pool currently not available.';
            return response()->json([
                'remark'  => 'not_available',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
        $poolInvests = PoolInvest::with('pool')->where('user_id', auth()->id())->orderBy('id', 'desc')->apiQuery();

        $poolInvests->transform(function ($poolInvest) {
            if ($poolInvest->pool->share_interest) {
                $totalReturn = $poolInvest->invest_amount + ($poolInvest->pool->interest * $poolInvest->invest_amount / 100);
            } else {
                $totalReturn = 'Not return yet!';
            }
            $poolInvest->total_return = $totalReturn;
            return $poolInvest;
        });

        $notify[] = 'My Pool Invests';
        return response()->json([
            'remark'  => 'pool_invests',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => ['pool_invests' => $poolInvests],
        ]);

    }

    public function savePoolInvest(Request $request)
    {
        if (!gs('pool_option')) {
            $notify[] = 'Pool currently not available.';
            return response()->json([
                'remark'  => 'not_available',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'pool_id' => 'required|integer',
            'wallet'  => 'required|in:deposit_wallet,interest_wallet',
            'amount'  => 'required|numeric|gt:0',
        ]);

        if ($validator->fails()) {
            if ($validator->fails()) {
                return response()->json([
                    'remark'  => 'validation_error',
                    'status'  => 'error',
                    'message' => ['error' => $validator->errors()->all()],
                ]);
            }

        }

        $pool = Pool::active()->find($request->pool_id);

        if (!$pool) {
            $notify[] = 'Pool not found';
            return response()->json([
                'remark'  => 'not_found',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $user   = auth()->user();
        $wallet = $request->wallet;

        if ($pool->start_date <= now()) {
            $notify[] = 'The investment period for this pool has ended.';
            return response()->json([
                'remark'  => 'date_over',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        if ($request->amount > $pool->amount - $pool->invested_amount) {
            $notify[] = 'Pool invest over limit!';
            return response()->json([
                'remark'  => 'limit_over',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        if ($user->$wallet < $request->amount) {
            $notify[] = 'Insufficient balance';
            return response()->json([
                'remark'  => 'insufficient_balance',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $poolInvest = PoolInvest::where('user_id', $user->id)->where('pool_id', $pool->id)->where('status', 1)->first();

        if (!$poolInvest) {
            $poolInvest          = new PoolInvest();
            $poolInvest->user_id = $user->id;
            $poolInvest->pool_id = $pool->id;
        }

        $poolInvest->invest_amount += $request->amount;
        $poolInvest->save();

        $pool->invested_amount += $request->amount;
        $pool->save();

        $user->$wallet -= $request->amount;
        $user->save();

        $transaction               = new Transaction();
        $transaction->user_id      = $user->id;
        $transaction->amount       = $request->amount;
        $transaction->post_balance = $user->$wallet;
        $transaction->charge       = 0;
        $transaction->trx_type     = '-';
        $transaction->details      = 'Pool investment';
        $transaction->trx          = getTrx();
        $transaction->wallet_type  = $wallet;
        $transaction->remark       = 'pool_invest';
        $transaction->save();

        $notify[] = 'Pool investment added successfully';
        return response()->json([
            'remark'  => 'investment_successfully',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }

    public function ranking()
    {
        if (!gs()->user_ranking) {
            $notify[] = 'User ranking currently not available.';
            return response()->json([
                'remark'  => 'not_available',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $userRankings = UserRanking::active()->get();
        $user         = auth()->user()->load('userRanking', 'referrals');
        $nextRanking  = UserRanking::active()->where('id', '>', $user->user_ranking_id)->first();
        $foundNext    = 0;

        $userRankings->transform(function ($userRanking) use ($user, &$foundNext) {
            if ($user->user_ranking_id >= $userRanking->id) {
                $userRanking->progress_percent = 100;
            } elseif (!$foundNext) {
                $myInvestPercent  = ($user->total_invests / $userRanking->minimum_invest) * 100;
                $refInvestPercent = ($user->team_invests / $userRanking->min_referral_invest) * 100;
                $refCountPercent  = ($user->activeReferrals->count() / $userRanking->min_referral) * 100;

                $myInvestPercent               = $myInvestPercent < 100 ? $myInvestPercent : 100;
                $refInvestPercent              = $refInvestPercent < 100 ? $refInvestPercent : 100;
                $refCountPercent               = $refCountPercent < 100 ? $refCountPercent : 100;
                $userRanking->progress_percent = ($myInvestPercent + $refInvestPercent + $refCountPercent) / 3;
                $foundNext                     = 1;
            } else {
                $userRanking->progress_percent = 0;
            }
            return $userRanking;
        });

        $notify[] = 'User rankings list';
        return response()->json([
            'remark'  => 'user_ranking',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data' => [
                'user_rankings' => $userRankings,
                'next_ranking'  => $nextRanking,
                'user'          => $user,
                'image_path'    => getFilePath('userRanking'),
            ]
        ]);        
    }
}
