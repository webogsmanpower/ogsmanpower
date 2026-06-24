<style>
   .profile_section {
    display: flex;
    flex-direction: column;
    align-items: center; /* Center items horizontally */
    justify-content: center; /* Center items vertically */
    padding: 10px;
}

.profile_section p {
    color: #fff;
    margin: 5px 0; /* Adjust spacing between lines */
    text-align: center; /* Center text inside the container */
    white-space: nowrap; /* Prevent line breaks */
    overflow: hidden; /* Hide overflow text */
    text-overflow: ellipsis; /* Add ellipsis (...) if text overflows */
    max-width: 200px; /* Set a maximum width for the text */
}

.profile_section h6 {
    color: #fff;
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
    margin-bottom: 10px; /* Space between avatar and text */
}


    /* .avatar img {
        width: 80px;
        height: 80px;
    } */


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
        background-color: #00cc44 !important;
        /* Green color for completed part */
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
            <img src="{{ asset(auth()->user()->company->logo) }}" alt="image" class="profile-image">
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

    </div>


    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-nav-wrapper">


            <!-- Sidebar Menu -->
            <nav class="sidebar-main-nav mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <x-admin.sidebar-list :linkActive="Route::is('company.dashboard') ? true : false" route="company.dashboard" parameter=""
                        path="company.dashboard" plus_icon="" icon="fas fa-tachometer-alt">
                        {{ __('dashboard') }}
                    </x-admin.sidebar-list>
                    {{-- <x-admin.sidebar-list :linkActive="Route::is('website.home') ? true : false" route="website.home" parameter=""
                    path="website.home" plus_icon="" icon="fas fa-home-alt">
                    {{ __('home') }}
                </x-admin.sidebar-list> --}}
                    <x-admin.sidebar-list :linkActive="Route::is('company.myjob') ? true : false" route="company.myjob" parameter="" path="company.myjob"
                        plus_icon="" icon="fas fa-tachometer-alt">
                        {{ __('my_jobs') }}
                    </x-admin.sidebar-list>


                    @if (!$setting->edited_job_auto_approved)
                        <x-admin.sidebar-list :linkActive="request()->routeIs('company.pending.edited.jobs') ? true : false" route="company.pending.edited.jobs" icon="ph-person"
                            path="company.pending.edited.jobs" plus_icon="">
                            {{ __('pending_edited_jobs') }}
                        </x-admin.sidebar-list>
                    @endif

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.job.create') ? true : false" route="company.job.create" icon="ph-suitcase-simple"
                        path="company.job.create" plus_icon="">
                        {{ __('post_a_job') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.bookmark') ? true : false" route="company.bookmark" icon="ph-bookmark-simple"
                        path="company.bookmark" plus_icon="">
                        {{ __('saved_candidate') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.job.alerts') ? true : false" route="candidate.job.alerts" icon="ph-bell-ringing"
                        path="candidate.job.alerts" plus_icon="">
                        {{ __('custom_questions') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.plan') ? true : false" route="company.plan" icon="ph-bell-ringing"
                        path="company.plan" plus_icon="">
                        {{ __('plans_billing') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.verify.documents.index') ? true : false" route="company.verify.documents.index" icon="ph-gear"
                        path="company.verify.documents.index" plus_icon="">
                        {{ __('verify_account') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.candidate-status') ? true : false" route="company.candidate-status" icon="fas fa-user"
                        path="company.candidate-status" plus_icon="">
                        {{ __('Status of Workers') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.setting') ? true : false" route="company.setting" icon="ph-gear"
                        path="company.setting" plus_icon="">
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
                        <a href="{{ route('website.employe.details', auth()->user()->username) }}"
                            class="{{ linkActive('company.verify.documents.index') }}">
                            <span class="button-content-wrapper tw-items-center">
                                <span class="button-icon align-icon-left tw-flex tw-items-center">
                                    <i class="ph-user-circle"></i>
                                </span>
                                <span class="button-text">
                                    {{ __('my_profile') }}
                                </span>
                            </span>
                        </a>
                    </li>

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
