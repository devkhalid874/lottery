@php
    $text = request()->routeIs('user.register') ? 'Register' : 'Login';
@endphp

<div class="mt-2">
    @if (@gs('socialite_credentials')->google->status == Status::ENABLE)
        <a href="{{ route('user.social.login', 'google') }}" class="btn btn--base w-100 my-2"><img src="{{ asset('assets/global/images/google.svg') }}" alt="@lang('image')" class="others-login-image"> @lang("$text with Google")</a>
    @endif

    @if (@gs('socialite_credentials')->facebook->status == Status::ENABLE)
        <a href="{{ route('user.social.login', 'facebook') }}" class="btn btn--base w-100 my-2"><img src="{{ asset('assets/global/images/facebook.svg') }}" alt="@lang('image')" class="others-login-image"> @lang("$text with Facebook")</a>
    @endif

    @if (@gs('socialite_credentials')->linkedin->status == Status::ENABLE)
        <a href="{{ route('user.social.login', 'linkedin') }}" class="btn btn--base w-100 my-2"><img src="{{ asset('assets/global/images/linkedin.svg') }}" alt="@lang('image')" class="others-login-image"> @lang("$text with Linkedin")</a>
    @endif

    @if (gs('metamask_login'))
        <button class="btn btn--base w-100 my-2 metamaskLogin"><img src="{{ asset($activeTemplateTrue . 'images/metamask.png') }}" alt="@lang('image')" class="others-login-image"> @lang("$text with Metamask")</button>
    @endif
</div>

@if (@gs('socialite_credentials')->linkedin->status || @gs('socialite_credentials')->facebook->status == Status::ENABLE || @gs('socialite_credentials')->google->status == Status::ENABLE || gs('metamask_login'))
    <div class="text-center">
        <span>@lang('OR')</span>
    </div>
@endif

@push('style')
    <style>
        .social-login-btn {
            border: 1px solid #cbc4c4;
        }

        .others-login-image {
            width: 22px;
        }
    </style>
@endpush

@if (gs('metamask_login'))
    @push('script-lib')
        <script src="{{ asset('assets/global/js/web3.min.js') }}"></script>
    @endpush

    @push('script')
        <script>
            var account = null;
            var signature = null;
            var message = 'Sign In';
            var token = null;
            $('.metamaskLogin').on('click', async () => {
                // detect wallet
                if (!window.ethereum) {
                    notify('error', 'MetaMask not detected. Please install MetaMask first.');
                    return;
                }

                // get wallet address
                await window.ethereum.request({
                    method: 'eth_requestAccounts'
                });
                window.web3 = new Web3(window.ethereum);
                accounts = await web3.eth.getAccounts();
                account = accounts[0];

                // get unique message
                let response = await fetch(`{{ route('user.login.metamask') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        'account': account,
                        '_token': '{{ csrf_token() }}'
                    })
                });
                message = (await response.json()).message;
                setTimeout(async () => {
                    // get signature
                    signature = await web3.eth.personal.sign(message, account);

                    // verify signature
                    response = await fetch(`{{ route('user.login.metamask.verify') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            'signature': signature,
                            '_token': '{{ csrf_token() }}'
                        })
                    });
                    response = await response.json();

                    notify(response.type, response.message);

                    // handle login
                    if (response.type == 'success') {
                        setTimeout(() => {
                            window.location.href = response.redirect_url;
                        }, 2000);
                    }
                }, 1500);

            })
        </script>
    @endpush
@endif
