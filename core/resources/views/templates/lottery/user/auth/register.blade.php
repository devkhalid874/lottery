@extends($activeTemplate . 'layouts.app')
@section('panel')
    @php
        $authContent = getContent('authentication.content', true);
    @endphp
    <!-- Account Section -->
    <section class="account-section position-relative">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-7 col-md-8">
                    <a href="{{ route('home') }}" class="text-center d-block mb-3 mb-sm-4 auth-page-logo"><img src="{{ siteLogo('dark') }}" alt="logo"></a>
                    <form action="{{ route('user.register') }}" method="POST" class="verify-gcaptcha account-form">
                        @csrf
                        <div class="mb-4">
                            <h4 class="mb-2">{{ __(@$authContent->data_values->register_title) }}</h4>
                            <p>{{ __(@$authContent->data_values->register_subtitle) }}</p>

                            @include($activeTemplate . 'partials.social_login')
                        </div>
                        <div class="row">
                            @if (session()->get('reference') != null)
                                <div class="col-12">
                                    <p>@lang('You\'re referred by') <i class="fw-bold text--base">{{ session()->get('reference') }}</i></p>
                                </div>
                            @endif

                            <div class="form-group col-sm-6">
                                <label class="form-label">@lang('First Name')</label>
                                <input type="text" class="form-control form--control" name="firstname" value="{{ old('firstname') }}" required>
                            </div>

                            <div class="form-group col-sm-6">
                                <label class="form-label">@lang('Last Name')</label>
                                <input type="text" class="form-control form--control" name="lastname" value="{{ old('lastname') }}" required>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">@lang('E-Mail Address')</label>
                                    <input type="email" class="form-control form--control h-45 checkUser" name="email" value="{{ old('email') }}" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">@lang('Password')</label>
                                    <input type="password" class="form-control form--control h-45 @if (gs('secure_password')) secure-password @endif" name="password" required>

                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">@lang('Confirm Password')</label>
                                    <input type="password" class="form-control form--control h-45" name="password_confirmation" required>
                                </div>
                            </div>
                            @if (gs('agree'))
                                @php
                                    $policyPages = getContent('policy_pages.element', false, null, true);
                                @endphp
                                <div class="col-12">
                                    <x-captcha />
                                </div>
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-2 justify-content-between">
                                        <div class="form-group custom--checkbox">
                                            <input type="checkbox" id="agree" @checked(old('agree')) name="agree" class="form-check-input" required>
                                            <label for="agree">@lang('I agree with') </label> <span>
                                                @foreach ($policyPages as $policy)
                                                    <a href="{{ route('policy.pages', $policy->slug) }}" class="link-color" target="_blank">{{ __($policy->data_values->title) }}</a>
                                                    @if (!$loop->last)
                                                        ,
                                                    @endif
                                                @endforeach
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="col-12">

                                <button type="submit" class="btn btn--base w-100">@lang('Create Account')</button>
                            </div>
                            <div class="col-12 mt-4">
                                <p class="text-center">@lang('Already have an account?') <a href="{{ route('user.login') }}" class="fw-bold text--base">@lang('Login Account')</a></p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <!-- Account Section -->


    <div class="modal fade" id="existModalCenter" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="existModalLongTitle">@lang('You are with us')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h6 class="text-center">@lang('You already have an account please Login ')</h6>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">@lang('Close')</button>
                    <a href="{{ route('user.login') }}" class="btn btn--base">@lang('Login')</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@if (gs('secure_password'))
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif

@push('script')
    <script>
        "use strict";
        (function($) {

            $('.checkUser').on('focusout', function(e) {
                var url = '{{ route('user.checkUser') }}';
                var value = $(this).val();
                var token = '{{ csrf_token() }}';

                var data = {
                    email: value,
                    _token: token
                }

                $.post(url, data, function(response) {
                    if (response.data != false) {
                        $('#existModalCenter').modal('show');
                    }
                });
            });
        })(jQuery);
    </script>
@endpush
