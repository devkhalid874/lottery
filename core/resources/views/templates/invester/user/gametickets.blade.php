@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="container py-5">
        <div class="section-header text-center mb-4">
            <h3 class="section-title">@lang('Tickets')</h3>
        </div>

        <div class="table-responsive">
            <table class="table table--responsive--xl text-center">
                <thead>
                    <tr>
                        <th>@lang('Ticket ID')</th>
                        <th>@lang('Game')</th>
                        <th>@lang('Numbers')</th>
                        <th>@lang('Amount')</th>
                        <th>@lang('Purchased At')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td data-label="@lang('Ticket ID')">{{ $ticket->ticket_id }}</td>
                            <td data-label="@lang('Game')">{{ $ticket->game->name ?? 'N/A' }}</td>
                            <td data-label="@lang('Numbers')">
                                @php
                                    $numbers = json_decode($ticket->number, true);
                                @endphp

                                @if (is_array($numbers))
                                    @foreach ($numbers as $n)
                                        <span class="badge bg-success  me-1">{{ str_pad($n, 2, '0', STR_PAD_LEFT) }}</span>
                                    @endforeach
                                @else
                                    <span class="badge bg-secondary">N/A</span>
                                @endif
                            </td>


                            <td data-label="@lang('Amount')">{{ showAmount($ticket->amount) }} {{ $general->cur_text }}
                            </td>
                            <td data-label="@lang('Purchased At')">{{ showDateTime($ticket->created_at) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">@lang('No ticket found')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-4">
            {{ $tickets->links() }}
        </div>
    </div>
@endsection
