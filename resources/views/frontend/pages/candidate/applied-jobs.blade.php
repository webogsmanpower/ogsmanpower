@extends('components.website.candidate.layout.app')

@section('title')
{{ __('applied_jobs') }}
@endsection

@section('main')
<div class="dashboard-wrapper">
    <div class="container">
        <div class="row">
            <div class="col-lg-9">
                <div class="dashboard-right">
                    {{-- HEADER --}}
                    <div class="dashboard-right-header rt-mb-32 tw-mt-4 lg:tw-mt-0">
                        <div class="left-text m-0">
                            <h3 class="f-size-18 lh-1 m-0">
                                {{ __('applied_jobs') }}
                                <span class="text-gray-400">({{ $appliedJobs->total() }})</span>
                            </h3>
                        </div>
                    </div>

                    {{-- JOB LIST --}}
                    @if($appliedJobs->count() > 0)
                        <div class="accordion ll-accordion" id="accordionExample">
                            @foreach($appliedJobs as $job)
                                <div class="accordion-item tw-mt-5">
                                    <h2 class="accordion-header" id="heading{{ $job->id }}">
                                        <div class="accordion-button tw-flex tw-gap-2 tw-flex-wrap tw-justify-between tw-p-5">

                                            {{-- JOB INFO --}}
                                            <div class="lg:tw-w-[45%] tw-w-full">
                                                <div class="rt-single-icon-box tw-flex-col lg:tw-flex-row tw-items-start lg:tw-items-center tw-gap-5">
                                                    <div class="tw-w-[56px] tw-h-[56px]">
                                                        <img class="tw-w-[56px] tw-h-[56px] tw-rounded-md"
                                                            src="{{ $job->company && $job->company->logo_url ? asset($job->company->logo_url) : asset('images/default-company.png') }}"
                                                            alt="logo" draggable="false">
                                                    </div>
                                                    <div class="iconbox-content">
                                                        <div class="tw-flex tw-flex-col tw-gap-3">
                                                            <div class="tw-flex tw-flex-col lg:tw-flex-row tw-gap-2 tw-items-start lg:tw-items-center">
                                                                <a class="tw-text-[#18191C] tw-text-base tw-font-medium"
                                                                    href="{{ route('website.job.details', $job->slug ?? '#') }}">
                                                                    {{ $job->company->user->name ?? 'Company Name' }}
                                                                </a>
                                                                @if($job->job_type)
                                                                    <span class="tw-py-1 tw-inline-flex tw-px-3 tw-text-sm rounded-pill bg-primary-50 text-primary-500">
                                                                        {{ $job->job_type->name }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <div class="tw-text-sm tw-text-[#5E6670] tw-flex tw-flex-col sm:tw-flex-row tw-gap-4 sm:tw-items-center text-gray-600">
                                                                @if($job->country)
                                                                <span class="info-tools tw-flex tw-items-center tw-gap-1.5">
                                                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <path d="M15.75 7.5C15.75 12.75 9 17.25 9 17.25C9 17.25 2.25 12.75 2.25 7.5C2.25 5.70979 2.96116 3.9929 4.22703 2.72703C5.4929 1.46116 7.20979 0.75 9 0.75C10.7902 0.75 12.5071 1.46116 13.773 2.72703C15.0388 3.9929 15.75 5.70979 15.75 7.5Z" stroke="#939AAD" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                        <path d="M9 9.75C10.2426 9.75 11.25 8.74264 11.25 7.5C11.25 6.25736 10.2426 5.25 9 5.25C7.75736 5.25 6.75 6.25736 6.75 7.5C6.75 8.74264 7.75736 9.75 9 9.75Z" stroke="#939AAD" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                    </svg>
                                                                    {{ $job->country }}
                                                                </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- DATE --}}
                                            <div class="tw-whitespace-nowrap tw-text-sm tw-text-[#5E6670]">
                                                {{ $job->pivot->created_at ? $job->pivot->created_at->format('M d, Y') : '' }}
                                            </div>

                                            {{-- STATUS --}}
                                            <div class="text-{{ $job->deadline_active ? 'success' : 'danger' }}-500">
                                                @if($job->deadline_active)
                                                    <div class="tw-flex tw-gap-1.5 tw-text-sm tw-items-center">
                                                        Active
                                                    </div>
                                                @else
                                                    <div class="tw-flex tw-gap-1.5 tw-items-center">
                                                        Expired
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- VIEW DETAILS BUTTON --}}
                                            <div class="db-job-btn-wrap d-flex justify-content-end">
                                                <button type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapse{{ $job->id }}"
                                                    aria-expanded="false"
                                                    aria-controls="collapse{{ $job->id }}"
                                                    class="btn bg-gray-50 text-primary-500">
                                                    {{ __('view_details') }}
                                                </button>
                                            </div>

                                        </div>
                                    </h2>

                                    {{-- COLLAPSE DETAIL --}}
                                    <div id="collapse{{ $job->id }}" class="accordion-collapse collapse"
                                        aria-labelledby="heading{{ $job->id }}"
                                        data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            <h4>{{ __('cover_letter') }}</h4>
                                            {!! $job->cover_letter ?? '<p>No cover letter submitted.</p>' !!}
                                            @if($job->cv_file)
                                                <a href="{{ asset($job->cv_file) }}" download="{{ basename($job->cv_file) }}">
                                                    Download Resume
                                                </a>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            @endforeach
                        </div>

                        {{-- PAGINATION --}}
                        <div class="mt-4">
                            {{ $appliedJobs->links('vendor.pagination.frontend') }}
                        </div>

                    @else
                        <div class="empty-state text-center py-5">
                            <h4>No Applied Jobs Yet</h4>
                            <p>Once you apply for jobs, they will appear here.</p>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection