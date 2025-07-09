@extends('admin.layouts.app')

@section('panel')
    @push('topBar')
        @include('admin.game_setting.top_bar')
    @endpush
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Ticket Price')</th>
                                    <th>@lang('Number Range')</th>
                                    <th>@lang('Winning Amount')</th>
                                    <th>@lang('Open Time')</th>
                                    <th>@lang('Close Time')</th>
                                    <th>@lang('Featured')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>

                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                    <tr>
                                        <td>{{ __($plan->name) }}</td>
                                        <td>{{ showAmount($plan->ticket_price) }}</td>
                                        <td>{{ $plan->range_start }} - {{ $plan->range_end }}</td>
                                        <td>{{ ($plan->winning_amount) }}</td>
                                        <td>{{ showDateTime($plan->open_time) }}</td>
                                        <td>{{ showDateTime($plan->close_time) }}</td>
                                        <td>
                                            @if ($plan->featured)
                                                <span class="badge badge--success">@lang('Yes')</span>
                                            @else
                                                <span class="badge badge--warning">@lang('No')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($plan->status)
                                                <span class="badge badge--success">@lang('Active')</span>
                                            @else
                                                <span class="badge badge--warning">@lang('Inactive')</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{-- <button class="btn btn-sm btn-outline--primary modalShow me-2" data-type="edit"
                                                data-bs-toggle="modal" data-bs-target="#editModal"
                                                data-resource="{{ $plan }}"
                                                data-action="{{ route('admin.game.update', $plan->id) }}">
                                                <i class="las la-pen"></i> @lang('Edit')
                                            </button> --}}

                                            {{-- New button --}}
                                            <button type="button" class="btn btn-sm btn-outline--primary modalShow me-2"
                                                data-bs-toggle="modal" data-bs-target="#editModal"
                                                data-action="{{ route('admin.game.update', $plan->id) }}"
                                                data-name="{{ $plan->name }}" data-price="{{ $plan->ticket_price }}"data-winning-amount="{{ $plan->winning_amount }}"
                                                data-start="{{ $plan->range_start }}" data-end="{{ $plan->range_end }}"
                                                data-open="{{ $plan->open_time }}" data-close="{{ $plan->close_time }}"
                                                data-auto="{{ $plan->auto_close }}" data-featured="{{ $plan->featured }}">
                                                <i class="las la-pen"></i> @lang('Edit')
                                            </button>
                                            
                                            @if ($plan->status)
                                                <button class="btn btn-sm btn-outline--danger confirmationBtn"
                                                    data-question="@lang('Are you sure to disable this plan?')"
                                                    data-action="{{ route('admin.game.status', $plan->id) }}">
                                                    <i class="las la-eye-slash"></i> @lang('Disable')
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-outline--success confirmationBtn"
                                                    data-question="@lang('Are you sure to enable this plan?')"
                                                    data-action="{{ route('admin.game.status', $plan->id) }}">
                                                    <i class="las la-eye"></i> @lang('Enable')
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
            </div><!-- card end -->
        </div>
    </div>

    <div class="modal fade" id="addModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Add New Game')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.game.store') }}" method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <!-- Game Name -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Name')</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>

                            <!-- Ticket Price -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Ticket Price')</label>
                                    <input type="number" step="0.01" class="form-control" name="ticket_price" required>
                                </div>
                            </div>

                            <!-- Range Start -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Range Start')</label>
                                    <input type="text" class="form-control" name="range_start" maxlength="2"
                                        pattern="\d{2}" required>
                                </div>
                            </div>

                            <!-- Range End -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Range End')</label>
                                    <input type="text" class="form-control" name="range_end" maxlength="2"
                                        pattern="\d{2}" required>
                                </div>
                            </div>

                               <!-- Winning Amount -->
                             <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Winning Amount')</label>
                                    <input type="text" class="form-control" name="winning_amount" required>
                                </div>
                            </div>

                            <!-- Open Time -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Open Time')</label>
                                    <input type="time" class="form-control" name="open_time" required>
                                </div>
                            </div>

                            <!-- Close Time -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Close Time')</label>
                                    <input type="time" class="form-control" name="close_time" required>
                                </div>
                            </div>

                            <!-- Auto Close Toggle -->
                            {{-- <div class="col-md-6 col-lg-6">
                                <div class="form-group">
                                    <label>@lang('Auto Close')</label>
                                    <input type="checkbox" name="auto_close" value="1" data-width="100%"
                                        data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle"
                                        data-on="@lang('Yes')" data-off="@lang('No')">
                                </div>
                            </div> --}}

                            <!-- Featured Toggle -->
                            <div class="col-md-6 col-lg-6">
                                <div class="form-group">
                                    <label>@lang('Featured')</label>
                                    <input type="checkbox" name="featured" value="1" data-width="100%"
                                        data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle"
                                        data-on="@lang('Yes')" data-off="@lang('No')">
                                </div>
                            </div>

            
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post">
                    @csrf
                    @method('POST') {{-- You can change this to PUT if using resource route --}}
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Edit Plan')</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="las la-times"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            {{-- Name --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Name')</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                            </div>

                            {{-- Ticket Price --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Ticket Price')</label>
                                    <input type="number" step="0.01" name="ticket_price" class="form-control"
                                        required>
                                </div>
                            </div>

                            {{-- Range Start --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Range Start')</label>
                                    <input type="text" name="range_start" maxlength="2" class="form-control"
                                        required>
                                </div>
                            </div>

                            {{-- Range End --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Range End')</label>
                                    <input type="text" name="range_end" maxlength="2" class="form-control" required>
                                </div>
                            </div>
 
                            {{-- Winning Amount --}}
                              <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Winning Amount')</label>
                                    <input type="text" name="winning_amount" class="form-control" required>
                                </div>
                            </div>

                            {{-- Open Time --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Open Time')</label>
                                    <input type="datetime-local" name="open_time" class="form-control" required>
                                </div>
                            </div>

                            {{-- Close Time --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Close Time')</label>
                                    <input type="datetime-local" name="close_time" class="form-control" required>
                                </div>
                            </div>

                    
                            {{-- Featured --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Featured')</label>
                                    <input type="checkbox" name="featured" value="1" data-bs-toggle="toggle"
                                        data-on="@lang('Yes')" data-off="@lang('No')">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Update')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <button class="btn btn-outline--primary btn-sm modalShow" data-type="add" data-bs-toggle="modal"
        data-bs-target="#addModal"><i class="las la-plus"></i> @lang('Add New')</button>
@endpush


@push('script')
    <script>
        (function($) {
            "use strict";

            $('.modalShow').on('click', function() {
                const modal = $('#editModal');

                // Set form action URL from data-action
                modal.find('form').attr('action', $(this).data('action'));

                // Populate form inputs
                modal.find('[name=name]').val($(this).data('name'));
                modal.find('[name=ticket_price]').val($(this).data('price'));
                modal.find('[name=range_start]').val(('0' + $(this).data('start')).slice(-2));
                modal.find('[name=range_end]').val(('0' + $(this).data('end')).slice(-2));
                modal.find('[name=winning_amount]').val($(this).data('winning-amount'));

                // Convert to proper datetime-local format
                modal.find('[name=open_time]').val(formatDate($(this).data('open')));
                modal.find('[name=close_time]').val(formatDate($(this).data('close')));

                // Set checkboxes
                modal.find('[name=auto_close]').prop('checked', $(this).data('auto') == 1).change();
                modal.find('[name=featured]').prop('checked', $(this).data('featured') == 1).change();
            });

            function formatDate(datetimeString) {
                if (!datetimeString) return '';
                const date = new Date(datetimeString);
                return date.toISOString().slice(0, 16); // "YYYY-MM-DDTHH:mm"
            }

        })(jQuery);
    </script>
@endpush
