<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Lib\CurlRequest;
use App\Lib\HyipLab;
use App\Models\CronJob;
use App\Models\CronJobLog;
use App\Models\Invest;
use App\Models\Plan;
use App\Models\ScheduleInvest;
use App\Models\StakingInvest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserRanking;
use Carbon\Carbon;

class CronController extends Controller
{
    public function cron()
    {
        $general            = gs();
        $general->last_cron = now();
        $general->save();

        $crons = CronJob::with('schedule');

        if (request()->alias) {
            $crons->where('alias', request()->alias);
        } else {
            $crons->where('next_run', '<', now())->where('is_running', Status::YES);
        }
        $crons = $crons->get();
        foreach ($crons as $cron) {
            $cronLog              = new CronJobLog();
            $cronLog->cron_job_id = $cron->id;
            $cronLog->start_at    = now();
            if ($cron->is_default) {
                $controller = new $cron->action[0];
                try {
                    $method = $cron->action[1];
                    $controller->$method();
                } catch (\Exception $e) {
                    $cronLog->error = $e->getMessage();
                }
            } else {
                try {
                    CurlRequest::curlContent($cron->url);
                    
                } catch (\Exception $e) {
                    $cronLog->error = $e->getMessage();
                }
            }
            $cron->last_run = now();
            $cron->next_run = now()->addSeconds($cron->schedule->interval);
            $cron->save();

            $cronLog->end_at = $cron->last_run;

            $startTime         = Carbon::parse($cronLog->start_at);
            $endTime           = Carbon::parse($cronLog->end_at);
            $diffInSeconds     = $startTime->diffInSeconds($endTime);
            $cronLog->duration = $diffInSeconds;
            $cronLog->save();
        }
        if (request()->target == 'all') {
            $notify[] = ['success', 'Cron executed successfully'];
            return back()->withNotify($notify);
        }
        if (request()->alias) {
            $notify[] = ['success', keyToTitle(request()->alias) . ' executed successfully'];
            return back()->withNotify($notify);
        }
    }

