@extends('admin.layouts.app')
@section('panel')
    <div class="row mb-none-30">
        <div class="col-lg-12 col-md-12 mb-30">
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-xl-3 col-sm-6">
                                <div class="form-group ">
                                    <label> @lang('Site Title')</label>
                                    <input class="form-control" type="text" name="site_name" required value="{{ gs('site_name') }}">
                                </div>
                            </div>
                            <div class="col-xl-3 col-sm-6">
                                <div class="form-group ">
                                    <label>@lang('Currency')</label>
                                    <input class="form-control" type="text" name="cur_text" required value="{{ gs('cur_text') }}">
                                </div>
                            </div>
                            <div class="col-xl-3 col-sm-6">
                                <div class="form-group ">
                                    <label>@lang('Currency Symbol')</label>
                                    <input class="form-control" type="text" name="cur_sym" required value="{{ gs('cur_sym') }}">
                                </div>
                            </div>
                            <div class="form-group col-xl-3 col-sm-6">
                                <label class="required"> @lang('Timezone')</label>
                                <select class="select2 form-control" name="timezone">
                                    @foreach ($timezones as $key => $timezone)
                                        <option value="{{ @$key }}" @selected(@$key == $currentTimezone)>{{ __($timezone) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-xl-3 col-sm-6">
                                <label class="required"> @lang('Site Base Color')</label>
                                <div class="input-group">
                                    <span class="input-group-text p-0 border-0">
                                        <input type='text' class="form-control colorPicker" value="{{ gs('base_color') }}">
                                    </span>
                                    <input type="text" class="form-control colorCode" name="base_color" value="{{ gs('base_color') }}">
                                </div>
                            </div>
                            <div class="form-group col-xl-3 col-sm-6">
                                <label class="required"> @lang('Site Secondary Color')</label>
                                <div class="input-group">
                                    <span class="input-group-text p-0 border-0">
                                        <input type='text' class="form-control colorPicker" value="{{ gs('secondary_color') }}">
                                    </span>
                                    <input type="text" class="form-control colorCode" name="secondary_color" value="{{ gs('secondary_color') }}">
                                </div>
                            </div>
                            <div class="form-group col-xl-3 col-sm-6">
                                <label>@lang('Balance Transfer Fixed Charge')</label>
                                <div class="input-group">
                                    <input class="form-control bal-charge" type="text" name="f_charge" required value="{{ getAmount(gs('f_charge')) }}" @if (!gs('b_transfer')) readonly @endif>
                                    <div class="input-group-text">{{ gs('cur_text') }}</div>
                                </div>
                            </div>
                            <div class="form-group col-xl-3 col-sm-6">
                                <label>@lang('Balance Transfer Percent Charge')</label>
                                <div class="input-group">
                                    <input class="form-control bal-charge" type="text" name="p_charge" required value="{{ getAmount(gs('p_charge')) }}" @if (!gs('b_transfer')) readonly @endif>
                                    <div class="input-group-text">%</div>
                                </div>
                            </div>
                            <div class="form-group col-xl-3 col-sm-6">
                                <label>@lang('Registration Bonus')</label>
                                <div class="input-group">
                                    <input class="form-control bal-charge" type="text" name="signup_bonus_amount" required value="{{ getAmount(gs('signup_bonus_amount')) }}" min="0" @if (!gs('signup_bonus_control')) readonly @endif>
                                    <div class="input-group-text">{{ gs('cur_text') }}</div>
                                    @if (!gs('signup_bonus_control'))
                                        <small class="text--small text-muted"><i><i class="las la-info-circle"></i> @lang('To give the registration bonus, please enable the module from the') <a href="{{ route('admin.setting.system.configuration') }}" class="text--small">@lang('System Configuration')</a></i></small>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group col-xl-3 col-sm-6">
                                <label> @lang('Record to Display Per page')</label>
                                <select class="select2 form-control" name="paginate_number" data-minimum-results-for-search="-1">
                                    <option value="20" @selected(gs('paginate_number') == 20)>@lang('20 items per page')</option>
                                    <option value="50" @selected(gs('paginate_number') == 50)>@lang('50 items per page')</option>
                                    <option value="100" @selected(gs('paginate_number') == 100)>@lang('100 items per page')</option>
                                </select>
                            </div>

                            <div class="form-group col-xl-3 col-sm-6">
                                <label class="required"> @lang('Currency Showing Format')</label>
                                <select class="select2 form-control" name="currency_format" data-minimum-results-for-search="-1">
                                    <option value="1" @selected(gs('currency_format') == Status::CUR_BOTH)>@lang('Show Currency Text and Symbol Both')</option>
                                    <option value="2" @selected(gs('currency_format') == Status::CUR_TEXT)>@lang('Show Currency Text Only')</option>
                                    <option value="3" @selected(gs('currency_format') == Status::CUR_SYM)>@lang('Show Currency Symbol Only')</option>
                                </select>
                            </div>

                            <div class="form-group col-xl-3 col-sm-6">
                                <label class="required"> @lang('SPA (Single Page Application)')</label>
                                <select class="select2 form-control" name="spa" data-minimum-results-for-search="-1">
                                    <option value="1" @selected(gs('spa') == Status::YES)>@lang('Yes')</option>
                                    <option value="0" @selected(gs('spa') == Status::NO)>@lang('No')</option>
                                </select>
                            </div>

                            <div class="form-group col-xl-3 col-sm-6">
                                <label>@lang('SPA URL')</label>
                                <div class="input-group">
                                    <input class="form-control" type="text" name="spa_url" value="{{ gs('spa_url') }}" />
                                </div>
                            </div>

                            <div class="form-group col-xl-3 col-sm-6">
                                <label>@lang('Staking Min Amount')</label>
                                <div class="input-group">
                                    <input class="form-control bal-charge" type="number" name="staking_min_amount" required value="{{ getAmount(gs('staking_min_amount')) }}" step="any" min="0" @if (!gs('staking_option')) readonly @endif>
                                    <div class="input-group-text">{{ gs('cur_text') }}</div>
                                    @if (!gs('staking_option'))
                                        <small class="text--small text-muted"><i><i class="las la-info-circle"></i> @lang('To set the staking min amount, please enable the module from the') <a href="{{ route('admin.setting.system.configuration') }}" class="text--small">@lang('System Configuration')</a></i></small>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group col-xl-3 col-sm-6">
                                <label>@lang('Staking Max Amount')</label>
                                <div class="input-group">
                                    <input class="form-control bal-charge" type="number" name="staking_max_amount" required value="{{ getAmount(gs('staking_max_amount')) }}" step="any" min="0" @if (!gs('staking_option')) readonly @endif>
                                    <div class="input-group-text">{{ gs('cur_text') }}</div>
                                    @if (!gs('staking_option'))
                                        <small class="text--small text-muted"><i><i class="las la-info-circle"></i> @lang('To set the staking max amount, please enable the module from the') <a href="{{ route('admin.setting.system.configuration') }}" class="text--small">@lang('System Configuration')</a></i></small>
                                    @endif
                                </div>
                            </div>

                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('script-lib')
    <script src="{{ asset('assets/admin/js/spectrum.js') }}"></script>
@endpush

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/spectrum.css') }}">
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";


            $('.colorPicker').spectrum({
                color: $(this).data('color'),
                change: function(color) {
                    $(this).parent().siblings('.colorCode').val(color.toHexString().replace(/^#?/, ''));
                }
            });

            $('.colorCode').on('input', function() {
                var clr = $(this).val();
                $(this).parents('.input-group').find('.colorPicker').spectrum({
                    color: clr,
                });
            });
        })(jQuery);
    </script>
@endpush
