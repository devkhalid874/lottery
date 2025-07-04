@extends($activeTemplate . 'layouts.master')
@section('content')
    @php
        $kyc = getContent('kyc.content', true);
    @endphp

    <section class="dashboard-section pt-120 pb-120">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12">

                    <div class="notice"></div>

                    @if ($user->kv == Status::KYC_UNVERIFIED && $user->kyc_rejection_reason)
                        <div class="alert border border--danger" role="alert">
                            <div class="alert__icon d-flex align-items-center text--danger"><i class="fas fa-times-circle"></i>
                            </div>
                            <p class="alert__message">
                                <span class="fw-bold">@lang('KYC Documents Rejected')</span><br>
                                <small>
                                    {{ __(@$kyc->data_values->reject) }}
                                    <a href="javascript::void(0)" class="link-color" data-bs-toggle="modal" data-bs-target="#kycRejectionReason">@lang('Click here')</a> @lang('to show the reason').

                                    <a href="{{ route('user.kyc.form') }}" class="link-color">@lang('Click Here')</a> @lang('to Re-submit Documents').
                                    <a href="{{ route('user.kyc.data') }}" class="link-color">@lang('See KYC Data')</a>
                                </small>
                            </p>
                        </div>
                    @elseif($user->kv == Status::KYC_UNVERIFIED)
                        <div class="alert border border--info" role="alert">
                            <div class="alert__icon d-flex align-items-center text--info"><i class="fas fa-exclamation-circle"></i>
                            </div>
                            <p class="alert__message">
                                <span class="fw-bold">@lang('KYC Verification Required')</span><br>
                                <small>{{ __(@$kyc->data_values->required) }} <a href="{{ route('user.kyc.form') }}">@lang('Click Here to Submit Documents')</a>
                                </small>
                            </p>
                        </div>
                    @elseif($user->kv == Status::KYC_PENDING)
                        <div class="alert border border--warning" role="alert">
                            <div class="alert__icon d-flex align-items-center text--warning"><i class="las la-hourglass-half"></i>
                            </div>
                            <p class="alert__message">
                                <span class="fw-bold">@lang('KYC Verification Pending')</span><br>
                                <small>{{ __(@$kyc->data_values->pending) }} <a href="{{ route('user.kyc.data') }}">@lang('See KYC Data')</a>
                                </small>
                            </p>
                        </div>
                    @endif

                    @if ($user->deposit_wallet <= 0 && $user->interest_wallet <= 0)
                        <div class="alert border border--danger" role="alert">
                            <div class="alert__icon d-flex align-items-center text--danger"><i class="fas fa-exclamation-triangle"></i></div>
                            <p class="alert__message">
                                <span class="fw-bold">@lang('Empty Balance')</span><br>
                                <small><i>@lang('Your balance is empty. Please make') <a href="{{ route('user.deposit.index') }}" class="link-color">@lang('deposit')</a> @lang('for your next investment.')</i></small>
                            </p>
                        </div>
                    @endif

                    @if ($user->deposits->where('status', 1)->count() == 1 && !$user->invests->count())
                        <div class="alert border border--success" role="alert">
                            <div class="alert__icon d-flex align-items-center text--success"><i class="fas fa-check"></i>
                            </div>
                            <p class="alert__message">
                                <span class="fw-bold">@lang('First Deposit')</span><br>
                                <small><i><span class="fw-bold">@lang('Congratulations!')</span> @lang('You\'ve made your first deposit successfully. Go to') <a href="{{ route('plan') }}" class="link-color">@lang('investment plan')</a>
                                        @lang('page and invest now')</i></small>
                            </p>
                        </div>
                    @endif

                    @if ($pendingWithdrawals)
                        <div class="alert border border--primary" role="alert">
                            <div class="alert__icon d-flex align-items-center text--primary"><i class="fas fa-spinner"></i>
                            </div>
                            <p class="alert__message">
                                <span class="fw-bold">@lang('Withdrawal Pending')</span><br>
                                <small><i>@lang('Total') {{ showAmount($pendingWithdrawals) }}
                                        @lang('withdrawal request is pending. Please wait for admin approval. The amount will send to the account which you\'ve provided. See') <a href="{{ route('user.withdraw.history') }}" class="link-color">@lang('withdrawal history')</a></i></small>
                            </p>
                        </div>
                    @endif

                    @if ($pendingDeposits)
                        <div class="alert border border--primary" role="alert">
                            <div class="alert__icon d-flex align-items-center text--primary"><i class="fas fa-spinner"></i>
                            </div>
                            <p class="alert__message">
                                <span class="fw-bold">@lang('Deposit Pending')</span><br>
                                <small><i>@lang('Total') {{ showAmount($pendingDeposits) }}
                                        @lang('deposit request is pending. Please wait for admin approval. See') <a href="{{ route('user.deposit.history') }}" class="link-color">@lang('deposit history')</a></i></small>
                            </p>
                        </div>
                    @endif

                    @if (!$user->ts)
                        <div class="alert border border--warning" role="alert">
                            <div class="alert__icon d-flex align-items-center text--warning"><i class="fas fa-user-lock"></i></div>
                            <p class="alert__message">
                                <span class="fw-bold">@lang('2FA Authentication')</span><br>
                                <small><i>@lang('To keep safe your account, Please enable') <a href="{{ route('user.twofactor') }}" class="link-color">@lang('2FA')</a> @lang('security').</i>
                                    @lang('It will make secure your account and balance.')</small>
                            </p>
                        </div>
                    @endif

                    @if ($isHoliday)
                        <div class="alert border border--info" role="alert">
                            <div class="alert__icon d-flex align-items-center text--info"><i class="fas fa-toggle-off"></i>
                            </div>
                            <p class="alert__message">
                                <span class="fw-bold">@lang('Holiday')</span><br>
                                <small><i>@lang('Today is holiday on this system. You\'ll not get any interest today from this system. Also you\'re unable to make withdrawal request today.') <br> @lang('The next working day is coming after') <span id="counter" class="fw-bold text--base fs--15px"></span></i></small>
                            </p>
                        </div>
                    @endif
                   
                </div>

            </div>
            <div class="row">

                <div class="col-lg-12">
                    <div class="dashboard-main">
                        <div class="row mt-30 mb-5">

                            <div class="col-lg-3 col-md-6 mb-30">
                                <div class="stat-item">
                                    <i class="las la-piggy-bank base--color"></i>
                                    <h6 class="caption text-shadow">@lang('Deposit Wallet')</h6>
                                    <span class="total__amount">{{ showAmount($user->deposit_wallet) }}</span>

                                    <div class="d-flex justify-content-center mt-3">
                                        <a href="{{ route('user.transactions') }}?wallet_type=deposit_wallet" class="btn btn-primary btn-small d-block text-center style--two">@lang('View report')</a>
                                    </div>

                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 mb-30">
                                <div class="stat-item">
                                    <i class="las la-piggy-bank base--color"></i>
                                    <h6 class="caption text-shadow">@lang('Interest Wallet')</h6>
                                    <span class="total__amount">{{ showAmount($user->interest_wallet) }}</span>
                                    <div class="d-flex justify-content-center mt-3">
                                        <a href="{{ route('user.transactions') }}?wallet_type=interest_wallet" class="btn btn-primary btn-small d-block text-center style--two">@lang('View report')</a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 mb-30">
                                <div class="stat-item">
                                    <i class="las la-credit-card base--color"></i>
                                    <h6 class="caption text-shadow">@lang('Total Invest')</h6>
                                    <span class="total__amount">{{ showAmount($totalInvest) }}</span>
                                    <div class="d-flex justify-content-center mt-3">
                                        <a href="{{ route('user.transactions') }}?remark=invest" class="btn btn-primary btn-small d-block text-center style--two">@lang('View report')</a>
                                    </div>
                                </div><!-- stat-item end -->
                            </div>

                            <div class="col-lg-3 col-md-6 mb-30">
                                <div class="stat-item">
                                    <i class="las la-ticket-alt base--color"></i>
                                    <h6 class="caption text-shadow">@lang('Total Ticket')</h6>
                                    <span class="total__amount">{{ $totalTicket }} </span>
                                    <div class="d-flex justify-content-center mt-3">
                                        <a href="{{ route('ticket.index') }}" class="btn btn-primary btn-small d-block text-center style--two">@lang('View Report')</a>
                                    </div>
                                </div><!-- stat-item end -->
                            </div>

                            <div class="col-lg-6">
                                <div class="stat-wrapper deposit">
                                    <div class="stat__header">
                                        <div class="left">
                                            <div class="icon"><i class="las la-chart-area"></i></div>
                                            <h3 class="caption">@lang('Deposit')</h3>
                                        </div>
                                        <div class="right"><i class="flaticon-next"></i></div>
                                    </div>
                                    <div class="item-wrapper">
                                        <div class="stat-item-two box-shadow-two">
                                            <h5 class="caption text-shadow">@lang('Total Deposit')</h5>
                                            <span class="total__amount base--color">{{ showAmount($totalDeposit) }}</span>
                                        </div><!-- stat-item-two end -->
                                        <div class="stat-item-two box-shadow-two">
                                            <h5 class="caption text-shadow">@lang('Last Deposit')</h5>
                                            <span class="total__amount base--color">{{ showAmount(@$lastDeposit->amount ?? 0) }}</span>
                                        </div><!-- stat-item-two end -->

                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6 mt-lg-0 mt-5">
                                <div class="stat-wrapper withdraw">
                                    <div class="stat__header">
                                        <div class="left">
                                            <div class="icon"><i class="las la-credit-card"></i></div>
                                            <h3 class="caption">@lang('Withdraw')</h3>
                                        </div>
                                        <div class="right"><i class="flaticon-next"></i></div>
                                    </div>
                                    <div class="item-wrapper">
                                        <div class="stat-item-two box-shadow-two">
                                            <h5 class="caption text-shadow">@lang('Total Withdraw')</h5>
                                            <span class="total__amount base--color">{{ showAmount($totalWithdraw) }}</span>
                                        </div><!-- stat-item-two end -->
                                        <div class="stat-item-two box-shadow-two">
                                            <h5 class="caption text-shadow">@lang('Last Withdraw')</h5>
                                            <span class="total__amount base--color">{{ showAmount(@$lastWithdraw->amount ?? 0) }}</span>
                                        </div><!-- stat-item-two end -->

                                    </div><!-- item-wrapper end -->
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div><!-- row end -->
        </div>
    </section>

    @if ($user->kv == Status::KYC_UNVERIFIED && $user->kyc_rejection_reason)
        <div class="modal fade" id="kycRejectionReason">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('KYC Document Rejection Reason')</h5>
                        <button type="button" class="close" data-bs-dismiss="modal">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>{{ $user->kyc_rejection_reason }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
@endsection

@push('script')
    <script>
        'use strict';
        (function($) {
            @if ($isHoliday)
                function createCountDown(elementId, sec) {
                    var tms = sec;
                    var x = setInterval(function() {
                        var distance = tms * 1000;
                        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        var days = `<span class="text--base">${days}d</span>`;
                        var hours = `<span class="text--base">${hours}h</span>`;
                        var minutes = `<span class="text--base">${minutes}m</span>`;
                        var seconds = `<span class="text--base">${seconds}s</span>`;
                        document.getElementById(elementId).innerHTML = days + ' ' + hours + " " + minutes +
                            " " + seconds;
                        if (distance < 0) {
                            clearInterval(x);
                            document.getElementById(elementId).innerHTML = "COMPLETE";
                        }
                        tms--;
                    }, 1000);
                }

                createCountDown('counter', {{ abs(\Carbon\Carbon::parse($nextWorkingDay)->diffInSeconds()) }});
            @endif
        })(jQuery);
    </script>
@endpush
