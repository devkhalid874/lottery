@extends($activeTemplate . 'layouts.frontend')
@section('content')
    @php
        $policyPages = getContent('policy_pages.element', false, null, true);
        $registerContent = getContent('register.content', true);
    @endphp

    <div class="signin-wrapper">
        <div class="outset-circle"></div>
        <div class="container">
            <div class="row justify-content-lg-between align-items-center">
                <div class="col-xl-5 col-lg-6">
                    <div class="signin-thumb">
                        <img src="{{ frontendImage('register', @$registerContent->data_values->image) }}" alt="image">
                    </div>
                </div>
                <div class="col-xl-5 col-lg-6">
                    <div class="signin-form-area">
                        <h3 class="title text-capitalize text-shadow mb-30">{{ __($pageTitle) }}</h3>
                        @include($activeTemplate.'partials.social_login')

                        <form class="signin-form verify-gcaptcha" action="{{ route('user.register') }}" method="post">
                            @csrf
                            @if (session()->get('reference') != null)
                                <div class="form-group">
                                    <p>@lang('You\'re referred by') <i class="fw-bold base--color">{{ session()->get('reference') }}</i></p>
                                </div>
                            @endif

                            <div class="row">
                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('First Name')</label>
                                    <input type="text" class="form-control form--control" name="firstname" value="{{ old('firstname') }}" required>
                                </div>
    
                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('Last Name')</label>
                                    <input type="text" class="form-control form--control" name="lastname" value="{{ old('lastname') }}" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">@lang('Email')</label>
                                <input type="email" name="email" class="checkUser" value="{{ old('email') }}" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">@lang('Password')</label>
                                <input type="password" name="password" @if (gs('secure_password')) class="secure-password" @endif required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">@lang('Confirm Password')</label>
                                <input type="password" name="password_confirmation" required>
                            </div>


                            <x-captcha />

                            @if (gs('agree'))
                                <div class="form-group">
                                    <input type="checkbox" id="agree" @checked(old('agree')) class="h-auto w-auto" name="agree" required>
                                    <label class="mb-0" for="agree">@lang('I agree with')</label> <span>
                                        @foreach ($policyPages as $policy)
                                            <a href="{{ route('policy.pages', $policy->slug) }}" class="base--color" target="_blank">{{ __($policy->data_values->title) }}</a>
                                            @if (!$loop->last)
                                                ,
                                            @endif
                                        @endforeach
                                    </span>
                                </div>
                            @endif

                            <div class="form-group">
                                <button type="submit" class="btn btn-success btn-small w-100 btn-primary">@lang('Sign Up')</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="existModalCenter" tabindex="-1" role="dialog" aria-labelledby="existModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="existModalLongTitle">@lang('You are with us')</h5>
                    <span type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </span>
                </div>
                <div class="modal-body">
                    <h6 class="text-center">@lang('You already have an account please Login ')</h6>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">@lang('Close')</button>
                    <a href="{{ route('user.login') }}" class="btn btn--base btn-sm text--base">@lang('Login')</a>
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
