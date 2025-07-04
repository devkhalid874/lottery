@extends($activeTemplate . 'layouts.frontend')

@section('content')
    @php
        $policyPages = getContent('policy_pages.element', false, null, true);
        $registerContent = getContent('register.content', true);
    @endphp


    <div class="account-section bg_img" data-background="{{ frontendImage('register', @$registerContent->data_values->section_bg) }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="account-card">
                        <div class="account-card__header bg_img overlay--one" data-background="{{ frontendImage('register', @$registerContent->data_values->card_bg) }}">
                            <h2 class="section-title">{{ __(@$registerContent->data_values->heading_w) }} <span class="base--color">{{ __(@$registerContent->data_values->heading_c) }}</span>
                            </h2>
                            <p>{{ __(@$registerContent->data_values->sub_heading) }}</p>

                            @include($activeTemplate . 'partials.social_login')

                        </div>
                        <div class="account-card__body">
                            <form action="{{ route('user.register') }}" method="POST" class="verify-gcaptcha">
                                @csrf
                                <div class="row">
                                    @if (session()->get('reference') != null)
                                        <div class="col-md-12">
                                            <p>@lang('You\'re referred by') <i class="fw-bold base--color">{{ session()->get('reference') }}</i></p>
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
                                            <input type="email" class="form-control form--control checkUser" name="email" value="{{ old('email') }}" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang('Password')</label>
                                            <input type="password" class="form-control form--control @if (gs('secure_password')) secure-password @endif" name="password" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang('Confirm Password')</label>
                                            <input type="password" class="form-control form--control" name="password_confirmation" required>
                                        </div>
                                    </div>

                                    <x-captcha />

                                </div>

                                @if (gs('agree'))
                                    <div class="form-group form--check">
                                        <input class="form-check-input" type="checkbox" id="agree" @checked(old('agree')) name="agree" required>
                                        <label class="form-check-label" for="agree">@lang('I agree with')</label> <span>
                                            @foreach ($policyPages as $policy)
                                                <a href="{{ route('policy.pages', $policy->slug) }}" target="_blank">{{ __($policy->data_values->title) }}</a>
                                                @if (!$loop->last)
                                                    ,
                                                @endif
                                            @endforeach
                                        </span>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <button type="submit" id="recaptcha" class="btn--base w-100">
                                        @lang('Register')</button>
                                </div>
                                <p class="mb-0">@lang('Already have an account?') <a href="{{ route('user.login') }}">@lang('Login')</a></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="existModalCenter" tabindex="-1" role="dialog">
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
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">@lang('Close')</button>
                    <a href="{{ route('user.login') }}" class="btn btn--base btn-sm">@lang('Login')</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .country-code .input-group-text {
            background: #fff !important;
        }

        .country-code select {
            border: none;
        }

        .country-code select:focus {
            border: none;
            outline: none;
        }
    </style>
@endpush

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
