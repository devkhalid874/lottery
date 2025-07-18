<?php
namespace App\Traits;

use App\Constants\Status;

trait UserNotify
{
    public static function notifyToUser(){
        return [
            'allUsers'              => 'All Users',
            'selectedUsers'         => 'Selected Users',

            'activeInvestUsers'           => 'Active Invest Users',
            'closedInvestUsers'           => 'Closed Invest Users',
            'canceledInvestUsers'         => 'Canceled Invest Users',

            'activeScheduleInvestUsers'   => 'Active Schedule Invest Users',
            'closedScheduleInvestUsers'   => 'Closed Schedule Invest Users',

            'runningStakingInvestUsers'   => 'Running Staking Invest Users',
            'completedStakingInvestUsers' => 'Completed Staking Invest Users',

            'runningPoolInvestUsers'      => 'Running Pool Invest Users',
            'completedPoolInvestUsers'    => 'Completed Pool Invest Users',


            'kycUnverified'         => 'Kyc Unverified Users',
            'kycVerified'           => 'Kyc Verified Users',
            'kycPending'            => 'Kyc Pending Users',
            'withBalance'           => 'With Balance Users',
            'emptyBalanceUsers'     => 'Empty Balance Users',
            'twoFaDisableUsers'     => '2FA Disable User',
            'twoFaEnableUsers'      => '2FA Enable User',
            'hasDepositedUsers'       => 'Deposited Users',
            'notDepositedUsers'       => 'Not Deposited Users',
            'pendingDepositedUsers'   => 'Pending Deposited Users',
            'rejectedDepositedUsers'  => 'Rejected Deposited Users',
            'topDepositedUsers'     => 'Top Deposited Users',
            'hasWithdrawUsers'      => 'Withdraw Users',
            'pendingWithdrawUsers'  => 'Pending Withdraw Users',
            'rejectedWithdrawUsers' => 'Rejected Withdraw Users',
            'pendingTicketUser'     => 'Pending Ticket Users',
            'answerTicketUser'      => 'Answer Ticket Users',
            'closedTicketUser'      => 'Closed Ticket Users',
            'notLoginUsers'         => 'Last Few Days Not Login Users',
        ];
    }

    public function scopeSelectedUsers($query)
    {
        return $query->whereIn('id', request()->user ?? []);
    }

    public function scopeAllUsers($query)
    {
        return $query;
    }


    public function scopeActiveInvestUsers($query)
    {
        return $query->whereHas('invests', function ($invest) {
            return $invest->where('status', Status::INVEST_RUNNING);
        });
    }

    public function scopeClosedInvestUsers($query)
    {
        return $query->whereHas('invests', function ($invest) {
            return $invest->where('status', Status::INVEST_CLOSED);
        });
    }

    public function scopeCanceledInvestUsers($query)
    {
        return $query->whereHas('invests', function ($invest) {
            return $invest->where('status', Status::INVEST_CANCELED);
        });
    }

    public function scopeActiveScheduleInvestUsers($query)
    {
        return $query->whereHas('scheduleInvests', function ($scheduleInvest) {
            return $scheduleInvest->where('status', Status::ENABLE);
        });
    }

    public function scopeClosedScheduleInvestUsers($query)
    {
        return $query->whereHas('scheduleInvests', function ($scheduleInvest) {
            return $scheduleInvest->where('status', Status::DISABLE);
        });
    }

    public function scopeRunningStakingInvestUsers($query)
    {
        return $query->whereHas('stakingInvests', function ($stakingInvest) {
            return $stakingInvest->where('status', Status::STAKING_RUNNING);
        });
    }

    public function scopeCompletedStakingInvestUsers($query)
    {
        return $query->whereHas('stakingInvests', function ($stakingInvest) {
            return $stakingInvest->where('status', Status::STAKING_COMPLETED);
        });
    }

    public function scopeRunningPoolInvestUsers($query)
    {
        return $query->whereHas('poolInvests', function ($poolInvest) {
            return $poolInvest->where('status', Status::STAKING_RUNNING);
        });
    }

    public function scopeCompletedPoolInvestUsers($query)
    {
        return $query->whereHas('poolInvests', function ($poolInvest) {
            return $poolInvest->where('status', Status::STAKING_COMPLETED);
        });
    }


    public function scopeEmptyBalanceUsers($query)
    {
        return $query->where(function ($q) {
            $q->where('balance', '<=', 0)->where('balance', '<=', 0);
        });
    }

    public function scopeTwoFaDisableUsers($query)
    {
        return $query->where('ts', Status::DISABLE);
    }

    public function scopeTwoFaEnableUsers($query)
    {
        return $query->where('ts', Status::ENABLE);
    }

    public function scopeHasDepositedUsers($query)
    {
        return $query->whereHas('deposits', function ($deposit) {
            $deposit->successful();
        });
    }

    public function scopeNotDepositedUsers($query)
    {
        return $query->whereDoesntHave('deposits', function ($q) {
            $q->successful();
        });
    }

    public function scopePendingDepositedUsers($query)
    {
        return $query->whereHas('deposits', function ($deposit) {
            $deposit->pending();
        });
    }

    public function scopeRejectedDepositedUsers($query)
    {
        return $query->whereHas('deposits', function ($deposit) {
            $deposit->rejected();
        });
    }

    public function scopeTopDepositedUsers($query)
    {
        return $query->whereHas('deposits', function ($deposit) {
            $deposit->successful();
        })->withSum(['deposits'=>function($q){
            $q->successful();
        }], 'amount')->orderBy('deposits_sum_amount', 'desc')->take(request()->number_of_top_deposited_user ?? 10);
    }

    public function scopeHasWithdrawUsers($query)
    {
        return $query->whereHas('withdrawals', function ($q) {
            $q->approved();
        });
    }

    public function scopePendingWithdrawUsers($query)
    {
        return $query->whereHas('withdrawals', function ($q) {
            $q->pending();
        });
    }

    public function scopeRejectedWithdrawUsers($query)
    {
        return $query->whereHas('withdrawals', function ($q) {
            $q->rejected();
        });
    }

    public function scopePendingTicketUser($query)
    {
        return $query->whereHas('tickets', function ($q) {
            $q->whereIn('status', [Status::TICKET_OPEN, Status::TICKET_REPLY]);
        });
    }

    public function scopeClosedTicketUser($query)
    {
        return $query->whereHas('tickets', function ($q) {
            $q->where('status', Status::TICKET_CLOSE);
        });
    }

    public function scopeAnswerTicketUser($query)
    {
        return $query->whereHas('tickets', function ($q) {

            $q->where('status', Status::TICKET_ANSWER);
        });
    }

    public function scopeNotLoginUsers($query)
    {
        return $query->whereDoesntHave('loginLogs', function ($q) {
            $q->whereDate('created_at', '>=', now()->subDays(request()->number_of_days ?? 10));
        });
    }

    public function scopeKycVerified($query)
    {
        return $query->where('kv', Status::KYC_VERIFIED);
    }

}
