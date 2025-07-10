@extends('admin.layouts.app')

@section('panel')
    <div class="card">
        <div class="card-header bg--primary">
            <h5 class="text-white">{{ $pageTitle }}</h5>
        </div>
        <div class="card-body table-responsive">

            <form method="GET" action="{{ route('admin.gametickets.gameticket.list') }}" class="row mb-4 g-3">
                <div class="col-md-4">
                    <label for="date">Select Date</label>
                    <input type="date" name="date" id="date" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-md-4">
                    <label for="game_id">Select Game</label>
                    <select name="game_id" id="game_id" class="form-control">
                        <option value="">All Games</option>
                        @foreach ($allGames as $singleGame)
                            <option value="{{ $singleGame->id }}"
                                {{ request('game_id') == $singleGame->id ? 'selected' : '' }}>
                                {{ $singleGame->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn--primary w-100">Search</button>
                </div>
            </form>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Number</th>
                        <th>Game</th>
                        <th>User Count</th>
                        <th>Users</th>
                        <th>Total Amount</th>
                        <th>View Tickets</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($numberStats as $stat)
                        <tr>
                            <td>{{ $stat['number'] }}</td>
                            <td>{{ $stat['game_name'] }}</td>
                            <td>{{ $stat['user_count'] }}</td>
                            <td>
                                <button type="button" class="btn btn--info btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#usersModal{{ $loop->index }}">
                                    View Users
                                </button>

                                <!-- Modal -->
                                <div class="modal fade" id="usersModal{{ $loop->index }}" tabindex="-1"
                                    aria-labelledby="usersModalLabel{{ $loop->index }}" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="usersModalLabel{{ $loop->index }}">Users for
                                                    Number {{ $stat['number'] }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered mb-0">
                                                        <thead class="custom-table-head">
                                                            <tr>
                                                                <th>Name</th>
                                                                <th>Email</th>
                                                                <th>Number</th>
                                                                <th>Ticket Id</th>
                                                                <th>Amount</th>
                                                                <th>Game</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($stat['users'] as $user)
                                                                <tr>
                                                                    <td>{{ $user->fullname }}</td>
                                                                    <td>{{ $user->email }}</td>
                                                                    <td>{{ $user->ticket_number }}</td>
                                                                     <td>{{ getTicketId($user->id) }}</td>

                                                                    <td>{{ $user->amount }}</td>
                                                                    <td>{{ $user->game_name }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>

                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $stat['total_amount'] }}</td>
                            <td>
                                <a href="{{ route('admin.gametickets.gameticket', $stat['game_id']) }}"
                                    class="btn btn--primary btn-sm">View Tickets</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No data found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </div>
@endsection
