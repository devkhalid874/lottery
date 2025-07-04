@extends($activeTemplate . 'layouts.app')
@section('panel')
    @php
        $authContent = getContent('authentication.content', true);
    @endphp
    <!-- Account Section -->
    <section class="account-section position-relative">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <a href="{{ route('home') }}" class="text-center d-block mb-3 mb-sm-4 auth-page-logo"><img src="{{ siteLogo('dark') }}" alt="logo"></a>
                    <form action="{{ route('user.data.submit') }}" method="POST" class="account-form">
                        @csrf
                        <div class="row">
                            @if (!$user->email)
                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('First Name')</label>
                                    <input type="text" class="form-control form--control" name="firstname" value="{{ old('firstname') }}" required>
                                </div>
                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('Last Name')</label>
                                    <input type="text" class="form-control form--control" name="lastname" value="{{ old('lastname') }}" required>
                                </div>
                                <div class="form-group col-sm-12">
                                    <label class="form-label">@lang('E-Mail Address')</label>
                                    <input type="email" class="form-control form--control checkUser" name="email" value="{{ old('email') }}" required>
                                    <small class="text-danger emailExist"></small>
                                </div>
                            @endif
                            <div class="form-group col-sm-12">
                                <label class="form-label">@lang('Username')</label>
                                <input type="text" class="form-control form--control checkUser" name="username" value="{{ old('username') }}" required>
                                <small class="text-danger usernameExist"></small>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label">@lang('Country')</label>
                                <select name="country" class="form--control form-select select2">
                                    @foreach ($countries as $key => $country)
                                        <option data-mobile_code="{{ $country->dial_code }}" value="{{ $country->country }}" data-code="{{ $key }}">
                                            {{ __($country->country) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label">@lang('Mobile')</label>
                                <div class="input-group ">
                                    <span class="input-group-text mobile-code">
                                    </span>
                                    <input type="hidden" name="mobile_code">
                                    <input type="hidden" name="country_code">
                                    <input type="number" name="mobile" value="{{ old('mobile') }}" class="form-control form--control checkUser" required>
                                </div>
                                <small class="text-danger mobileExist"></small>
                            </div>

                            <div class="form-group col-sm-6">
                                <label class="form-label">@lang('Address')</label>
                                <input type="text" class="form-control form--control" name="address" value="{{ old('address') }}">
                            </div>
                            <div class="form-group col-sm-6">
                                <label class="form-label">@lang('State')</label>
                                <input type="text" class="form-control form--control" name="state" value="{{ old('state') }}">
                            </div>
                            <div class="form-group col-sm-6">
                                <label class="form-label">@lang('Zip Code')</label>
                                <input type="text" class="form-control form--control" name="zip" value="{{ old('zip') }}">
                            </div>

                            <div class="form-group col-sm-6">
                                <label class="form-label">@lang('City')</label>
                                <input type="text" class="form-control form--control" name="city" value="{{ old('city') }}">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection


@push('script')
    <script>
        "use strict";
        (function($) {

            @if ($mobileCode)
                $(`option[data-code={{ $mobileCode }}]`).attr('selected', '');
            @endif

            $('select[name=country]').on('change', function() {
                $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
                $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
                $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));
                var value = $('[name=mobile]').val();
                var name = 'mobile';
                checkUser(value, name);
            });

            $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
            $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
            $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));


            $('.checkUser').on('focusout', function(e) {
                var value = $(this).val();
                var name = $(this).attr('name')
                checkUser(value, name);
            });

            function checkUser(value, name) {
                var url = `{{ route('user.checkUser') }}`;
                var token = '{{ csrf_token() }}';

                if (name == 'mobile') {
                    var mobile = `${value}`;
                    var data = {
                        mobile: mobile,
                        mobile_code: $('.mobile-code').text().substr(1),
                        _token: token
                    }
                }

                if (name == 'email') {
                    var data = {
                        email: value,
                        _token: token
                    }
                }
                if (name == 'username') {
                    var data = {
                        username: value,
                        _token: token
                    }
                }
                $.post(url, data, function(response) {
                    if (response.data != false) {
                        $(`.${response.type}Exist`).text(`${response.field} already exist`);
                    } else {
                        $(`.${response.type}Exist`).text('');
                    }
                });
            }
        })(jQuery);
    </script>
@endpush
