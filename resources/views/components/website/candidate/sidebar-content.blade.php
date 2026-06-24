<style>
    .profile_section {
        display: flex;
        flex-direction: column;
        /* Stack items vertically */
        align-items: center;
        /* Center items horizontally */
        justify-content: center;
        /* Center items vertically */
        padding: 10px;
    }

    .profile_section p {
        color: #fff
    }

    .profile_section h6 {
        color: #fff
    }

    .avatar {
        width: 120px;
        height: 120px;
        background-color: #fff;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        margin-bottom: 10px;
        /* Space between avatar and text */
    }

    /* .avatar img {
        width: 80px;
        height: 80px;
    } */

    .profile_section p {
        margin: 5px 0;
        /* Adjust the spacing between each line */
        text-align: center;
        /* Center text inside the container */
    }
    .profile-image {
            width: 100px;
            /* Reduced from 120px */
            height: auto;
            border-radius: 50%;
            /* border: 2px solid #147ce4; */
            margin: 10px auto;
            display: block;
        }

    .progress {
        height: 15px;
        background-color: #f3f3f3;
        border-radius: 5px;
        overflow: hidden;
        margin-top: 5px;
    }

    .progress-bar {
        height: 100% !important;
        background-color: #00cc44 !important; /* Green color for completed part */
        width: {{ $completionPercentage }}% !important;
    }

    h4 {
        margin-bottom: 5px;
    }
</style>
<aside id="sidebar" class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('candidate.dashboard') }}" class="brand-link">
        <img src="{{ $setting->favicon_image_url }}" alt="{{ __('logo') }}" class="elevation-3">
        <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
    </a>
    <div class="profile_section">

        <div class="avatar">
            <img src="{{ asset(auth()->user()->candidate->photo) }}" alt="image" class="profile-image">
        </div>

        <div>
            <p class="">{{ auth()->user()->name }}</p>
            <p class="">
                <i class="fas fa-envelope"></i> {{ auth()->user()->email }}
            </p>
            <p class="">
                <i class="fas fa-phone"></i> {{ auth()->user()->whatsapp }}
            </p>

        </div>
        <h6>Profile Status</h6>
        <div class="progress-bar" role="progressbar" style="width: {{ $completionPercentage }}%;"
            aria-valuenow="{{ $completionPercentage }}" aria-valuemin="0" aria-valuemax="100">
            {{ number_format($completionPercentage, 2) }}%
        </div>
    </div>


    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-nav-wrapper">


            <!-- Sidebar Menu -->
            <nav class="sidebar-main-nav mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.dashboard') ? true : false" route="candidate.dashboard" icon="ph-person" path="candidate.dashboard"
                        plus_icon="">
                        {{ __('Dashboard') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.view.cv') ? true : false" route="candidate.view.cv" icon="ph-gear"
                        path="candidate.view.cv" plus_icon="">
                        {{ __(' Bilangual CV') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.appliedjob') ? true : false" route="candidate.appliedjob" icon="ph-suitcase-simple"
                        path="candidate.appliedjob" plus_icon="">
                        {{ __('applied_jobs') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.bookmark') ? true : false" route="candidate.bookmark" icon="ph-bookmark-simple"
                        path="candidate.bookmark" plus_icon="">
                        {{ __('favorite_jobs') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.job.alerts') ? true : false" route="candidate.job.alerts" icon="ph-bell-ringing"
                        path="candidate.job.alerts" plus_icon="">
                        {{ __('job_alert') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.plan') ? true : false" route="candidate.plan" icon="ph-bell-ringing"
                        path="candidate.plan" plus_icon="">
                        {{ __('plans') }}
                    </x-admin.sidebar-list>

                    {{-- <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.messages') ? true : false" route="candidate.messages" icon="ph-chat"
                        path="candidate.messages" plus_icon="">
                        {{ __('Message') }}
                    </x-admin.sidebar-list> --}}
                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.document') ? true : false" route="candidate.document" icon="ph-bell-ringing"
                        path="candidate.document" plus_icon="">
                        {{ __('Document') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.setting') ? true : false" route="candidate.setting" icon="ph-gear"
                        path="candidate.setting" plus_icon="">
                        {{ __('settings') }}
                    </x-admin.sidebar-list>




                    {{-- <x-admin.sidebar-list :linkActive=" request()->routeIs('logout') ? 'active' : '' " route="logout" icon="ph-sign-out" path="logout"
                        plus_icon=""
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        {{ __('log_out') }}

                        <!-- Hidden Logout Form -->
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </x-admin.sidebar-list> --}}




                </ul>


                <ul class="sidebar-menu">

                    <li>
                        <a class="{{ request()->routeIs('logout') ? 'active' : '' }}" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <span class="button-content-wrapper ">
                                <span class="button-icon align-icon-left tw-flex tw-items-center">
                                    <i class="ph-sign-out"></i>
                                </span>
                                <span class="button-text">
                                    {{ __('log_out') }}
                                </span>
                            </span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </li>
                </ul>
            </nav>
            <!-- Sidebar Menu -->

        </div>
    </div>
    <!-- /.sidebar -->
</aside>
