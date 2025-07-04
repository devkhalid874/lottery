@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-inner">
        <div class="mb-4">
            <p>@lang('Investment')</p>
            <h3>@lang('My Investment Statistics')</h3>
            <p>@lang('Here you can find your active and closed investment and their start date, next return date, total return and more.')</p>
        </div>

        <div class="mt-4">
            @include($activeTemplate . 'partials.invest_history', ['invests' => $invests])

            @if ($invests->hasPages())
                <div class="custom--pagination">
                    {{ paginateLinks($invests) }}
                </div>
            @endif

        </div>
    </div>
@endsection
