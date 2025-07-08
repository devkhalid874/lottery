@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Title')</th>
                                    <th>@lang('Description')</th>
                                    <th>@lang('Image')</th>
                                    <th>@lang('Time')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($announcements as $item)
                                    <tr>
                                        <td>{{ $item->title }}</td>
                                        <td>{{ Str::limit($item->description, 80) }}</td>
                                        <td>
                                            <img src="{{ announcementAsset($item->media_path) }}" alt="announcement"
                                                width="100">
                                        </td>
                                        <td>{{ $item->created_at->diffForHumans() }}</td>
                                        <td>
                                            @if ($item->is_active)
                                                <span class="badge badge--success">Active</span>
                                            @else
                                                <span class="badge badge--danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{-- Edit Button --}}
                                            <button class="btn btn-sm btn-outline--primary editBtn"
                                                data-id="{{ $item->id }}" data-title="{{ $item->title }}"
                                                data-description="{{ $item->description }}"
                                                data-media_type="{{ $item->media_type }}"
                                                data-is_active="{{ $item->is_active }}" data-bs-toggle="modal"
                                                data-bs-target="#editModal">
                                                <i class="la la-pencil-alt"></i>
                                            </button>

                                            {{-- Delete Button --}}
                                            <button class="btn btn-sm btn-outline--danger confirmationBtn"
                                                data-action="{{ route('admin.announcement.delete', $item->id) }}"
                                                data-question="@lang('Are you sure you want to delete this announcement?')">
                                                <i class="la la-trash"></i>
                                            </button>
                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No announcements found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add New Modal --}}
    <div class="modal fade" id="timeModal" tabindex="-1" aria-labelledby="timeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg--primary text-white">
                    <h5 class="modal-title" id="timeModalLabel">
                        <i class="las la-bullhorn"></i> @lang('Add New Announcement')
                    </h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.announcement.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Title')</label>
                            <input type="text" class="form-control" name="title">
                        </div>

                        <div class="form-group">
                            <label>@lang('Description')</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>@lang('Media Type')</label>
                            <select name="media_type" class="form-control">
                                <option value="image">Image</option>
                                <option value="video">Video</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>@lang('Media File')</label>
                            <input type="file" name="media_path" class="form-control">
                        </div>

                        <div class="form-check mt-2">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <label class="form-check-label">@lang('Set as Active')</label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">
                            <i class="fa fa-save"></i> @lang('Save')
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.announcement.update') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="editId">
                <div class="modal-header bg--primary text-white">
                    <h5 class="modal-title">@lang('Edit Announcement')</h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body row">
                    <div class="form-group col-md-6">
                        <label>@lang('Title')</label>
                        <input type="text" class="form-control" name="title" id="editTitle">
                    </div>

                    <div class="form-group col-md-6">
                        <label>@lang('Media Type')</label>
                        <select name="media_type" class="form-control" id="editMediaType">
                            <option value="image">@lang('Image')</option>
                            <option value="video">@lang('Video')</option>
                        </select>
                    </div>

                    <div class="form-group col-md-12">
                        <label>@lang('Description')</label>
                        <textarea class="form-control" name="description" id="editDescription" rows="3"></textarea>
                    </div>

                    <div class="form-group col-md-12">
                        <label>@lang('Media File (Optional)')</label>
                        <input type="file" name="media_path" class="form-control">
                    </div>

                    <div class="form-check mt-3">
                        <input type="checkbox" name="is_active" id="editActive" value="1">
                        <label class="form-check-label">@lang('Set as Active')</label>
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
    <button type="button" data-bs-toggle="modal" data-bs-target="#timeModal" class="btn btn-sm btn-outline--primary">
        <i class="las la-plus"></i> @lang('Add New')
    </button>
@endpush
@push('script')
<script>
    $('.editBtn').on('click', function () {
        const modal = $('#editModal');
        modal.find('#editId').val($(this).data('id'));
        modal.find('#editTitle').val($(this).data('title'));
        modal.find('#editDescription').val($(this).data('description'));
        modal.find('#editMediaType').val($(this).data('media_type'));
        if ($(this).data('is_active')) {
            modal.find('#editActive').prop('checked', true);
        } else {
            modal.find('#editActive').prop('checked', false);
        }
    });
</script>
@endpush

