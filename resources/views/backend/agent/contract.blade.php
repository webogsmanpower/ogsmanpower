@extends('backend.layouts.app')
@section('title')
    {{ __('Contract Form') }}
@endsection

@section('content')
    @if ($errors->has('contract'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('contract') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('save.agreement') }}" method="POST">
        @csrf
        <div class="container my-5">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="text-center my-4">
                    <img src="{{ $setting->dark_logo_url }}" alt="Company Logo" style="width: 150px;">
                </div>
                <div class="card-body px-4 py-5">
                    <div class="contract-content">
                        <h5 class="fw-bold">Contract Details</h5>
                        <p>This agreement is made between:</p>
                        <ul class="list-unstyled">
                            <li><strong>Name:</strong> {{ auth()->user()->name }}</li>
                            <li><strong>Email:</strong> {{ auth()->user()->email }}</li>
                            <li><strong>Phone/WhatsApp:</strong> {{ auth()->user()->whatsapp }}</li>
                            @if (auth()->user()->hasRole('recruitment agency'))
                                <li><strong>Company:</strong> {{ auth()->user()->company }}</li>
                            @endif
                        </ul>
                        <hr>
                        <textarea name="contract_content" hidden>{!! $contract->content !!}</textarea>
                        <p>{!! $contract->content !!}</p>
                        <hr>
                    </div>

                    <!-- Signature Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="signature" class="form-label fw-bold">Signature <span
                                    class="text-danger">*</span></label>
                            <p> {{ auth()->user()->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <label for="date" class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="date" name="date"
                                value="{{ \Carbon\Carbon::now()->toDateString() }}" readonly>
                        </div>
                    </div>
                    @if (optional(auth()->user()->contractAgreement)->is_contract_submitted)
                        <div class="form-check mb-4">

                            <label class="form-check-label" for="accept-agreement">
                                <a href="{{ route('download.agreement') }}" class="text-decoration-underline">
                                    Download Agreement
                                </a>.
                            </label>
                        </div>
                    @else
                        <!-- Terms and Conditions -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="accept-agreement" name="accept_agreement"
                                required>
                            <label class="form-check-label" for="accept-agreement">
                                I agree to the terms and conditions/<a href="{{ route('download.agreement') }}"
                                    class="text-decoration-underline">
                                    Download Agreement
                                </a>.
                            </label>
                        </div>

                        <!-- Submit Button -->

                        <div class="d-grid mb-3">
                            <button id="submit-btn" class="btn btn-primary btn-lg" type="submit">Submit</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkbox = document.getElementById('accept-agreement');
            const submitBtn = document.getElementById('submit-btn');

            // Disable the submit button by default
            submitBtn.disabled = true;

            // Add event listener to enable the submit button when the checkbox is checked
            checkbox.addEventListener('change', function() {
                submitBtn.disabled = !checkbox.checked;
            });
        });
    </script>
@endsection
