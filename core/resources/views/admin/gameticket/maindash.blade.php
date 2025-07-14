@extends('admin.layouts.app')

@section('panel')

    <h3>{{ $game->name }}</h3>

{{-- Winner Form --}}
@php
    $todayWinner = $game->winner->where('created_at', '>=', now()->startOfDay())->first();
@endphp

@if (!$todayWinner)
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.gametickets.setWinner', $game->id) }}" method="POST">
                @csrf
                <label>Select Winning Number</label>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <select name="winning_numbers[]" class="form-control" required>
                            <option value="">Select Number</option>
                            @for ($i = $game->range_start; $i <= $game->range_end; $i++)
                                <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">
                                    {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <button class="btn btn--success w-100">Announce Winner</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@else
    <div class="alert alert-success">
        <strong>Today's Winning Number: {{ $todayWinner->winning_numbers }}</strong>
    </div>
@endif




{{-- Time and Day Filter Form --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.gametickets.gameticket', $game->id) }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="day" class="form-label">Day</label>
                    <select id="day" name="day" class="form-control">
                        <option value="">All</option>
                        @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
                            <option value="{{ $day }}" {{ request()->day == $day ? 'selected' : '' }}>{{ $day }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="start_time" class="form-label">Start Time</label>
                    <input type="time" id="start_time" name="start_time" class="form-control"
                        value="{{ request()->start_time }}">
                </div>
                <div class="col-md-3">
                    <label for="end_time" class="form-label">End Time</label>
                    <input type="time" id="end_time" name="end_time" class="form-control"
                        value="{{ request()->end_time }}">
                </div>
                <div class="col-md-3">
                    <button class="btn btn--primary w-100" type="submit">Filter Tickets</button>
                </div>
            </div>
        </form>
    </div>
</div>


    {{-- Ticket Table --}}
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>User</th>
                        <th>Numbers</th>
                        <th>Is Winner?</th>
                        <th>Purchased At</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $tickets = $filteredTickets ?? $game->tickets;
                    @endphp
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->formatted_ticket_id }}</td>
                            <td>
                                {{ $ticket->user->name }}<br>
                                <small>{{ $ticket->user->email }}</small>
                            </td>
                            <td>
                                <span class="badge text-dark"><strong>{{ $ticket->number }}</strong></span>
                            </td>
                            <td>
                                @if ($ticket->is_winner)
                                    <span class="badge bg-success">Winner</span>
                                @else
                                    <span class="badge bg-secondary">—</span>
                                @endif
                            </td>
                            <td>{{ showDateTime($ticket->created_at) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No tickets found for this game.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .widget_select {
            padding: 3px 3px;
            font-size: 13px;
        }
    </style>
@endpush
