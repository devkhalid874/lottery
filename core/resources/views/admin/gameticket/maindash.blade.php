@extends('admin.layouts.app')

@section('panel')

<h3>{{ $game->name }}</h3>

{{-- Winner Form --}}
@if (!$game->winning_numbers)
    <div class="card mb-4">
        <div class="card-body">
          <form action="{{ route('admin.gametickets.setWinner', $game->id) }}" method="POST">

                @csrf
                <label>Select Winning Numbers</label>
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <input type="number" name="winning_numbers[]" class="form-control" placeholder="Number 1" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="number" name="winning_numbers[]" class="form-control" placeholder="Number 2">
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="number" name="winning_numbers[]" class="form-control" placeholder="Number 3">
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn--success w-100">Announce Winner</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@else
    <div class="alert alert-success">
        Winning Numbers: <strong>{{ implode(', ', json_decode($game->winning_numbers)) }}</strong>
    </div>
@endif

{{-- Ticket Table --}}
<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-bordered">
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
                @forelse ($game->tickets as $ticket)
                    <tr>
                        <td>{{ $ticket->ticket_id }}</td>
                        <td>{{ $ticket->user->name }}<br><small>{{ $ticket->user->email }}</small></td>
                        <td>
                    @foreach (json_decode($ticket->number) as $num)

                                <span class="badge bg-primary">{{ $num }}</span>
                            @endforeach
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
