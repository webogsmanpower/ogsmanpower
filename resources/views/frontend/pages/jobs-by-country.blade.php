@extends('frontend.layouts.app')

@section('description')
    @php
        $data = metaData('candidates');
    @endphp
    {{ $data->description }}
@endsection
@section('og:image')
    {{ asset($data->image) }}
@endsection
@section('title')
    {{ $data->title }}
@endsection

@section('main')
    {{-- <div class="n-header" id="" >


        <div class="n-header--bottom "
            style=" box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">

            <div class="container mt-4">

                <div class="tabs d-flex">
                    <button id="1tab-job-seeker" class="tab-button " onclick="selectTab('job-seeker')">Job
                        Seeker</button>
                    <button id="1tab-jobs" class="tab-button active" onclick="selectTab('jobs')">Jobs</button>
                    <button id="1tab-courses" class="tab-button" onclick="selectTab('courses')">Courses</button>
                </div>

                <!-- Search Filter Section -->
                <div class="row">
                    <div id="1form-job-seeker" class="col-12 search-form ">
                        <!-- Search Form -->
                        <form class="d-flex align-items-center flex-column flex-md-row"
                            action="{{ route('website.filterJobSeeker') }}" method="GET" style="width: 100%">
                            <!-- Input Field for Title -->
                            <div class="input-group" style="flex: 1;">
                                <span class="input-group-text bg-light" id="basic-addon1">
                                    <x-svg.briefcase-icon />
                                </span>
                                <input type="text" name="keyword" class="form-control" placeholder="Enter Keyword"
                                    aria-label="Title" aria-describedby="basic-addon1" required>
                            </div>

                            <!-- Search Button -->
                            <button class="btn  ms-0 ms-md-2 mt-2 mt-md-0 search-btn-mobile" type="submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </form>
                    </div>
                    <div id="1form-jobs" class="col-12 search-form active">
                        <!-- Search Form -->
                        <form class="d-flex align-items-center flex-column flex-md-row"
                            action="{{ route('website.filterJobs') }}" method="GET" style="width: 100%">
                            <!-- Input Field for Title -->
                            <div class="input-group" style="flex: 1;">
                                <span class="input-group-text bg-light" id="basic-addon1">
                                    <x-svg.briefcase-icon />
                                </span>
                                <input type="text" name="keyword" class="form-control" placeholder="Enter Keyword"
                                    aria-label="Title" aria-describedby="basic-addon1" required>
                            </div>

                            <!-- Search Button -->
                            <button class="btn btn-primary ms-0 ms-md-2 mt-2 mt-md-0 search-btn-mobile" type="submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </form>
                    </div>
                    <div id="1form-courses" class="col-12 search-form ">
                        <!-- Search Form -->
                        <form class="d-flex align-items-center flex-column flex-md-row"
                            action="{{ route('website.filterJobSeeker') }}" method="GET" style="width: 100%">
                            <!-- Input Field for Title -->
                            <div class="input-group" style="flex: 1;">
                                <span class="input-group-text bg-light" id="basic-addon1">
                                    <x-svg.briefcase-icon />
                                </span>
                                <input type="text" name="keyword" class="form-control" placeholder="Enter Keyword"
                                    aria-label="Title" aria-describedby="basic-addon1" required>
                            </div>

                            <!-- Search Button -->
                            <button class="btn btn-primary ms-0 ms-md-2 mt-2 mt-md-0 search-btn-mobile" type="submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
        <div class="rt-mobile-menu-overlay"></div>
        <div class="sidebar-overlay"></div>
    </div> --}}

        <div class="job-filter-overlay"></div>

        <div class="joblist-content">
            <div class="container">


                 <!-- google adsense area end -->
                <div class="row mt-5">
                    {{-- <h5>{{ __('latest_jobs') }}</h5> --}}

                    @php
                        // $mix_jobs = $all_jobs && count($all_jobs) ? $all_jobs : $jobs;
                        // $jobId = 0;
                    @endphp

                    @forelse ($jobs as $job)
                        <div class="col-xl-4 col-md-6 fade-in-bottom rt-mb-24 cat-1 cat-3">
                            <x-website.job.job-card :job="$job" />
                        </div>
                        @if (isset($job->id))
                            @php $jobId =  $job->id; @endphp
                        @endif
                    @empty
                        <div class="col-12" id="loading-spinner">
                            <div class="card text-center">
                                <x-not-found message="{{ __('no_data_found') }}" />
                            </div>
                        </div>
                    @endforelse
                    <div id="mix-job" class="row"></div>

                    {{-- @if (!$mix_jobs->isEmpty())
                        <button id="load-more-button" data-page="1" data-id="{{ $jobId }}"
                            class="newsButton btn btn-primary px-4 py-2 m-auto">{{ __('load_more') }}</button>
                    @endif --}}
                </div>
            </div>
        </div>
    </form>

    <div class="rt-spacer-100 rt-spacer-md-50"></div>

    {{-- Subscribe Newsletter --}}
    <x-website.subscribe-newsletter />

    <!-- ===================================== -->
    <div class="modal fade cadidate-modal" id="aemploye-profile" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-modal="true" role="dialog">
        <div class="modal-dialog modal-wrapper modal-lg">
            <div class="modal-content">
                <div class="modal-body">
                    <h5 class="text-center">
                        {{ __('save_to') }}
                    </h5>
                    <div class="row border-top">
                        <div class="col-md-12" id="categoryList">
                        </div>
                        <div class="col-md-12 tw-mt-3">
                            <div class="saved-candidate">
                                <a class="btn btn-primary" target="_blank"
                                    href="{{ route('company.bookmark.category.index') }}">
                                    <span>{{ __('create_category') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <x-website.modal.candidate-profile-modal />

    <script>
        function selectTab(tabName) {
            // Remove 'active' class from all tabs and forms
            // console.log(document.getElementById(`tab-${tabName}`));

            document.querySelectorAll('.tab-button').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.search-form').forEach(form => form.classList.remove('active'));


            // Add 'active' class to selected tab and corresponding form
            document.getElementById(`1tab-${tabName}`).classList.add('active');
            document.getElementById(`1form-${tabName}`).classList.add('active');
            // console.log(document.getElementById(`tab-${tabName}`));

        }
    </script>

@endsection
