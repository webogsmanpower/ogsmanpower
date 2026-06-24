@extends('frontend.auth.layouts.auth')


@section('title', __('login'))

@section('content')
<div class="login-page d-flex flex-column flex-lg-row min-vh-100">

    {{-- Left Panel: Login Form --}}
    <div class="login-left flex-fill d-flex align-items-center justify-content-center bg-light p-4">
        <div class="auth-card glass-card p-5 rounded-3 shadow-lg w-100" style="max-width: 480px;">
            
            
            <h3 class="fw-bold mb-3 text-center">{{ __('log_in') }}</h3>
            <p class="text-center text-muted mb-4">
                {{ __('dont_have_account') }}
                <a href="{{ route('register') }}" class="text-primary">{{ __('create_account') }}</a>
            </p>

            <form id="dynamicForm" method="POST" action="{{ route('login') }}">
                @csrf

                

                {{-- Email --}}
                <div class="mb-3">
                    <input type="email" name="email" id="email" class="form-control form-control-lg" placeholder="{{ __('email_address') }}" value="{{ old('email') }}">
                    @error('email')<span class="text-danger small">{{ __($message) }}</span>@enderror
                </div>

                {{-- Password --}}
                <div class="mb-3 position-relative">
                    <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="{{ __('password') }}">
                    <button type="button" class="btn btn-sm toggle-password position-absolute top-50 end-0 translate-middle-y me-2" onclick="passToText('password','eyeIcon')">
                        <i id="eyeIcon" class="ph-eye"></i>
                    </button>
                    @error('password')<span class="text-danger small">{{ __($message) }}</span>@enderror
                </div>

                {{-- Remember & Forgot Password --}}
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label for="remember" class="form-check-label">{{ __('keep_me_logged') }}</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="text-primary">{{ __('forget_password') }}</a>
                </div>

                {{-- Submit Button --}}
                <button type="submit" id="submitButton" class="btn btn-primary w-100 py-2 fw-bold" disabled>
                    {{ __('log_in') }}
                </button>

            </form>

            {{-- Social Login --}}
            <div class="social-login mt-4 text-center">
                <p class="text-muted mb-2">{{ __('or') }}</p>
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    {{-- Social Buttons Placeholder --}}
                    <a href="#" class="btn btn-outline-dark d-flex align-items-center gap-2 px-3 py-1 mb-2">
                        <svg width="20" height="20"><circle cx="10" cy="10" r="9" stroke="black" stroke-width="2" fill="none"/></svg>
                        Google
                    </a>
                    <a href="#" class="btn btn-outline-dark d-flex align-items-center gap-2 px-3 py-1 mb-2">
                        <svg width="20" height="20"><rect x="2" y="2" width="16" height="16" stroke="black" stroke-width="2" fill="none"/></svg>
                        Facebook
                    </a>
                    <a href="#" class="btn btn-outline-dark d-flex align-items-center gap-2 px-3 py-1 mb-2">
                        <svg width="20" height="20"><polygon points="10,2 18,18 2,18" stroke="black" stroke-width="2" fill="none"/></svg>
                        Twitter
                    </a>
                    <a href="#" class="btn btn-outline-dark d-flex align-items-center gap-2 px-3 py-1 mb-2">
                        <svg width="20" height="20"><polygon points="10,2 18,18 2,18" stroke="black" stroke-width="2" fill="none"/></svg>
                        LinkedIn
                    </a>
                    <a href="#" class="btn btn-outline-dark d-flex align-items-center gap-2 px-3 py-1 mb-2">
                        <svg width="20" height="20"><circle cx="10" cy="10" r="9" stroke="black" stroke-width="2" fill="none"/></svg>
                        GitHub
                    </a>
                </div>
            </div>

        </div>
    </div>

    {{-- Right Panel: Illustration & Stats --}}
    <div class="login-right flex-fill d-none d-lg-flex align-items-center justify-content-center position-relative">
        <div class="sidebar-bg position-absolute w-100 h-100" style="background-image: url('../images/hrms-illustration.png'); background-size: cover; background-position: center;"></div>
        <div class="stats-overlay text-center text-white z-2 p-4">
            <h4 class="fw-bold">120 {{ __('open_jobs_waiting_for_you') }}</h4>
            <div class="d-flex gap-3 justify-content-center mt-4 flex-wrap">
                <div class="stat-card p-3 rounded bg-dark bg-opacity-50">
                    <svg width="30" height="30"><circle cx="15" cy="15" r="13" stroke="white" stroke-width="2" fill="none"/></svg>
                    <div class="h4 fw-bold">35</div>
                    <small>{{ __('live_job') }}</small>
                </div>
                <div class="stat-card p-3 rounded bg-dark bg-opacity-50">
                    <svg width="30" height="30"><rect x="5" y="5" width="20" height="20" stroke="white" stroke-width="2" fill="none"/></svg>
                    <div class="h4 fw-bold">50</div>
                    <small>{{ __('companies') }}</small>
                </div>
                <div class="stat-card p-3 rounded bg-dark bg-opacity-50">
                    <svg width="30" height="30"><polygon points="15,5 25,25 5,25" stroke="white" stroke-width="2" fill="none"/></svg>
                    <div class="h4 fw-bold">200</div>
                    <small>{{ __('candidates') }}</small>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('style')
<style>
    .login-page { min-height: 100vh; }
    .glass-card {
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(12px);
        border-radius: 20px;
        transition: all 0.3s;
    }
    .glass-card:hover { transform: translateY(-5px); }

    .role-option { cursor: pointer; border: 1px solid #ddd; transition: 0.3s; padding: 0.5rem; }
    .role-option:hover { border-color: #007bff; }
    .role-option input { display: none; }

    .stat-card { width: 120px; text-align: center; transition: transform 0.3s; }
    .stat-card:hover { transform: translateY(-5px); }

    .toggle-password { background: transparent; border: none; cursor: pointer; }

    .social-login .btn { transition: transform 0.2s; }
    .social-login .btn:hover { transform: translateY(-2px); }
</style>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const submitBtn = document.getElementById('submitButton');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const form = document.getElementById('dynamicForm');

    function toggleButton() {
        submitBtn.disabled = !(emailInput.value.trim() && passwordInput.value.trim());
    }

    emailInput.addEventListener('input', toggleButton);
    passwordInput.addEventListener('input', toggleButton);
    toggleButton();

    // Password toggle
    const eyeIcon = document.getElementById('eyeIcon');
    window.passToText = function(id, icon) {
        const input = document.getElementById(id);
        const eye = document.getElementById(icon);
        if(input.type === 'password'){
            input.type = 'text';
            eye.className = 'ph-eye-slash';
        } else {
            input.type = 'password';
            eye.className = 'ph-eye';
        }
    };

    // Role-based login action
    document.querySelectorAll('input[name="role"]').forEach(radio => {
        radio.addEventListener('change', () => {
            form.action = radio.value === 'agent' ? "{{ route('admin.login') }}" : "{{ route('login') }}";
        });
    });
});
</script>
@endsection