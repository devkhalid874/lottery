@extends($activeTemplate.'layouts.app')
@section('panel')


<div class="d-flex flex-wrap">

    @include($activeTemplate.'partials.sidebar')

    <div class="dashboard-wrapper">

        @include($activeTemplate.'partials.topbar')

        <div class="@if(request()->routeIs('pool') || request()->routeIs('user.staking.index')) dashboard-container-fluid  @else dashboard-container @endif">

            @yield('content')

        </div>
    </div>
</div>
@endsection

@push('style')
    <style>
        .language_switcher::after {
            color: #000 !important;
        }
        .language_switcher__caption .text {
            color: #000 !important;
        }
    </style>
@endpush
