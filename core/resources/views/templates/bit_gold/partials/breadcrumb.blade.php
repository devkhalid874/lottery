@php
    $breadcrumb = getContent('breadcrumb.content', true);
@endphp

<section class="inner-hero bg_img" data-background="{{ frontendImage('breadcrumb', @$breadcrumb->data_values->image, '1920x1280') }}">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <h2 class="page-title">{{ __($pageTitle) }}</h2>
            </div>
        </div>
    </div>
</section>
