@extends('frontend.layouts.app')


@section('title')
    {{-- {{ $data->title }} --}}
@endsection

@section('main')


    {{-- <div class="n-header" id="">


        <div class="n-header--bottom "
            style=" box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">

            <div class="container mt-4">

                <div class="tabs d-flex">
                    <button id="1tab-job-seeker" class="tab-button active" onclick="selectTab('job-seeker')">Job
                        Seeker</button>
                    <button id="1tab-jobs" class="tab-button" onclick="selectTab('jobs')">Jobs</button>
                    <button id="1tab-courses" class="tab-button" onclick="selectTab('courses')">Courses</button>
                </div>

                <!-- Search Filter Section -->
                <div class="row">
                    <div id="1form-job-seeker" class="col-12 search-form active">
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
                    <div id="1form-jobs" class="col-12 search-form ">
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
<style>
     .bookMeBtn {
            line-height: 1px !important;
            border-radius: 4px !important;
            padding: 10px 10px 10px !important;
            font-size: 12px !important;

        }
</style>

    <div class="row g-4">

        @forelse  ($candidates as $candidate)
        @if (optional($candidate->user)->username != '')
            <div
                class="@if (request('education') || request('gender') || request('experience') || request('skills')) col-md-6 col-lg-6 fade-in-bottom condition_class rt-mb-24
                     @else
                        col-md-6 col-lg-3 fade-in-bottom condition_class rt-mb-24 @endif">
                <a onclick="showCandidateProfileModal('{{ $candidate->user->username ?? '' }}')"
                    href="javascript:void(0);"
                    class="card jobcardStyle1 body-24 {{ !auth('user')->check() ? 'login_required' : '' }} h-100"
                    style="box-shadow: 0 14px 25px rgba(0, 0, 0, 0.1); position: relative;">
                    <div class="card-body">
                        <!-- Share Icon -->
                        <div style="position: absolute; top: 10px; right: 10px;">
                            <button type="button" onclick="copyToClipboard(event)" class="btn btn-sm btn-light" title="Share">
                                <x-svg.share-icon />
                            </button>
                        </div>

                        <div class="rt-single-icon-box icb-clmn-lg tw-reltaive">
                            <div class="icon-thumb tw-relative">
                                <div class="profile-image" style="">
                                    <img src="{{ $candidate->photo }}" alt="{{ __('candidate_image') }}">
                                </div>
                                <div class="tw-absolute tw-top-0 tw-left-1">
                                    @if ($candidate->status == 'available')
                                        <div class="tw-inline-flex">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="7" cy="7" r="6" fill="#2ecc71"
                                                    stroke="white" stroke-width="2">
                                                </circle>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="iconbox-content !tw-m-0">
                                <div class="job-mini-title">
                                    @if (auth('user')->check())
                                        <span>{{ $candidate->user->name ?? '' }}</span>
                                    @else
                                        <span
                                            class="login_required">{{ maskFullName($candidate->user->name ?? '') }}</span>
                                    @endif
                                </div>

                                @if ($candidate->country)
                                    <li style="color: #333">
                                        <span class="loacton text-gray-500 ">{{ $candidate->country }}</span>
                                    </li>
                                @endif

                                @if ($candidate->profession)
                                    <li style="color: #333">
                                        <span
                                            class="loacton text-gray-500 ">{{ $candidate->profession ? $candidate->profession->name : '' }}</span>
                                    </li>
                                @endif

                                <li style="color: #333">
                                    <span
                                        class="loacton text-gray-500 ">{{ $candidate->experience ? 'Experience:' . $candidate->experience->name : 'No Experience' }}</span>
                                </li>

                                @if ($candidate->status == 'available')
                                    <span
                                        class="body-font-4 mt-1 text-gray-900 d-block">{{ __('i_am_available') }}</span>
                                @endif
                                @if (auth('user')->check())
                                    <form action="{{ route('company.hire-request') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="candidate_id" value="{{ $candidate->id }}">
                                        <button type="submit" class="btn btn-primary bookMeBtn">Book Me</button>
                                    </form>
                                @else
                                    <span class="body-font-4 text-primary-500 login_required">{{ __('Book Me') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endif
        @empty
            <div class="col-12" id="loading-spinner">
                <div class="card text-center">
                    <x-not-found message="{{ __('no_data_found') }}" />
                </div>
            </div>
            @endforelse


    </div>
    <script>
        function copyToClipboard(event) {
            // Prevent event propagation to parent elements
            event.stopPropagation();

            const loginUrl = "{{ url('login') }}";
            navigator.clipboard.writeText(loginUrl).then(() => {
                alert('Login URL copied to clipboard: ' + loginUrl);
            }).catch(err => {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
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
    <!-- Modal -->
    <x-website.modal.candidate-profile-modal />




@endsection
