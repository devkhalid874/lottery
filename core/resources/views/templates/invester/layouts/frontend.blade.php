@extends($activeTemplate . 'layouts.app')
@section('panel')
    <div class="preloader">
        <div class="animated-preloader"></div>
    </div>
    <div class="overlay"></div>


    {{-- <div class="header">
        <div class="container">
            <div class="header-bottom">
                <div class="header-bottom-area align-items-center">
                    <div class="logo"><a href="{{ route('home') }}"><img src="{{ siteLogo() }}" alt="logo"></a></div>
                    <ul class="menu ms-auto">
                        <li>
                            <a href="{{ route('home') }}">@lang('Home')</a>
                        </li>
                        @php
                            $pages = App\Models\Page::where('tempname', $activeTemplate)->where('is_default', 0)->get();
                        @endphp
                        @foreach ($pages as $k => $data)
                            <li><a href="{{ route('pages', [$data->slug]) }}">{{ __($data->name) }}</a></li>
                        @endforeach
                        @if (auth()->check())
                            <li class="menu-btn">
                                <a href="{{ route('user.home') }}" class="ps-2"> <i class="las la-user"></i>
                                    @lang('Dashboard')</a>
                            </li>
                        @else
                            <li>
                                <a href="{{ route('user.register') }}">@lang('Register')</a>
                            </li>
                            <li class="menu-btn">
                                <a href="{{ route('user.login') }}"> <i class="las la-user"></i> @lang('Login')</a>
                            </li>
                        @endif
                    </ul>
                    @if (gs('multi_language'))
                        @include($activeTemplate . 'partials.language')
                    @endif
                    <div class="header-trigger-wrapper d-flex d-lg-none align-items-center">
                        <div class="header-trigger">
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>  
    </div> --}}

    {{-- ✅ Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top py-3">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="{{ route('home') }}">
                <img src="{{ siteLogo() }}" alt="logo" height="60">
            </a> 

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link fs-6 {{ request()->routeIs('home') ? 'active fw-semibold text-primary' : '' }}"
                            href="{{ route('home') }}">
                            @lang('Home')
                        </a>
                    </li>

                    @php
                        $pages = App\Models\Page::where('tempname', $activeTemplate)->where('is_default', 0)->get();
                    @endphp
                    @foreach ($pages as $data)
                        <li class="nav-item">
                            <a class="nav-link fs-6 {{ request()->routeIs('pages') && request()->route('slug') == $data->slug ? 'active fw-semibold text-primary' : '' }}"
                                href="{{ route('pages', [$data->slug]) }}">
                                {{ __($data->name) }}
                            </a>
                        </li>
                    @endforeach


                    @auth
                        <li class="nav-item">
                            <a class="nav-link fs-6 {{ request()->routeIs('user.home') ? 'active fw-semibold text-primary' : '' }}"
                                href="{{ route('user.home') }}">
                                <i class="las la-user"></i> @lang('Dashboard')
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link fs-6 {{ request()->routeIs('user.register') ? 'active fw-semibold text-primary' : '' }}"
                                href="{{ route('user.register') }}">
                                @lang('Register')
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fs-6 {{ request()->routeIs('user.login') ? 'active fw-semibold text-primary' : '' }}"
                                href="{{ route('user.login') }}">
                                <i class="las la-sign-in-alt"></i> @lang('Login')
                            </a>
                        </li>
                    @endauth
                </ul>

                {{-- Optional: Language Switcher --}}
                @if (gs('multi_language'))
                    @include($activeTemplate . 'partials.language')
                @endif
            </div>
        </div>
    </nav>

    @yield('content')

    @php
        $content = getContent('footer.content', true);
    @endphp
    <!-- Footer Section -->
    <footer class="py-4 border-top border-white">
        <div class="container">
            <div class="footer-content text-center">
                <a href="{{ route('home') }}" class="logo mb-3"><img src="{{ siteLogo('dark') }}" alt="images"></a>
                <p class="footer-text mx-auto">{{ __(@$content->data_values->content) }}</p>
                <ul class="footer-links d-flex flex-wrap gap-3 justify-content-center mt-3 mb-3">
                    <li><a href="{{ route('home') }}" class="link-color-footer">@lang('Home')</a></li>
                    <li><a href="{{ route('contact') }}" class="link-color-footer">@lang('Contact')</a></li>
                    <li><a href="{{ route('user.login') }}" class="link-color-footer">@lang('Sign In')</a></li>
                    <li><a href="{{ route('user.register') }}" class="link-color-footer">@lang('Sign Up')</a></li>
                </ul>
                <p class="copy-right-text">&copy; {{ date('Y') }} <a href="{{ route('home') }}"
                    class="text--base-name">{{ __(gs('site_name')) }}</a>. @lang('All Rights Reserved')</p>
            </div>
        </div>
    </footer>
    <!-- Footer Section -->
@endsection
