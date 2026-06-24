<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ __('sign_in') }} | {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests" /> --}}
    <link rel="stylesheet" href="{{ asset('backend') }}/plugins/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $setting->favicon_image_url }}">
    @vite('resources/backend/app.css')

    <!-- For PWA Theme Color as it is Frontend Start  -->
    @php
        $sessionPrimaryColor = session('primaryColor');
        $primaryColor = $sessionPrimaryColor ? $sessionPrimaryColor : $setting->frontend_primary_color;
    @endphp
    <!-- For PWA Theme Color as it is Frontend End  -->

    <!-- PWA Meta Theme color and link Start  -->
    @if ($setting->pwa_enable)
        <meta name="theme-color" content="{{ $primaryColor }}" />
        <link rel="apple-touch-icon" href="{{ $setting->favicon_image_url }}">
        <link rel="manifest" href="{{ asset('/manifest.json') }}">
    @endif
    <!-- PWA Meta Theme color and link End -->

    <style>
        :root {
            /* For PWA Theme Color as it is Frontend  */
            --primary-500: {{ $primaryColor }} !important;
        }
    </style>

    <style>
        .system-logo {
            max-width: 200px !important;
        }

        @media (min-width: 768px) {
            .login-card-body {
                width: 380px !important;
                max-width: 380px !important;
            }
        }

        .login-card-body .input-group input.form-control,
        .login-card-body button.btn {
            padding: 12px 20px;
            height: unset !important;
        }

        .quote {
            max-width: 380px;
            margin: 0 auto;
        }

        .background-view {
            background-image: url('https://source.unsplash.com/random/1920x1280/?nature,landscape,mountains'), url('/backend/image/river.jpeg');
            background-size: cover;
        }

        .admin_panel_h1 {
            color: #81a54e;
            align-items: center;
            justify-content: center;
            display: flex;
        }

        .btn-block {
            background-color: #007bff;
            /* Blue color */
            color: white;
            /* Text color */
            border-color: #007bff;
        }

        .navbar {
            background-color: #4470af;
            height: 4rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            /* Adjust the font size as needed */
        }


        @media (max-width: 2561px),
        (max-height: 1588px) {

            .pb-5,
            .py-5 {
            }

            .pt-5,
            .py-5 {
                padding-top: 25rem !important;
            }
        }
        @media (max-width: 2044px),
        (max-height: 1155px) {

            .pb-5,
            .py-5 {
            }

            .pt-5,
            .py-5 {
                padding-top: 5rem !important;
            }
        }
        @media (max-width: 1025px),
        (max-height: 635px) {

            .pb-5,
            .py-5 {
            }

            .pt-5,
            .py-5 {
                padding-top: 5rem !important;
            }
        }
    </style>

    {{-- ==================================================== --}}
    {{-- ================ DO NOT REMOVE THIS ================ --}}
    {{-- ==================================================== --}}


    {{-- ==================================================== --}}
    {{-- ================ DO NOT REMOVE THIS ================ --}}
    {{-- ==================================================== --}}

    @yield('backend_auth_link')
</head>

<body>
    <nav class="navbar">
        OGS MANPOWER
    </nav>


    {{-- <div class="container-fluid"> --}}

    <div class="row justify-content-center">

        <div class="col-lg-4 col-md-5">

            <div class="d-flex flex-column justify-content-start align-items-center py-5 px-4 ">
                <a href="{{ route('admin.login') }}" class="d-block">
                    <div class="system-logo d-flex justify-content-center">
                        {{-- <img src="{{ $setting->dark_logo_url }}" alt="{{ __('logo') }}" class="img-fluid"> --}}
                        <img src="{{ asset('images/Admin_logo.jpeg') }}" alt="{{ __('logo') }}" class="img-fluid">

                    </div>
                </a>
                <div class="login-card-body p-0">
                    @yield('content')
                </div>
                {{-- <div class="text-center text-secondary quote">
                        {{ inspireMe() }}
                    </div> --}}
            </div>
        </div>
        {{-- <div class="col-lg-8 col-md-7 col d-lg-block d-none">
                <div class="h-100 min-vh-100 background-view">
                </div>
            </div> --}}
    </div>
    <p style="text-align: center;">Copyright @2024 All Rights Reserved</p>

    {{-- </div> --}}

    <!-- PWA Button Start -->
    {{-- <button class="pwa-install-btn bg-white position-fixed d-none" id="installApp">
        <img src="{{ asset('pwa-btn.png') }}" alt="Install App">
    </button> --}}
    <!-- PWA Button End -->

    <script src="{{ asset('backend/plugins/jquery/jquery.min.js') }}"></script>

    @yield('backend_auth_script')

    <!-- PWA Script Start -->
    @if ($setting->pwa_enable)
        <script src="{{ asset('/sw.js') }}"></script>
        <script>
            if (!navigator.serviceWorker) {
                navigator.serviceWorker.register("/sw.js").then(function(reg) {
                    console.log("Service worker has been registered for scope: " + reg);
                });
            }

            let deferredPrompt;
            window.addEventListener('beforeinstallprompt', (e) => {
                $('#installApp').removeClass('d-none');
                deferredPrompt = e;
            });

            const installApp = document.getElementById('installApp');
            installApp.addEventListener('click', async () => {
                if (deferredPrompt !== null) {
                    deferredPrompt.prompt();
                    const {
                        outcome
                    } = await deferredPrompt.userChoice;
                    if (outcome === 'accepted') {
                        deferredPrompt = null;
                    }
                }
            });
        </script>
    @endif
    <!-- PWA Script End -->

</body>

</html>
