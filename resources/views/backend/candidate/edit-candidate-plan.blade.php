@extends('backend.settings.setting-layout')

@section('title')
    {{ __('Plan') }}
@endsection
@section('content')
    <div class="container mt-5">
        <div class="card mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <h5 class="text-center">Add a Subscription Plan</h5>
                <p class="text-center">Enter details to create a new subscription plan.</p>
                <form action="{{ route('storeOrUpdatePlan') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="planName" class="form-label">Plan Name</label>
                        <input type="text" class="form-control" id="planName" name="planName"
                            value="{{ $plan->name }}" placeholder="Enter plan name" required>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price (USD)</label>
                        <input type="number" class="form-control" id="price" value="{{ $plan->price }}" name="price"
                            placeholder="Enter price" required>
                    </div>
                    <div class="mb-3">
                        <label for="duration" class="form-label">Duration (Days)</label>
                        <input type="number" class="form-control" id="duration" value="{{ $plan->duration }}"
                            name="duration" placeholder="Enter duration in days" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Plan</button>
            </div>
        </div>
    </div>
@endsection
