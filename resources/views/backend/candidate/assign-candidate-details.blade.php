{{-- @extends('backend.layouts.app') --}}
{{-- @extends('components.website.company.layout.app') --}}
@extends('backend.layouts.app')

@section('title')
    {{ __('candidate_list') }}
@endsection
@section('content')
    <div class="card shadow">
        <div class="card-header text-white d-flex justify-content-between align-items-center">
            <div class="input-group input-group-sm w-50">
                <input type="text" id="searchInput" class="form-control border rounded-start"
                    placeholder="{{ __('Search Candidates...') }}" onkeyup="filterTable()">
                <button class="btn btn-light border rounded-end" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <div class="d-flex align-items-center p-3">
            <div class="me-3">
                <img src="{{ $candidate->candidate->photo ?? 'https://via.placeholder.com/40' }}" alt="Photo"
                    class="rounded-circle shadow-sm" style="width: 50px; height: 50px;">
            </div>
            <div>
                <h6 class="mb-0">{{ $candidate->candidate->user->name }}</h6>
                <small class="text-muted">{{ $candidate->candidate->user->email ?? '' }}</small>
            </div>
            <a class="btn btn-primary ms-auto mr-2" href="#" role="button" data-bs-toggle="modal"
                data-bs-target="#addDetailsModal"
                onclick="setModalData('{{ $candidate->candidate->id }}', '{{ $candidate->job->id ?? '' }}')">
                Add Details
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless align-middle" id="candidatesTable">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-secondary">
                            <th class="text-start">{{ __('Date & Time') }}</th>
                            <th>{{ __('Status Name') }}</th>
                            <th>{{ __('Approved') }}</th>
                            <th>{{ __('Attachment') }}</th>

                            @if (auth()->user()->hasRole('superadmin'))
                                <th>{{ __('Action') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if ($assignCandidates->count() > 0)
                            @foreach ($assignCandidates as $candidatee)
                                <tr>
                                    <td class="text-start align-middle">
                                        {{ $candidatee->created_at->format('d M Y, h:i A') }}
                                    </td>
                                    <td class="fw-bold align-middle">{{ $candidatee->name ?? __('N/A') }}</td>
                                    <td class="fw-bold align-middle">
                                        {{ $candidatee->is_approved == 1 ? 'Yes' : 'No' }}
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('storage/' . $candidatee->attachments) }}" alt="Attachment"
                                                style="width: 100px; height: auto; object-fit: cover;" class="me-2">
                                            <a href="{{ asset('storage/' . $candidatee->attachments) }}"
                                                download="{{ basename($candidatee->attachments) }}"
                                                class="btn btn-sm btn-secondary">
                                                {{ __('Download') }}
                                            </a>
                                        </div>
                                    </td>
                                    @if (auth()->user()->hasRole('superadmin'))
                                        <td class="align-middle">
                                            <form action="{{ route('candidates.approve', $candidatee->id) }}"
                                                method="POST" style="display: inline-block;">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm"
                                                    {{ $candidatee->is_approved == 1 ? 'disabled' : '' }}>
                                                    {{ __('Approve') }}
                                                </button>
                                            </form>
                                            <form action="{{ route('candidates.disapprove', $candidatee->id) }}"
                                                method="POST" style="display: inline-block;">
                                                @csrf
                                                <button type="submit" class="btn btn-warning btn-sm"
                                                    {{ $candidatee->is_approved == 0 ? 'disabled' : '' }}>
                                                    {{ __('Disapprove') }}
                                                </button>
                                            </form>
                                            <form action="{{ route('candidates.delete', $candidatee->id) }}" method="POST"
                                                style="display: inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('{{ __('Are you sure?') }}')">
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <span class="text-muted">{{ __('No data found.') }}</span>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            @if ($assignCandidates->count())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $assignCandidates->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

    </div>

    <!-- Add Details Modal -->
    <div class="modal fade" id="addDetailsModal" tabindex="-1" aria-labelledby="addDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDetailsModalLabel">{{ __('Add Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addDetailsForm" method="POST" action="{{ route('addCandidateDetails') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="candidate_id" id="candidateId">
                        <input type="hidden" name="job_id" id="jobId">
                        <input type="hidden" name="admin_id" value="{{ $candidate->candidate->admin_id }}">


                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="attachments" class="form-label">{{ __('Attachments') }}</label>
                            <input type="file" class="form-control" id="attachments" name="attachments">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('candidatesTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                rows[i].style.display = found ? '' : 'none';
            }
        }

        function setModalData(candidateId, jobId) {
            document.getElementById('candidateId').value = candidateId;
            document.getElementById('jobId').value = jobId;
        }
    </script>

@endsection
