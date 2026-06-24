@extends('frontend.auth.layouts.auth')

@section('meta')
    @php
        $data = metaData('register');
    @endphp
@endsection

@section('description')
    {{ $data->description }}
@endsection

@section('title')
    {{ __('register') }}
@endsection

@section('og:image')
    {{ asset($data->image) }}
@endsection

@section('content')
<!----start---->
<div class="login-page d-flex flex-column flex-lg-row min-vh-100">

    {{-- Left Panel: Login Form --}}
     <div class="full-height col-12 order-1 order-lg-0">
        <div class="container">
            <div class="row full-height align-items-center">
                <div class="col-xl-5 col-lg-6 col-md-12">
                    <div class="auth-box2 glass-card">
                        <form id="dynamicForm" action="{{ route('register') }}" method="POST">
                            @csrf
                            <h4 class="rt-mb-20">{{ __('create_account') }}</h4>
                            
                            <span class="d-block body-font-3 text-gray-600 rt-mb-32">
                                {{ __('already_have_account') }}
                                <span>
                                    <a href="{{ route('login') }}">{{ __('log_in') }}</a>
                                </span>
                            </span>

                            <!-- Role Selection -->
                            <div class="tw-bg-[#F1F2F4] tw-rounded-lg tw-mb-6 tw-p-3 text-center">
                                <p class="tw-text-[#767F8C] tw-text-xs tw-font-medium tw-text-center tw-mb-2">
                                    @php
$type = request()->get('type');
@endphp

<p class="text-center fw-bold">
    You are registering as 
    <span class="text-primary"><x-svg.candidate-profile-icon />
        {{ $type == 'seeker' ? 'Seeker' : ($type == 'employer' ? 'Employer/Kafeel' : 'User') }}
    </span>
</p>
                                </p>
                                <div class="flex justify-center gap-4">
                                    <label class="flex items-center gap-1">
                                        <input  name="role" type="radio" value="candidate"{{ request('type') == 'seeker' ? 'checked' : '' }}>
                                        <x-svg.candidate-profile-icon /> {{ __('candidate') }}
                                    </label>
                                    <label class="flex items-center gap-1">
                                        <input  name="role" type="radio" value="company"{{ request('type') == 'employer' ? 'checked' : '' }}>
                                        <x-svg.employer-profile-icon /> {{ __('Employer/Kafeel') }}
                                    </label>
                                    
                                </div>
                            </div>

                            <!-- Name -->
                            <div class="fromGroup rt-mb-15">
                                <input type="text" name="name" id="name" value="{{ old('name') }}"
                                    class="form-control @error('name') is-invalid @enderror"
                                    placeholder="{{ __('full_name') }}">
                                @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            <!-- Email -->
                            <div class="fromGroup rt-mb-15">
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                    class="form-control @error('email') is-invalid @enderror"
                                    placeholder="{{ __('email_address') }}">
                                @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            <!-- WhatsApp -->
                            <div class="fromGroup rt-mb-15">
                                <input type="tel" name="whatsapp" id="whatsappNumber" class="form-control"
                                    placeholder="WhatsApp Number">
                                <small id="validationMessage" style="color:red; display:none;"></small>
                            </div>

                            <!-- HR Dropdown for Agent -->
                            <div id="hrResourceContainer" style="display:none;" class="fromGroup rt-mb-15">
                                <select name="hr_resource" class="form-control">
                                    <option selected>{{ __('select_one') }}</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Password -->
                            <div class="fromGroup rt-mb-15 position-relative">
                                <input type="password" name="password" id="password" class="form-control"
                                    placeholder="{{ __('password') }}">
                                <div onclick="togglePassword('password','eyeIcon')" id="eyeIcon" class="has-badge"
                                    style="position:absolute; top:50%; right:10px; cursor:pointer;">
                                    <i class="ph-eye"></i>
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="fromGroup rt-mb-15 position-relative">
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                    class="form-control" placeholder="{{ __('confirm_password') }}">
                                <div onclick="togglePassword('password_confirmation','eyeIcon2')" id="eyeIcon2"
                                    class="has-badge" style="position:absolute; top:50%; right:10px; cursor:pointer;">
                                    <i class="ph-eye"></i>
                                </div>
                            </div>

                            <!-- Terms -->
                            <div class="d-flex flex-wrap rt-mb-30">
                                <div class="flex-grow-1">
                                    <div class="form-check from-chekbox-custom">
                                        <input type="checkbox" id="term" class="form-check-input">
                                        <label for="term" class="form-check-label">
                                            {{ __('i_have_read_and_agree_with') }}
                                            <a href="{{ url('terms-condition') }}" target="_blank">
                                                {{ __('terms_of_service') }}
                                            </a>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <button id="submitButton" type="submit" class="btn btn-primary d-block" disabled>
                                {{ __('create_account') }}
                            </button>

                        </form>
                    </div>
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
<!---end-->
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
<script>
    // Password toggle
    function togglePassword(fieldId, iconId){
        let field = document.getElementById(fieldId);
        let icon = document.getElementById(iconId).querySelector('i');
        if(field.type === 'password'){ field.type='text'; icon.classList.replace('ph-eye','ph-eye-slash'); }
        else{ field.type='password'; icon.classList.replace('ph-eye-slash','ph-eye'); }
    }

    // Enable submit button
    const inputs = ['name','email','password','password_confirmation'];
    const submitButton = document.getElementById('submitButton');
    const term = document.getElementById('term');
    function checkEnable(){
        let enable = inputs.every(id => document.getElementById(id).value.length>0) && term.checked;
        submitButton.disabled = !enable;
    }
    inputs.forEach(id => document.getElementById(id).addEventListener('keyup',checkEnable));
    term.addEventListener('change',checkEnable);

    // Show HR dropdown if agent selected
    document.querySelectorAll('input[name="role"]').forEach(radio=>{
        radio.addEventListener('change',function(){
            document.getElementById('hrResourceContainer').style.display = this.value==='agent'?'block':'none';
        });
    });

    // WhatsApp intl-tel-input
    const input = document.querySelector("#whatsappNumber");
    const iti = window.intlTelInput(input,{ initialCountry:"auto", separateDialCode:true, geoIpLookup:function(callback){ fetch("https://ipinfo.io/json?token=<YOUR_API_KEY>").then(r=>r.json()).then(d=>callback(d.country)).catch(()=>callback("us")); }, utilsScript:"https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js" });
    input.form.addEventListener('submit',function(e){
        if(!iti.isValidNumber()){ e.preventDefault(); document.getElementById('validationMessage').style.display='block'; document.getElementById('validationMessage').textContent='Please enter valid WhatsApp number'; }
        else{ input.value = iti.getNumber(); }
    });
</script>
@endsection