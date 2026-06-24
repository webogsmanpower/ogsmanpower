<style>
    .profile_section {
        display: flex;
        flex-direction: column;
        align-items: center;
        /* Center items horizontally */
        justify-content: center;
        /* Center items vertically */
        padding: 10px;
    }

    .profile_section p {
        color: #fff;
        margin: 5px 0;
        /* Adjust spacing between lines */
        text-align: center;
        /* Center text inside the container */
        white-space: nowrap;
        /* Prevent line breaks */
        overflow: hidden;
        /* Hide overflow text */
        text-overflow: ellipsis;
        /* Add ellipsis (...) if text overflows */
        max-width: 200px;
        /* Set a maximum width for the text */
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
        margin-bottom: 10px;
        /* Space between avatar and text */
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
            <img src="{{ asset(auth()->user()->image) }}" alt="image" class="profile-image">
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
                    <x-admin.sidebar-list :linkActive="Route::is('agent.dashboard') ? true : false" route="agent.dashboard" parameter=""
                        path="agent.dashboard" plus_icon="" icon="fas fa-tachometer-alt">
                        {{ __('dashboard') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="Route::is('candidate*') ? true : false" route="candidate.index" path="candidate.create"
                        plus_icon="fa fa-plus-circle" icon="fas fa-user">
                        {{ __('candidate') }}
                    </x-admin.sidebar-list>


                    <x-admin.sidebar-list :linkActive="request()->routeIs('agent.setting') ? true : false" route="agent.setting" icon="ph-gear" path="agent.setting"
                        plus_icon="">
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
