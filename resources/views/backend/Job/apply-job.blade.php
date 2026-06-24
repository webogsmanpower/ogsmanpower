@extends('backend.layouts.app')
@section('title')
    {{ __('job_list') }}
@endsection
@section('content')

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-responsive p-0 m-0">
                    <div class="row">
                        <div class="col-sm-12">
                            <!-- Updated Table -->
                            <table class="ll-table table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th width="10%">{{ __('name') }}</th>
                                        <th width="10%">{{ __('category') }}/{{ __('role') }}</th>
                                        <th width="10%">{{ __('salary') }}</th>
                                        <th width="10%">{{ __('deadline') }}</th>
                                        <th width="10%">{{ __('Ip Address') }}</th>
                                        <th width="10%">{{ __('Ip Country') }}</th>
                                        @if (userCan('job.update') || userCan('job.delete'))
                                            <th width="10%">{{ __('action') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($jobs->count() > 0)
                                        @foreach ($jobs as $job)
                                            <tr>
                                                <td tabindex="0">
                                                    <a href="{{ route('website.job.details.hr', $job->slug) }}" class="company">
                                                        @if ($job->company)
                                                            <img src="{{ asset($job->company->logo_url) }}" alt="image">
                                                        @else
                                                            <x-svg.briefcase-logo />
                                                        @endif
                                                        <div>
                                                            <h2>{{ $job->title }}</h2>
                                                            <p>
                                                                <span>{{ $job->company && $job->company->user ? $job->company->user->name : $job->company_name }}</span>
                                                                <span>·</span>
                                                                <span>{{ $job->job_type->name ?? '' }}</span>
                                                                @if ($job->is_remote)
                                                                    <span>·</span>
                                                                    <span>{{ __('remote') }}</span>
                                                                @endif
                                                            </p>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td tabindex="0">
                                                    <div class="category">
                                                        <x-svg.table-layer />
                                                        <div>
                                                            <h3>{{ $job->category->name }}</h3>
                                                            <p>{{ $job->role->name }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td tabindex="0">
                                                    <div class="category">
                                                        <x-svg.table-money />
                                                        <div>
                                                            @if ($job->salary_mode == 'range')
                                                                <h3 class='bold'>
                                                                    {{ getFormattedNumber($job->min_salary) }} -
                                                                    {{ getFormattedNumber($job->max_salary) }}
                                                                    {{ currentCurrencyCode() }}
                                                                </h3>
                                                            @else
                                                                <h3 class="bold">{{ $job->custom_salary }}</h3>
                                                            @endif
                                                            <p>{{ $job->salary_type->name }} </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td tabindex="0">
                                                    @php
                                                        $dateTime = new DateTime();
                                                        $formattedDateTime = $dateTime->format('Y-m-d');
                                                    @endphp
                                                    @if ($job->deadline <= $formattedDateTime)
                                                        {{ date('j F, Y', strtotime($job->deadline)) }}
                                                        <p class="text-danger mt-2">
                                                            <small>{{ __('deadline_expired') }}</small>
                                                        </p>
                                                    @else
                                                        {{ date('j F, Y', strtotime($job->deadline)) }}
                                                    @endif
                                                </td>
                                                <td>{{ $job->ip_address }}</td>
                                                <td>{{ $job->ip_country }}</td>
                                                <td>
                                                    <!-- Button to trigger modal -->
                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                        data-bs-target="#applyJobModal-{{ $job->id }}" data-job-id="{{ $job->id }}">
                                                        Apply Job
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Modal for each job -->
                                            <div class="modal fade" id="applyJobModal-{{ $job->id }}" tabindex="-1"
                                                aria-labelledby="applyJobModalLabel-{{ $job->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="{{ route('save.job') }}" method="POST" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="applyJobModalLabel-{{ $job->id }}">Apply for Job</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>

                                                            <div class="modal-body">
                                                                <input type="hidden" id="jobId" name="job_id" value="{{ $job->id }}">
                                                                <div class="mb-3">
                                                                    <label for="applicationType" class="form-label">Application Type</label>
                                                                    <select id="applicationType" name="application_type" class="form-select">
                                                                        <option value="with_candidate">Apply With Candidate</option>
                                                                        <option value="without_candidate">Apply Without Candidate</option>
                                                                    </select>
                                                                </div>

                                                                <!-- Section for 'With Candidate' -->
                                                                <div id="withCandidateSection" class="mb-3">
                                                                    <label for="candidate" class="form-label">Select Candidate</label>
                                                                    <select id="candidate" name="candidate_id" class="form-select">
                                                                        <option value="">-- Select a Candidate --</option>
                                                                        @foreach ($candidates as $candidate)
                                                                        <option value="{{ $candidate->id }}"
                                                                            @if($job->allAppliedJobs->contains('candidate_id', $candidate->id)) disabled @endif>
                                                                            {{ $candidate->user->name }}
                                                                        </option>
                                                                        @endforeach
                                                                    </select>

                                                                    <label for="resume_format" class="form-label">Select Resume Format</label>
                                                                    <select id="resume_format" class="form-select" name="resume_format">
                                                                        <option value="">{{ __('select_one') }}</option>
                                                                        <option value="general_format">General Format</option>
                                                                        <option value="driver_format">Driver Format</option>
                                                                        <option value="guard_format">Security Guard Format</option>
                                                                        <option value="beautician_format">Beautician Format</option>
                                                                        <option value="web_developer_format">Professional Format</option>
                                                                        <option value="bike_rider_format">Bike Rider Format</option>
                                                                        <option value="bilangual_format">Bilingual Format</option>
                                                                    </select>
                                                                </div>

                                                                <!-- Section for 'Without Candidate' -->
                                                                <div id="withoutCandidateSection" class="mb-3" style="display: none;">
                                                                    <label for="generalCV" class="form-label">Choose CV</label>
                                                                    <input type="file" id="generalCV" name="cv" class="form-control">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" class="btn btn-primary">Submit Application</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="6" class="text-center">{{ __('no_data_found') }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>

                            <!-- Modal -->




                        </div>
                    </div>
                    @if ($jobs->total() > $jobs->perPage())
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $jobs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script>
    document.getElementById('applicationType').addEventListener('change', function () {
        const applicationType = this.value;
        const withCandidateSection = document.getElementById('withCandidateSection');
        const withoutCandidateSection = document.getElementById('withoutCandidateSection');

        if (applicationType === 'with_candidate') {
            withCandidateSection.style.display = 'block';
            withoutCandidateSection.style.display = 'none';
            // Enable "With Candidate" fields
            document.getElementById('candidate').disabled = false;
            document.getElementById('resume_format').disabled = false;
            document.getElementById('candidateCV').disabled = false;
            // Disable "Without Candidate" fields
            document.getElementById('generalCV').disabled = true;
        } else if (applicationType === 'without_candidate') {
            withCandidateSection.style.display = 'none';
            withoutCandidateSection.style.display = 'block';
            // Enable "Without Candidate" fields
            document.getElementById('generalCV').disabled = false;
            // Disable "With Candidate" fields
            document.getElementById('candidate').disabled = true;
            document.getElementById('resume_format').disabled = true;
            document.getElementById('candidateCV').disabled = true;
        }
    });
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>


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

        function RemoveFilter(id) {
            $('#' + id).val('');
            $('#formSubmit').submit();
        }
    </script>


    <script>
        // Add this script in your HTML file or separate JS file
        $(document).ready(function() {
            // Add this script in your HTML file or separate JS file
            $(document).ready(function() {
                // Select all checkboxes
                $('#select-all').click(function(event) {
                    if (this.checked) {
                        $('.job-checkbox').each(function() {
                            this.checked = true;
                        });
                    } else {
                        $('.job-checkbox').each(function() {
                            this.checked = false;
                        });
                    }
                });

                // Handle delete selected button click
                $('#delete-selected').click(function() {
                    var selectedJobs = [];
                    $('.job-checkbox:checked').each(function() {
                        selectedJobs.push($(this).val());
                    });

                    function showSuccessMessage(message) {
                        toastr.success(message);
                    }
                    // AJAX request to delete selected jobs
                    $.ajax({
                        url: '{{ route('jobs.deleteSelected') }}',
                        data: {
                            ids: selectedJobs
                        },
                        success: function(response) {

                            showSuccessMessage('Job deleted successfully');
                            window.location.reload()
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                        }
                    });
                });
            });

        });
        $('#assignJobModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var jobId = button.data('job-id'); // Extract job ID from data-* attribute
            var form = $(this).find('form'); // Find the form in the modal

            // Update form action with the job ID
            var action = "{{ route('job.assign-roles', ['id' => ':id']) }}".replace(':id', jobId);
            form.attr('action', action);
        });
    </script>
@endsection

@section('style')
    <style>
        .select2-results__option[aria-selected=true] {
            display: none;
        }

        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
            color: #fff;
            border: 1px solid #fff;
            background: #007bff;
            border-radius: 30px;
        }

        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff;
        }

        /* Style  radio button */
        .expired_radio::after {
            content: "";
            display: inline-block;
            border-radius: 50%;
            margin-right: 8px;
            background-color: red;
        }
    </style>
@endsection
