@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="dashboard-inner">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="mb-4">
                    <h3 class="mb-2">@lang('Deposit Funds')</h3>
                    <p>@lang("Add funds using our system's gateway. The deposited amount will be credited to your deposit wallet.")</p>
                </div>
                <div class="text-end mb-3">
                    <a href="{{ route('user.deposit.history') }}" class="btn btn--secondary btn--smd"><i class="las la-long-arrow-alt-left"></i> @lang('Deposit History')</a>
                </div>

                {{-- ✅ Show success message --}}
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                {{-- ✅ Show validation error --}}
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('user.deposit.insert') }}" method="post" class="deposit-form">
                    @csrf
                    <input type="hidden" name="currency" value="USD">
                    <div class="gateway-card">
                        <div class="row justify-content-center gy-sm-4 gy-3">
                            <div class="col-lg-6">
                                <div class="payment-system-list gateway-option-list">
                                    @foreach ($gatewayCurrency as $data)
                                        @if ($data->method_code == 103)
                                            <label for="{{ titleToKey($data->name) }}" class="payment-item gateway-option">
                                                <div class="payment-item__info">
                                                    <span class="payment-item__check"></span>
                                                    <span class="payment-item__name">{{ __($data->name) }}</span>
                                                </div>
                                                <div class="payment-item__thumb">
                                                    <img class="payment-item__thumb-img"
                                                         src="{{ getImage(getFilePath('gateway') . '/' . $data->method->image) }}"
                                                         alt="@lang('payment-thumb')">
                                                </div>
                                                <input class="payment-item__radio gateway-input"
                                                       id="{{ titleToKey($data->name) }}" hidden
                                                       data-min="{{ showAmount($data->min_amount) }}"
                                                       data-max="{{ showAmount($data->max_amount) }}"
                                                       type="radio"
                                                       name="gateway"
                                                       value="{{ $data->method_code }}"
                                                       checked>
                                            </label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="payment-system-list p-3">
                                    <div class="deposit-info">
                                        <div class="deposit-info__title">
                                            <p class="text mb-0">@lang('Amount')</p>
                                        </div>
                                        <div class="deposit-info__input">
                                            <div class="deposit-info__input-group input-group">
                                                <span class="deposit-info__input-group-text px-2">{{ gs('cur_sym') }}</span>
                                                <input type="number" min="1" step="0.01"
                                                       class="form-control form--control amount"
                                                       name="amount"
                                                       placeholder="@lang('00.00')"
                                                       value="{{ old('amount') }}" required>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn--base w-100 mt-3">
                                        @lang('Confirm Deposit')
                                    </button>

                                    <div class="info-text pt-3">
                                        <p class="text">@lang('Your funds will be added to your deposit wallet instantly.')</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection
