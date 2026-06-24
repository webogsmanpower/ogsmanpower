@extends('backend.layouts.app')
@section('title')
    Assign OTP methods to the role
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title line-height-36">Assign OTP methods to the role</h3>
                        <a href="{{ route('roles.otp-methods.index') }}" class="btn bg-primary float-right d-flex align-items-center justify-content-center">
                            <i class="fas fa-arrow-left mr-1"></i>
                            {{ __('back') }}
                        </a>
                    </div>
                    <div class="row">
                        <div class="col-md-6 offset-md-3">
                            <form role="form" action="{{ route('roles.otp-methods.update', $role->id) }}" method="POST">
                                @method('PUT')
                                @csrf
                                <div class="card-body">
                                    <div class="form-group">
                                        <x-forms.label name="Role name" for="role_name" />
                                        <input value="{{ $role->name }}" name="name" type="text"
                                            class="form-control" id="role_name"
                                            placeholder="{{ __('name') }}" readonly>
                                    </div>
                                    <div class="form-group">
                                        <x-forms.label name="OTP methods" />
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input
                                                id="otp_method_all"
                                                type="checkbox"
                                                class="custom-control-input"
                                                onclick="toggleAllOtpMethods(this)"
                                                {{ $role->otpMethods->count() === \App\Models\OtpMethod::active()->count() ? 'checked' : '' }}
                                            >
                                            <label for="otp_method_all" class="custom-control-label">
                                                {{ __('all') }}
                                            </label>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col">
                                                @foreach (\App\Models\OtpMethod::active()->get() as $otpMethod)
                                                    <div class="custom-control custom-checkbox mb-2">
                                                        <input
                                                            name="otp_methods[]"
                                                            class="custom-control-input otp-method-checkbox"
                                                            type="checkbox"
                                                            id="otp_method_checkbox_{{ $otpMethod->id }}"
                                                            value="{{ $otpMethod->id }}"
                                                            {{ $role->otpMethods->contains($otpMethod->id) ? 'checked' : '' }}
                                                        >
                                                        <label for="otp_method_checkbox_{{ $otpMethod->id }}"
                                                            class="custom-control-label">
                                                            {{ $otpMethod->display_name ?? ucfirst($otpMethod->name) }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fa fa-sync mr-1"></i> {{ __('save') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function toggleAllOtpMethods(source) {
            const checkboxes = document.querySelectorAll('.otp-method-checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }
        // Optionally, update "all" checkbox if any individual is unchecked
        document.querySelectorAll('.otp-method-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                const all = document.getElementById('otp_method_all');
                const allChecked = Array.from(document.querySelectorAll('.otp-method-checkbox')).every(c => c.checked);
                all.checked = allChecked;
            });
        });
    </script>
@endsection