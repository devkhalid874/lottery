<header class="header-section">
    <div class="header-top">
        <div class="container-fluid">
            <div class="header-top-content d-flex flex-wrap align-items-center justify-content-between">
                <div class="header-top-left">
                    @if (gs('multi_language'))
                        @include($activeTemplate . 'partials.language')
                    @endif
                </div>
                <div class="header-top-right">
                    <div class="header-action d-flex flex-wrap align-items-center">
                        <a href="{{ route('user.home') }}" class="btn btn-primary btn-small w-auto">@lang('Dashboard')</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="header-bottom">
        <div class="container-fluid">
            <nav class="navbar navbar-expand-xl align-items-center">
                <a href="{{ url('/') }}" class="site-logo site-title">
                    <img src="{{ siteLogo() }}" alt="logo">
                </a>
                <button type="button" class="dashboard-side-menu-open ms-auto"><i class="fa fa-bars"></i>
                </button>
            </nav>
        </div>
    </div>
</header>
