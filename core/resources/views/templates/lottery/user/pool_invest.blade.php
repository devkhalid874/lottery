@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-inner">
        <div class="mb-4">
            <p>@lang('Pool Investment')</p>
            <h3>@lang('My Pool Investment')</h3>
        </div>

        <div class="mt-4">
            <div class="plan-list d-flex flex-wrap flex-xxl-column gap-3 gap-xxl-0">
                @forelse($poolInvests as $poolInvest)
                    <div class="plan-item-two">
                        <div class="plan-info plan-inner-div">
                            <div class="d-flex align-items-center gap-3">
                                <div class="plan-name-data">
                                    <div class="plan-name fw-bold">{{ __($poolInvest->pool->name) }}</div>
                                    <div class="plan-desc">@lang('Invested'): <span class="fw-bold">{{ showAmount($poolInvest->invest_amount) }} </span></div>
                                </div>
                            </div>
                        </div>
                        <div class="plan-start plan-inner-div">
                            <p class="plan-label">@lang('Invest Till')</p>
                            <p class="plan-value date">{{ showDateTime($poolInvest->pool->start_date, 'M d, Y h:i A') }}</p>
                        </div>
                        <div class="plan-inner-div">
                            <p class="plan-label">@lang('Return Date')</p>
                            <p class="plan-value">{{ showDateTime($poolInvest->pool->end_date, 'M d, Y h:i A') }}</p>
                        </div>
                        <div class="plan-inner-div text-end">
                            <p class="plan-label">@lang('Total Return')</p>
                            <p class="plan-value amount">
                                @if ($poolInvest->pool->share_interest)
                                    {{ showAmount($poolInvest->invest_amount * (1 + $poolInvest->pool->interest / 100)) }}
                                @else
                                    @lang('Not return yet!')
                                @endif
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="accordion-body text-center bg-white p-4">
                        <h4 class="text--muted"><i class="far fa-frown"></i> {{ __($emptyMessage) }}</h4>
                    </div>
                @endforelse
            </div>

            @if ($poolInvests->hasPages())
                <div class="custom--pagination">
                    {{ paginateLinks($poolInvests) }}
                </div>
            @endif

        </div>
    </div>
@endsection
