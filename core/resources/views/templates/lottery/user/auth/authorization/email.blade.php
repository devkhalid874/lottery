@extends($activeTemplate.'layouts.app')
@section('panel')

<!-- Account Section -->
<section class="account-section position-relative">
    <div class="container">
        <div class="text-center">
            <a href="{{ route('home') }}" class="d-block mb-3 mb-sm-4 auth-page-logo"><img src="{{ siteLogo('dark') }}" alt="logo"></a>
        </div>
        <div class="d-flex justify-content-center">
            <div class="verification-code-wrapper">
                <div class="verification-area">
                    <h5 class="pb-3 text-center border-bottom">@lang('Verify Email Address')</h5>
                    <form action="{{route('user.verify.email')}}" method="POST" class="submit-form">
                        @csrf
                        <p class="verification-text">@lang('A 6 digit verification code sent to your email address') :  {{ showEmailAddress(auth()->user()->email) }}</p>

                        @include($activeTemplate.'partials.verification_code')

                        <div class="form-group">
                            <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                        </div>

                        <div class="form-group">
                            <p>
                                @lang('If you don\'t get any code'), <span class="countdown-wrapper">@lang('try again after') <span id="countdown" class="fw-bold">--</span> @lang('seconds')</span> <a href="{{route('user.send.verify.code', 'email')}}" class="try-again-link fw-bold link-color d-none"> @lang('Try again')</a>
                            </p>
                            <a href="{{ route('user.logout') }}" class="fw-bold link-color">@lang('Logout')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Account Section -->

@endsection


@push('script')
    <script>
        var distance =Number("{{@$user->ver_code_send_at->addMinutes(2)->timestamp-time()}}");
        var x = setInterval(function() {
            distance--;
            document.getElementById("countdown").innerHTML = distance;
            if (distance <= 0) {
                clearInterval(x);
                document.querySelector('.countdown-wrapper').classList.add('d-none');
                document.querySelector('.try-again-link').classList.remove('d-none');
            }
        }, 1000);
    </script>
@endpush