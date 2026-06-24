@extends('backend.layouts.app')
@section('title')
    {{ __('Contract Form') }}
@endsection

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        {{ __('Account Details') }}
                    </div>
                    <div class="card-body">
                        <!-- Name Input -->
                        <div class="form-group">
                            <label for="name">{{ __('Employer Name') }}</label>
                            <input type="text" id="name" name="name" class="form-control"
                                value="{{ old('name', $user->name) }}" placeholder="Name" disabled>
                        </div>

                        <!-- Username and Email -->
                        <div class="row">
                            <div class="form-group col-sm-6">
                                <label for="username">{{ __('Username') }}</label>
                                <input type="text" id="username" name="username" class="form-control"
                                    value="{{ old('username', $user->username) }}" placeholder="Username" disabled>
                            </div>
                            <div class="form-group col-sm-6">
                                <label for="email">{{ __('Email') }}</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="{{ old('email', $user->email) }}" placeholder="Email" disabled>
                            </div>
                        </div>

                        <!-- Website and Industry Type -->
                        <div class="row">
                            <div class="form-group col-sm-6">
                                <label for="website">{{ __('Website') }}</label>
                                <input type="text" id="website" name="website" class="form-control"
                                    value="{{ old('website', $user->website) }}" placeholder="Website" disabled>
                            </div>
                            <div class="form-group col-sm-6">
                                <label for="industry_type">{{ __('Industry Type') }}</label>
                                <select id="industry_type" name="industry_type_id" class="form-control" disabled>
                                    <option value="" disabled selected>{{ __('Select One') }}</option>
                                    @foreach ($industry_types as $type)
                                        <option value="{{ $type->id }}"
                                            {{ old('industry_type_id', $user->industry_type_id) == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-sm-6">
                                <label for="username">{{ __('Country') }}</label>
                                <input type="text" id="username" name="username" class="form-control"
                                    value="{{ old('username', $user->country) }}" placeholder="Username" disabled>
                            </div>
                            <div class="form-group col-sm-6">
                                <label for="email">{{ __('Province') }}</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="{{ old('email', $user->region) }}" placeholder="Email" disabled>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-sm-6">
                                <label for="username">{{ __('City') }}</label>
                                <input type="text" id="username" name="username" class="form-control"
                                    value="{{ old('username', $user->district) }}" placeholder="Username" disabled>
                            </div>

                        </div>
                        <!-- Bio -->
                        <div class="form-group">
                            <label for="bio">{{ __('Bio') }}</label>
                            <textarea id="bio" name="bio" rows="5" class="form-control" placeholder="{{ __('Enter your bio') }}"
                                disabled>{{ old('bio', $user->bio) }}</textarea>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Profile Image Card -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        {{ __('Profile Image') }}
                    </div>
                    <div class="card-body">
                        <div class="row justify-content-center">
                            <div class="form-group col-xl-6 text-center">
                                <x-forms.label name="Profile" />
                                <div class="profile-image-wrapper">
                                    <img src="{{ $user->image_url }}" alt="Profile Image" class="img-fluid rounded shadow">
                                </div>
                                <p class="tw-text-gray-500 tw-text-xs tw-text-center mt-2 recommended-img-note mb-0">
                                    Recommended Image Size: 150x150
                                </p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-xl-6">
                                <x-forms.label name="ID Card" />
                                <img src="{{ asset($user->id_card_image) }}" alt="ID Card Image"
                                    class="img-fluid rounded shadow">
                                <p class="tw-text-gray-500 tw-text-xs tw-text-left mt-2 recommended-img-note mb-0">
                                    Recommended Image Size: 68x68
                                </p>
                            </div>
                            <div class="form-group col-xl-6">
                                <x-forms.label name="Passport" />
                                <img src="{{ asset($user->passport_image) }}" alt="Passport Image"
                                    class="img-fluid rounded shadow">
                                <p class="tw-text-gray-500 tw-text-xs tw-text-left mt-2 recommended-img-note mb-0">
                                    Recommended Image Size: 68x68
                                </p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-xl-6">
                                <x-forms.label name="Company Certificate" />
                                <img src="{{ asset($user->comapny_certificate_image) }}" alt="Company Certificate Image"
                                    class="img-fluid rounded shadow">
                                <p class="tw-text-gray-500 tw-text-xs tw-text-left mt-2 recommended-img-note mb-0">
                                    Recommended Image Size: 68x68
                                </p>
                            </div>
                            <div class="form-group col-xl-6">
                                <x-forms.label name="License" />
                                <img src="{{ asset($user->lisence_image) }}" alt="License Image"
                                    class="img-fluid rounded shadow">
                                <p class="tw-text-gray-500 tw-text-xs tw-text-left mt-2 recommended-img-note mb-0">
                                    Recommended Image Size: 68x68
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
    @if ($contractAgreement)
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            {{ __('Account Details') }}
                        </div>
                        <div class="card-body">
                            <form action="{{ route('approved.contract', $user->id) }}" method="POST">
                                @csrf
                                <div class="container my-5">
                                    <div class="card shadow-lg border-0 rounded-lg">
                                        <div class="card-body px-4 py-5">
                                            <div class="contract-content">
                                                <h5 class="fw-bold">Contract Details</h5>
                                                <p>This agreement is made between:</p>
                                                <ul class="list-unstyled">
                                                    <li><strong>Name:</strong> {{ $user->name }}</li>
                                                    <li><strong>Email:</strong> {{ $user->email }}</li>
                                                    <li><strong>Phone/WhatsApp:</strong> {{ $user->whatsapp }}</li>
                                                    @if (auth()->user()->hasRole('recruitment agency'))
                                                        <li><strong>Company:</strong> {{ $user->company }}</li>
                                                    @endif
                                                </ul>
                                                <hr>
                                                <textarea name="contract_content" hidden>{!! $contractAgreement->contract_content !!}</textarea>
                                                <p>{!! $contractAgreement->contract_content !!}</p>
                                                <hr>
                                            </div>

                                            <!-- Signature Section -->
                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <label for="signature" class="form-label fw-bold">Signature <span
                                                            class="text-danger">*</span></label>
                                                    <p> {{ $user->name }}</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="date" class="form-label fw-bold">Date <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="date"
                                                        name="date"
                                                        value="{{ \Carbon\Carbon::now()->toDateString() }}" readonly>
                                                </div>
                                            </div>

                                            <!-- Terms and Conditions -->
                                            <div class="form-check mb-4">
                                                <label class="form-check-label" for="accept-agreement">
                                                    <a href="{{ route('download.agreement') }}"
                                                        class="text-decoration-underline">
                                                        Download Agreement
                                                    </a>.
                                                </label>
                                            </div>
                                            @if ($contractAgreement->is_approved == 1)
                                                <div class="d-grid mb-3">
                                                    <button id="submit-btn" class="btn btn-primary btn-lg" type="submit"
                                                        disabled>Approved</button>
                                                </div>
                                            @else
                                                <div class="d-grid mb-3">
                                                    <button id="submit-btn" class="btn btn-primary btn-lg"
                                                        type="submit">Approved Contract</button>
                                                </div>
                                            @endif

                                            <!-- Submit Button -->
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif



@endsection

@section('script')
    {{-- <script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkbox = document.getElementById('accept-agreement');
        const submitBtn = document.getElementById('submit-btn');

        // Disable the submit button by default
        submitBtn.disabled = true;

        // Add event listener to enable the submit button when the checkbox is checked
        checkbox.addEventListener('change', function () {
            submitBtn.disabled = !checkbox.checked;
        });
    });
</script> --}}
@endsection
@section('css')
    <style>


    </style>
@endsection
