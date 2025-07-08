@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="dashboard-inner">
        <div class="mb-4">
            <p>@lang('My Tickets')</p>
            <h3>@lang('Purchased Ticket History')</h3>
        </div>
        <hr>
        {{-- Filter/Search (optional) --}}
        {{-- Add your filters/search here if needed --}}
        {{-- Ticket Table --}}
        <div class="table-responsive">
            <table class="table table--responsive--xl text-center">
                <thead class="thead-dark">
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
                            {{-- <td data-label="@lang('Ticket ID')">{{ $ticket->ticket_id }}</td> --}}
                            <td data-label="@lang('Ticket ID')">
                                <span class="fw-bold text--dark">{{ $ticket->formatted_ticket_id }}</span>
                            </td>
                            <td data-label="@lang('Game')">{{ $ticket->game->name ?? 'N/A' }}</td>
                            <td data-label="@lang('Numbers')">
                                <span class="badge text-dark px-2 py-1">{{ $ticket->number }}</span>
                            </td>
                            <td data-label="@lang('Amount')">
                                {{ showAmount($ticket->amount) }}
                            </td>
                            <td data-label="@lang('Purchased At')">
                                {{ showDateTime($ticket->created_at) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted text-center">@lang('No ticket found')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($tickets->hasPages())
            <div class="custom--pagination mt-4">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
@endsection
