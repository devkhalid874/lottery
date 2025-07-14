@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="dashboard-inner">
    <div class="mb-4">
        <h3>@lang('Purchased Ticket History')</h3>
        <p>@lang('Here is your whole ticket history')</p>
    </div>
    <hr>

    {{-- Optional Filter/Search --}}
    <div class="filter-area mb-3">
        <div class="d-flex flex-wrap gap-4">
            <div class="flex-grow-1">
                <form>
                    <div class="custom-input-box trx-search">
                        <label>@lang('Game Name')</label>
                        <input type="text" name="search" value="{{ request()->search }}" placeholder="Search Game Name">
                        <button type="submit" class="icon-area">
                            <i class="las la-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Accordion Table --}}
    <div class="accordion table--acordion" id="ticketAccordion">
        @forelse ($tickets as $ticket)
        <div class="accordion-item transaction-item">
            <h2 class="accordion-header" id="h-{{ $loop->iteration }}">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-{{ $loop->iteration }}">
                    <div class="col-lg-4 col-sm-5 col-8 order-1 icon-wrapper">
                        <div class="left">
                            <div class="icon tr-icon icon-success">
                                🎟️
                            </div>
                            <div class="content">
                                <h6 class="trans-title">{{ $ticket->game->name ?? 'N/A' }}</h6>
                                <span class="text-muted font-size--14px mt-2">{{ showDateTime($ticket->created_at, 'M d Y @g:i:a') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-4 col-12 order-sm-2 order-3 content-wrapper mt-sm-0 mt-3">
                        <p class="text-muted font-size--14px"><b>#{{ $ticket->formatted_ticket_id }}</b></p>
                    </div>
                    <div class="col-lg-4 col-sm-3 col-4 order-sm-3 order-2 text-end amount-wrapper">
                        <p>
                            <b>{{ showAmount($ticket->amount) }}</b><br>
                            <small class="fw-bold text-muted">@lang('Numbers'): {{ $ticket->number }}</small>
                        </p>
                    </div>
                </button>
            </h2>
            <div id="c-{{ $loop->iteration }}" class="accordion-collapse collapse" aria-labelledby="h-{{ $loop->iteration }}" data-bs-parent="#ticketAccordion">
                <div class="accordion-body">
                    <ul class="caption-list">
                        <li>
                            <span class="caption">@lang('Ticket ID')</span>
                            <span class="value">{{ $ticket->formatted_ticket_id }}</span>
                        </li>
                        <li>
                            <span class="caption">@lang('Game')</span>
                            <span class="value">{{ $ticket->game->name ?? 'N/A' }}</span>
                        </li>
                        <li>
                            <span class="caption">@lang('Numbers')</span>
                            <span class="value">{{ $ticket->number }}</span>
                        </li>
                        <li>
                            <span class="caption">@lang('Amount')</span>
                            <span class="value">{{ showAmount($ticket->amount) }}</span>
                        </li>
                        <li>
                            <span class="caption">@lang('Purchased At')</span>
                            <span class="value">{{ showDateTime($ticket->created_at) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @empty
        <div class="accordion-body text-center">
            <h4 class="text--muted"><i class="far fa-frown"></i> @lang('No ticket found')</h4>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($tickets->hasPages())
    <div class="custom--pagination mt-4">
        {{ $tickets->links() }}
    </div>
    @endif
</div>
@endsection

@push('style')
<style>
    .trx-search {
        position: relative;
    }

    .trx-search .icon-area {
        position: absolute;
        top: 10px;
        right: 8px;
        font-size: 20px;
        background: transparent;
        border: none;
    }
</style>
@endpush
