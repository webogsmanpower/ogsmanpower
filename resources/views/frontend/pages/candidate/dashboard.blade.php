@extends('components.website.candidate.layout.app')

@section('title')
{{ __('dashboard') }}
@endsection

@section('main')

@php
$percentage = $candidate->profile_complete ?? 0;

$radius = 40;
$circumference = 2 * pi() * $radius;

if ($percentage < 40) {
    $color = '#dc3545';
} elseif ($percentage < 70) {
    $color = '#ffc107';
} else {
    $color = '#28a745';
}
@endphp


<div class="py-4">
<div class="container">
<div class="row">

<div class="col-lg-9">

{{-- Welcome --}}
<div class="card shadow-sm border-0 mb-4">
<div class="card-body">
<h5 class="mb-1">
{{ __('hello') }}, {{ auth()->user()->name }}
</h5>

<p class="text-muted mb-0">
{{ __('here_is_your_daily_activities_career_opportunities') }}
</p>
</div>
</div>


{{-- Stats --}}
<div class="row g-3 mb-4">

<div class="col-md-4">
<div class="card border-0 shadow-sm h-100">
<div class="card-body d-flex justify-content-between align-items-center">

<div>
<h4 class="fw-bold mb-0">{{ $appliedJobs }}</h4>
<small class="text-muted">{{ __('job_applied') }}</small>
</div>

<i class="ph-suitcase-simple fs-2 text-primary"></i>

</div>
</div>
</div>


<div class="col-md-4">
<div class="card border-0 shadow-sm h-100">
<div class="card-body d-flex justify-content-between align-items-center">

<div>
<h4 class="fw-bold mb-0">{{ $favoriteJobs }}</h4>
<small class="text-muted">{{ __('favorite_jobs') }}</small>
</div>

<i class="ph-bookmark-simple fs-2 text-warning"></i>

</div>
</div>
</div>


<div class="col-md-4">
<div class="card border-0 shadow-sm h-100">
<div class="card-body d-flex justify-content-between align-items-center">

<div>
<h4 class="fw-bold mb-0">{{ $notifications }}</h4>
<small class="text-muted">{{ __('job_alert') }}</small>
</div>

<i class="ph-bell-ringing fs-2 text-danger"></i>

</div>
</div>
</div>

</div>


{{-- Profile Completion --}}
@if($percentage > 0)

<div class="card border-0 shadow-sm mb-4">
<div class="card-body d-flex justify-content-between align-items-center flex-wrap">


<div class="d-flex align-items-center gap-3">

<img
src="{{ $candidate->photo ? asset($candidate->photo) : asset('images/default-user.png') }}"
class="rounded-circle"
width="70"
height="70"
>

<div>
<h6 class="mb-1">{{ __('profile_completion') }}</h6>

<p class="text-muted mb-0">
{{ __('complete_your_profile_to_get_better_jobs') }}
</p>
</div>

</div>



<div class="text-center mt-3 mt-md-0">

<div class="position-relative" style="width:100px;height:100px">

<svg width="100" height="100">

<circle
cx="50"
cy="50"
r="{{ $radius }}"
stroke="#e9ecef"
stroke-width="8"
fill="none"
/>

<circle
id="progressBar"
cx="50"
cy="50"
r="{{ $radius }}"
stroke="{{ $color }}"
stroke-width="8"
fill="none"
stroke-linecap="round"
stroke-dasharray="{{ $circumference }}"
stroke-dashoffset="{{ $circumference }}"
transform="rotate(-90 50 50)"
/>

</svg>

<div
id="progressText"
class="position-absolute top-50 start-50 translate-middle fw-bold"
>
0%
</div>

</div>


<a href="{{ route('candidate.setting') }}"
class="btn btn-outline-primary btn-sm mt-3">
{{ __('edit_profile') }}
</a>

</div>


</div>
</div>

@endif



{{-- Recently Applied Jobs --}}
<div class="card border-0 shadow-sm">
<div class="card-body">

<div class="d-flex justify-content-between mb-3">

<h6 class="mb-0">
{{ __('recently_applied') }}
</h6>

<a
href="{{ route('candidate.appliedjob') }}"
class="text-primary small"
>
{{ __('view_all') }}
</a>

</div>


<div class="table-responsive">

<table class="table align-middle">

<thead class="table-light">
<tr>
<th>{{ __('job') }}</th>
<th>{{ __('date_applied') }}</th>
<th>{{ __('status') }}</th>
<th>{{ __('action') }}</th>
</tr>
</thead>


<tbody>

@forelse ($jobs as $job)

<tr>

<td>

<div class="d-flex align-items-center gap-3">

<img
src="{{ asset($job->company->logo_url) }}"
width="50"
class="rounded"
>

<div>

<a
href="{{ route('website.job.details',$job->slug) }}"
class="fw-semibold text-dark"
>
{{ Str::limit($job->title,35) }}
</a>

<div class="small text-muted">
<i class="ph-map-pin"></i>
{{ $job->country }}
</div>

</div>

</div>

</td>


<td>
{{ date('M d, Y',strtotime($job->pivot->created_at)) }}
</td>


<td>

@if($job->deadline_active)

<span class="badge bg-success">
{{ __('active') }}
</span>

@else

<span class="badge bg-danger">
{{ __('expired') }}
</span>

@endif

</td>


<td>

<a
href="{{ route('website.job.details',$job->slug) }}"
class="btn btn-sm btn-outline-primary"
>
{{ __('view_details') }}
</a>

</td>

</tr>

@empty

<tr>

<td colspan="4" class="text-center py-4">

<a
href="{{ route('website.job') }}"
class="btn btn-primary btn-sm"
>
{{ __('browse_job') }}
</a>

</td>

</tr>

@endforelse

</tbody>

</table>

</div>

</div>
</div>


</div>
</div>
</div>
</div>



{{-- Progress Animation --}}
@if($percentage > 0)

<script>

document.addEventListener("DOMContentLoaded",function(){

let current = 0;
let target = {{ $percentage }};
let circumference = {{ $circumference }};

let progressBar = document.getElementById("progressBar");
let progressText = document.getElementById("progressText");

if(!progressBar) return;

let interval = setInterval(function(){

if(current >= target){
clearInterval(interval);
return;
}

current++;

let offset = circumference - (circumference * current / 100);

progressBar.style.strokeDashoffset = offset;
progressText.innerText = current + "%";

},15);

});

</script>

@endif

@endsection