    public function interest()
    {
        try {
            $now     = Carbon::now();
            $general = gs();

            $day    = strtolower(date('D'));
            $offDay = (array) $general->off_day;
            if (array_key_exists($day, $offDay)) {
                echo "Holiday";
                exit;
            }

            $invests = Invest::with('plan.timeSetting', 'user')->where('status', Status::INVEST_RUNNING)->where('next_time', '<=', $now)->orderBy('last_time')->take(100)->get();

            foreach ($invests as $invest) {
                $now  = $now;
                $next = HyipLab::nextWorkingDay($invest->plan?->timeSetting->time);
                $user = $invest->user;

                $invest->return_rec_time += 1;
                $invest->paid += $invest->interest;
                $invest->should_pay -= $invest->period > 0 ? $invest->interest : 0;
                $invest->next_time = $next;
                $invest->last_time = $now;
                $invest->net_interest += $invest->rem_compound_times ? 0 : $invest->interest;

                // Add Return Amount to user's Interest Balance
                $user->balance += $invest->interest;
                $user->save();

                $trx = getTrx();

                // Create The Transaction for Interest Back
                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->invest_id    = $invest->id;
                $transaction->amount       = $invest->interest;
                $transaction->charge       = 0;
                $transaction->post_balance = $user->balance;
                $transaction->trx_type     = '+';
                $transaction->trx          = $trx;
                $transaction->remark       = 'interest';
                $transaction->wallet_type  = 'balance';
                $transaction->details      = showAmount($invest->interest) . ' interest from ' . @$invest->plan->name;
                $transaction->save();

                // Give Referral Commission if Enabled
                if ($general->invest_return_commission == 1) {
                    $commissionType = 'invest_return_commission';
                    HyipLab::levelCommission($user, $invest->interest, $commissionType, $trx, $general);
                }

                // Complete the investment if user get full amount as plan
                if ($invest->return_rec_time >= $invest->period && $invest->period != -1) {
                    $invest->status = 0; // Change Status so he do not get any more return

                    // Give the capital back if plan says the same and hold capital option is disabled
                    if ($invest->capital_status == 1 && !$invest->hold_capital) {
                        HyipLab::capitalReturn($invest);
                    }
                }

                if ($invest->rem_compound_times) {
                    $interest        = $invest->interest;
                    $newInvestAmount = $invest->amount + $interest;
                    $newInterest     = $invest->interest * $newInvestAmount / $invest->amount;
                    $newShouldPay    = $invest->should_pay == -1 ? -1 : ($invest->period - $invest->return_rec_time) * $newInterest;

                    $user->balance -= $invest->interest;
                    $user->save();

                    $invest->amount     = $newInvestAmount;
                    $invest->interest   = $newInterest;
                    $invest->should_pay = $newShouldPay;
                    $invest->rem_compound_times -= 1;

                    $transaction               = new Transaction();
                    $transaction->user_id      = $user->id;
                    $transaction->invest_id    = $invest->id;
                    $transaction->amount       = $interest;
                    $transaction->post_balance = $user->balance;
                    $transaction->charge       = 0;
                    $transaction->trx_type     = '-';
                    $transaction->details      = 'Invested Compound on ' . $invest->plan->name;
                    $transaction->trx          = $trx;
                    $transaction->wallet_type  = 'balance';
                    $transaction->remark       = 'invest_compound';
                    $transaction->save();
                }

                $invest->save();

                notify($user, 'INTEREST', [
                    'trx'          => $invest->trx,
                    'amount'       => showAmount($invest->interest, currencyFormat: false),
                    'plan_name'    => @$invest->plan->name,
                    'post_balance' => showAmount($user->balance, currencyFormat: false),
                ]);
            }
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }

    public function rank()
    {
        try {
            $general = gs();
            if (!$general->user_ranking) {
                return 'MODULE DISABLED';
            }

            $users = User::with('referrals', 'activeReferrals')->orderBy('last_rank_update', 'asc')->limit(100)->get();
            foreach ($users as $user) {
                $user->last_rank_update = now();
                $user->save();

                $userInvests     = $user->total_invests;
                $referralInvests = $user->team_invests;
                $referralCount   = $user->activeReferrals->count();

                $rankings = UserRanking::active()->where('id', '>', $user->user_ranking_id)->where('minimum_invest', '<=', $userInvests)->where('min_referral_invest', '<=', $referralInvests)->where('min_referral', '<=', $referralCount)->get();

                foreach ($rankings as $ranking) {
                    $user->balance += $ranking->bonus;
                    $user->user_ranking_id = $ranking->id;
                    $user->save();

                    $transaction               = new Transaction();
                    $transaction->user_id      = $user->id;
                    $transaction->amount       = $ranking->bonus;
                    $transaction->charge       = 0;
                    $transaction->post_balance = $user->balance;
                    $transaction->trx_type     = '+';
                    $transaction->trx          = getTrx();
                    $transaction->remark       = 'ranking_bonus';
                    $transaction->wallet_type  = 'balance';
                    $transaction->details      = showAmount($ranking->bonus) . ' ranking bonus for ' . @$ranking->name;
                    $transaction->save();
                }
            }
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }

    public function investSchedule()
    {
        try {
            if (!gs('schedule_invest')) {
                return 'MODULE DISABLED';
            }

            $scheduleInvests = ScheduleInvest::with('user.deviceTokens', 'plan.timeSetting')->where('next_invest', '<=', now())->where('rem_schedule_times', '>', 0)->where('status', Status::ENABLE)->get();
            $planIds         = array_unique($scheduleInvests->pluck('plan_id')->toArray());
            $activePlanIds   = Plan::whereIn('id', $planIds)->where('status', Status::ENABLE)->whereHas('timeSetting', function ($timeSetting) {
                $timeSetting->where('status', Status::ENABLE);
            })->pluck('id')->toArray();

            foreach ($scheduleInvests as $scheduleInvest) {
                $user   = $scheduleInvest->user;
                $wallet = $scheduleInvest->wallet;

                if ($scheduleInvest->amount > $user->$wallet) {
                    $scheduleInvest->next_invest = now()->addHours($scheduleInvest->interval_hours);
                    $scheduleInvest->save();

                    notify($user, 'INSUFFICIENT_BALANCE', [
                        'invest_amount' => showAmount($scheduleInvest->amount, currencyFormat: false),
                        'wallet'        => keyToTitle($wallet),
                        'plan_name'     => $scheduleInvest->plan->name,
                        'balance'       => showAmount($user->$wallet, currencyFormat: false),
                        'next_schedule' => $scheduleInvest->next_invest,
                    ]);
                    continue;
                }

                if (!in_array($scheduleInvest->plan_id, $activePlanIds)) {
                    continue;
                }

                $hyip = new HyipLab($user, $scheduleInvest->plan);
                $hyip->invest($scheduleInvest->amount, $wallet, $scheduleInvest->compound_times);

                $scheduleInvest->rem_schedule_times -= 1;
                $scheduleInvest->next_invest = $scheduleInvest->rem_schedule_times ? now()->addHours($scheduleInvest->interval_hours) : null;
                $scheduleInvest->status      = $scheduleInvest->rem_schedule_times ? 1 : 0;
                $scheduleInvest->save();
            }
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }

    public function staking()
    {
        try {
            $stakingInvests = StakingInvest::with('user')->where('status', Status::STAKING_RUNNING)->where('end_at', '<=', now())->get();

            foreach ($stakingInvests as $stakingInvest) {
                $user = $stakingInvest->user;
                $user->balance += $stakingInvest->invest_amount + $stakingInvest->interest;
                $user->save();

                $stakingInvest->status = Status::STAKING_COMPLETED;
                $stakingInvest->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->amount       = $stakingInvest->invest_amount + $stakingInvest->interest;
                $transaction->post_balance = $user->balance;
                $transaction->charge       = 0;
                $transaction->trx_type     = '+';
                $transaction->details      = 'Staking invested return';
                $transaction->trx          = getTrx();
                $transaction->wallet_type  = 'balance';
                $transaction->remark       = 'staking_invest_return';
                $transaction->save();
            }

        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }

}
