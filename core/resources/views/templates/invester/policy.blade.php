@extends($activeTemplate . 'layouts.frontend')
@section('content')

    @if (url()->current() == url('policy/rules'))
        <section class="plan-section pt-120 pb-120 bg--light">
            <div class="container">
                @php
                    $workProcess = getContent('how_it_work.content', true);
                    $workProcessElements = getContent('how_it_work.element', null, false, true);
                @endphp

                <div class="how-it-work pt-5">
                    <div class="mb-3">
                        <h4>{{ __(@$workProcess->data_values->title) }}</h4>
                        <p>@php echo __(@$workProcess->data_values->subtitle) @endphp</p>
                    </div>
                    <div class="row gy-4">
                        @foreach ($workProcessElements as $process)
                            <div class="col-md-3 col-sm-6">
                                <div class="work-process-card">
                                    <div class="icon-area">
                                        <img src="{{ frontendImage('how_it_work', $process->data_values->image, '50x50') }}"
                                            alt="">
                                    </div>
                                    <h5 class="my-1">{{ __($process->data_values->title) }}</h5>
                                    <p>{{ __($process->data_values->content) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
    <section class="pt-120 pb-120 bg--light full-height ">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <h4>{{ __($pageTitle) }}</h4>
                    </div>
                    @php
                        echo $policy->data_values->details;
                    @endphp
                </div>
            </div>
        </div>
    </section>
@endsection
