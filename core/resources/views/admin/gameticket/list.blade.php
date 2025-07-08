@extends('admin.layouts.app')

@section('panel')
<div class="card">
    <div class="card-header bg--primary">
        <h5 class="text-white">{{ $pageTitle }}</h5>
    </div>
    <div class="card-body table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Game Name</th>
                    <th scope="col">Start Time</th>
                    <th scope="col">End Time</th>
                    <th scope="col">Tickets Sold</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($games as $game)
                    <tr>
                        <td>{{ $game->name }}</td>
                        <td>{{ showDateTime($game->start_time) }}</td>
                        <td>{{ showDateTime($game->end_time) }}</td>
                        <td>{{ $game->tickets_count }}</td>
                        <td>
                            <a href="{{ route('admin.gametickets.gameticket', $game->id) }}" class="btn btn--primary btn-sm">
                                View Tickets
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No games found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
