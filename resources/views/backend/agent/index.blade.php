@extends('backend.layouts.app')
@section('title')
    {{ __('hr list') }}
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title line-height-36">{{ __('Hr Solutons') }}</h3>
                        <div>
                            {{-- <div class="btn-group">
                                <a href="#" class="btn bg-primary">
                                    <i class="fas fa-download mr-1"></i> Export
                                </a>
                                <button type="button" class="btn bg-primary dropdown-toggle dropdown-toggle-split"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="">CSV</a>
                                    <a class="dropdown-item" href="">PDF</a>
                                    <a class="dropdown-item" href="">Excel</a>
                                    <!-- Add more options for different export formats if needed -->
                                </div>
                            </div> --}}


                            {{-- <a href="{{ route('agent.create') }}" class="btn bg-primary"><i
                                        class="fas fa-plus mr-1"></i> {{ __('create') }}
                                </a>

                            @if (request('keyword') || request('ev_status') || request('sort_by'))
                                <a href="{{ route('company.index') }}" class="btn bg-danger"><i
                                        class="fas fa-times"></i>&nbsp; {{ __('clear') }}
                                </a>
                            @endif --}}
                        </div>
                    </div>
                </div>

                {{-- Filter  --}}
                <form id="formSubmit" action="{{ route('agent.index') }}" method="GET" onchange="this.submit();">
                    <div class="card-body border-bottom row">
                        <div class="col-lg-4 col-md-6 col-12">
                            <label>{{ __('search') }}</label>
                            <input name="keyword" type="text" placeholder="{{ __('search') }}" class="form-control"
                                value="{{ request('keyword') }}">
                        </div>
                        <div class="col-lg-4 col-md-6 col-12">
                            <label>{{ __('email_verification') }}</label>
                            <select name="ev_status" class="form-control select2bs4 w-100-p">
                                <option value="">
                                    {{ __('all') }}
                                </option>
                                <option {{ request('ev_status') == 'true' ? 'selected' : '' }} value="true">
                                    {{ __('verified') }}
                                </option>
                                <option {{ request('ev_status') == 'false' ? 'selected' : '' }} value="false">
                                    {{ __('not_verified') }}
                                </option>
                            </select>
                        </div>
                        <div class="col-lg-4 col-md-6 col-12">
                            <label>{{ __('sort_by') }}</label>
                            <select name="sort_by" class="form-control select2bs4 w-100-p">
                                <option {{ !request('sort_by') || request('sort_by') == 'latest' ? 'selected' : '' }}
                                    value="latest" selected>
                                    {{ __('latest') }}
                                </option>
                                <option {{ request('sort_by') == 'oldest' ? 'selected' : '' }} value="oldest">
                                    {{ __('oldest') }}
                                </option>
                            </select>
                        </div>
                    </div>
                </form>

                {{-- Table  --}}
                <div class="card-body table-responsive p-0">
                    <table class="ll-table table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>{{ __('Hr Solutions') }}</th>
                                <th>{{ __('role') }}/{{ __('position') }}</th>
                                <th width="10%">{{ 'Approved Profile' }}</th>
                                {{-- <th>{{ __('email_verification') }}</th> --}}
                                <th width="12%">{{ __('action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($agents->count() > 0)
                                @foreach ($agents as $agent)
                                    <tr>
                                        <td tabindex="0">
                                            <a href="{{ route('agent.show', $agent->id) }}" class="company">
                                                <img src="{{ $agent->image_url }}" alt="image">
                                                <div>
                                                    <h2>{{ $agent->name }}</h2>
                                                    <p>{{ $agent->email }}</p>
                                                </div>
                                            </a>
                                        </td>
                                        <td tabindex="0">
                                            @if ($agent->getRoleNames()->isNotEmpty())
                                                {{ $agent->getRoleNames()->map(fn($role) => ucfirst($role))->implode(', ') }}
                                            @else
                                                No Role Assigned
                                            @endif
                                        </td>
                                        <td tabindex="0">
                                            <a href="javascript:void(0)" class="active-status">
                                                <label class="switch">
                                                    <input data-id="{{ $agent->id }}" type="checkbox"
                                                        class="success status-switch change-active-status"
                                                        {{ $agent->is_profile_approved == 1 ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                                <p class="{{ $agent->is_profile_approved == 1 ? 'active' : '' }}"
                                                    id="status_{{ $agent->id }}">
                                                    {{ $agent->is_profile_approved == 1 ? __('Approved') : __('Unapproved') }}
                                                </p>
                                            </a>
                                        </td>

                                    
                                        <td>



                                            <form action="{{ route('agent.destroy', $agent->id) }}" method="POST"
                                                class="d-inline">
                                                @method('DELETE')
                                                @csrf
                                                <button
                                                    onclick="return confirm('{{ __('are_you_sure_you_want_to_delete_this_item') }}');"
                                                    class="btn ll-p-0">
                                                    <x-svg.table-delete />
                                                </button>
                                            </form>
                                            <button class="btn btn-sm btn-outline-primary"
                                                onclick="openChangeRoleModal(
                                                {{ $agent->id }},
                                                '{{ $agent->getRoleNames()->first() }}',
                                                '{{ $agent->name }}',
                                                '{{ $agent->image_url }}'
                                            )">
                                                <i class="bi bi-pencil-square"></i> Change Role
                                            </button>

                                        </td>

                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="8">
                                        {{ __('no_data_found') }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    @if ($agents->count())
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $agents->onEachSide(1)->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- Role Change Modal -->
    <!-- Role Change Modal -->
    <div class="modal fade" id="changeRoleModal" tabindex="-1" aria-labelledby="changeRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form id="changeRoleForm" method="POST" action="{{ route('agent.change-role') }}" class="needs-validation"
                novalidate>
                @csrf
                <input type="hidden" name="agent_id" id="agent_id" >
                <div class="modal-content shadow-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="changeRoleModalLabel">
                            <i class="bi bi-person-check"></i> Change Role
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 text-center mb-3">
                                <img id="agentImage" src="" alt="Agent Profile Image" class="rounded-circle shadow"
                                    width="100" height="100">
                                <h5 id="agentName" class="mt-2"></h5>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="roles" class="form-label">Select Role</label>
                                    <select class="form-select shadow-sm" id="roles" name="role" required>
                                        <option value="" disabled selected>Select a role</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a role.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>


@endsection

@section('style')
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 35px;
            height: 19px;
        }

        /* Hide default HTML checkbox */
        .switch input {
            display: none;
        }

        /* The slider */
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 15px;
            width: 15px;
            left: 3px;
            bottom: 2px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
        }

        input.success:checked+.slider {
            background-color: #28a745;
        }

        input:checked+.slider:before {
            -webkit-transform: translateX(15px);
            -ms-transform: translateX(15px);
            transform: translateX(15px);
        }

        /* Rounded sliders */
        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        .modal-content {
            border-radius: 12px;
            overflow: hidden;
        }

        .modal-header {
            border-bottom: none;
        }

        .modal-footer {
            border-top: none;
        }

        #agentImage {
            border: 4px solid #e9ecef;
        }

        #agentName {
            font-weight: 600;
            color: #333;
        }
    </style>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js"></script>
    <script src="{{ asset('backend') }}/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
    <script>
        $('.email-verification-switch').on('change', function() {
            var status = $(this).prop('checked') == true ? 1 : 0;
            var id = $(this).data('userid');

            $.ajax({
                type: "GET",
                dataType: "json",
                url: '{{ route('company.verify.change') }}',
                data: {
                    'status': status,
                    'id': id
                },
                success: function(response) {
                    toastr.success(response.message, 'Success');
                }
            });
            if (status == 1) {
                $(`#verification_status_${id}`).text("{{ __('verified') }}")
            } else {
                $(`#verification_status_${id}`).text("{{ __('unverified') }}")
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            validate();
            $('#title').keyup(validate);
        });

        function validate() {
            if ($('#title')?.val()?.length > 0) {
                $('#crossB').removeClass('d-none');
            } else {
                $('#crossB').addClass('d-none');
            }
        }

        $('#formSubmit').on('change', function() {
            $(this).submit();
        });

        function RemoveFilter(id) {
            $('#' + id).val('');
            $('#formSubmit').submit();
        }
        document.addEventListener('DOMContentLoaded', function() {
            const switches = document.querySelectorAll('.change-active-status');

            switches.forEach((switchElement) => {
                switchElement.addEventListener('change', function() {
                    const agentId = this.getAttribute('data-id');
                    const isChecked = this.checked;

                    fetch('{{ route('agent.toggleProfileStatus') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                id: agentId
                            }),
                        })
                        .then((response) => response.json())
                        .then((data) => {
                            if (data.status) {
                                const statusElement = document.getElementById(
                                    `status_${agentId}`);
                                statusElement.textContent = data.is_profile_approved ?
                                    'Approved' : 'Unapproved';
                                statusElement.classList.toggle('active', data
                                    .is_profile_approved);
                                alert(data.message);
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch((error) => {
                            console.error('Error:', error);
                        });
                });
            });
        });
    </script>
    <script>
        function openChangeRoleModal(agentId, currentRole, agentName, agentImageUrl) {
            // Populate the modal with the current data
            document.getElementById('agent_id').value = agentId;
            document.getElementById('roles').value = currentRole || '';
            document.getElementById('agentName').textContent = agentName;
            document.getElementById('agentImage').src = agentImageUrl || '/path/to/default-image.jpg';

            // Show the modal
            var myModal = new bootstrap.Modal(document.getElementById('changeRoleModal'), {});
            myModal.show();
        }
    </script>
@endsection
