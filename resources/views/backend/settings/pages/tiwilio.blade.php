@extends('backend.settings.setting-layout')


@section('title')
{{"Twilio Settings"}}
@endsection

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Twilio Configuration') }}</div>

                    <div class="card-body">
                        <!-- Display success message if any -->
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <!-- Form for Twilio Configuration -->
                        <form action="{{ route('settings.tiwilio.update') }}" method="POST">
                            @csrf

                            <!-- Twilio SID -->
                            <div class="form-group mb-3">
                                <label for="twilio_sid">{{ __('Twilio SID') }}</label>
                                <input type="text" class="form-control @error('twilio_sid') is-invalid @enderror"
                                       id="twilio_sid" name="twilio_sid" value="{{ old('twilio_sid') }}" required>
                                @error('twilio_sid')
                                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Twilio Auth Token -->
                            <div class="form-group mb-3">
                                <label for="twilio_auth_token">{{ __('Twilio Auth Token') }}</label>
                                <input type="text" class="form-control @error('twilio_auth_token') is-invalid @enderror"
                                       id="twilio_auth_token" name="twilio_auth_token" value="{{ old('twilio_auth_token') }}" required>
                                @error('twilio_auth_token')
                                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Twilio WhatsApp Number -->
                            <div class="form-group mb-3">
                                <label for="twilio_whatsapp_number">{{ __('Twilio WhatsApp Number') }}</label>
                                <input type="text" class="form-control @error('twilio_whatsapp_number') is-invalid @enderror"
                                       id="twilio_whatsapp_number" name="twilio_whatsapp_number" value="{{ old('twilio_whatsapp_number') }}" required>
                                @error('twilio_whatsapp_number')
                                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Submit Button -->
                            <div class="form-group mb-0 text-center">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Save Configuration') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
