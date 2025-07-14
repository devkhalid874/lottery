@extends('admin.layouts.app')

@section('panel')
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg--primary">
                    <h5 class="text-white">{{ $pageTitle }}</h5>
                </div>
                <div class="card-body pb-0">
                    {{-- Filters --}}
                    <form method="GET" action="{{ route('admin.gametickets.gameticket.list') }}" class="row mb-4 g-3">
                        <div class="col-md-4">
                            <label for="date">@lang('Select Date')</label>
                            <input type="date" name="date" id="date" class="form-control"
                                value="{{ request('date') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="game_id">@lang('Select Game')</label>
                            <select name="game_id" id="game_id" class="form-control">
                                <option value="">@lang('All Games')</option>
                                @foreach ($allGames as $singleGame)
                                    <option value="{{ $singleGame->id }}"
                                        {{ request('game_id') == $singleGame->id ? 'selected' : '' }}>
                                        {{ $singleGame->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn--primary w-100">@lang('Search')</button>
                        </div>
                    </form>

                    {{-- Table --}}
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Number')</th>
                                    <th>@lang('Game')</th>
                                    <th>@lang('User Count')</th>
                                  
                                    <th>@lang('Total Amount')</th>
                                      <th>@lang('Users')</th>
                                    <th>@lang('View Tickets')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($paginatedStats as $stat)
                                    <tr>
                                        <td>{{ $stat['number'] }}</td>
                                        <td>{{ $stat['game_name'] }}</td>
                                        <td>{{ $stat['user_count'] }}</td>
                                        <td>{{ $stat['total_amount'] }}</td>
                                            <td>
                                            <button type="button" class="btn  btn--primary btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#usersModal{{ $loop->index }}">
                                                @lang('View Users')
                                            </button>

                                            {{-- Modal --}}
                                            <div class="modal fade" id="usersModal{{ $loop->index }}" tabindex="-1"
                                                aria-labelledby="usersModalLabel{{ $loop->index }}" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"
                                                                id="usersModalLabel{{ $loop->index }}">
                                                                @lang('Users for Number') {{ $stat['number'] }}
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="table-responsive">
                                                                <table class="table table-bordered mb-0">
                                                                    <thead class="custom-table-head">
                                                                        <tr>
                                                                            <th>@lang('Name')</th>
                                                                            <th>@lang('Email')</th>
                                                                            <th>@lang('Number')</th>
                                                                            <th>@lang('Ticket Id')</th>
                                                                            <th>@lang('Amount')</th>
                                                                            <th>@lang('Game')</th>
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
                                        <td>
                                            <a href="{{ route('admin.gametickets.gameticket', $stat['game_id']) }}"
                                                class="btn btn--primary btn-sm">
                                                @lang('View Tickets')
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">@lang('No data found.')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Pagination --}}
                @if ($paginatedStats->hasPages())
                    <div class="card-footer py-4">
                        {{ $paginatedStats->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
