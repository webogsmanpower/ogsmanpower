@extends('frontend.layouts.app')

@section('title')
    {{ config('app.name') }} | Find Jobs & Talent
@endsection

@section('content')

{{-- ================= HERO SECTION ================= --}}
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">

            <div class="col-lg-6">
                <h1 class="hero-title">
                    Find The Right Talent or Your Dream Job
                </h1>

                <p class="hero-subtitle">
                    Connecting Employers and Skilled Professionals Worldwide.
                </p>

                <form action="{{ route('website.jobs') }}" method="GET" class="hero-search-box">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <input type="text" name="keyword" class="form-control form-control-lg"
                                   placeholder="Job title or keyword">
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="location" class="form-control form-control-lg"
                                   placeholder="Location">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary btn-lg w-100">
                                Search
                            </button>
                        </div>
                    </div>
                </form>

                <div class="hero-stats mt-4">
                    <div class="stat-item">
                        <h4>{{ $live_jobs ?? '2,500+' }}</h4>
                        <span>Live Jobs</span>
                    </div>
                    <div class="stat-item">
                        <h4>{{ $companies ?? '1,200+' }}</h4>
                        <span>Companies</span>
                    </div>
                    <div class="stat-item">
                        <h4>{{ $candidates ?? '8,000+' }}</h4>
                        <span>Candidates</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 text-center">
                <img src="{{ asset('frontend/images/hero-illustration.png') }}"
                     class="img-fluid hero-image">
            </div>

        </div>
    </div>
</section>

{{-- ================= FEATURED CATEGORIES ================= --}}
<section class="section-padding bg-light">
    <div class="container">
        <div class="section-header text-center">
            <h2>Explore By Category</h2>
            <p>Find opportunities across industries</p>
        </div>

        <div class="row g-4 mt-4">
            @foreach($categories ?? [] as $category)
                <div class="col-lg-3 col-md-4 col-6">
                    <a href="{{ route('website.jobs', ['category' => $category->slug]) }}"
                       class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h6>{{ $category->name }}</h6>
                        <span>{{ $category->jobs_count ?? 0 }} Jobs</span>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================= FEATURED JOBS ================= --}}
<section class="section-padding">
    <div class="container">
        <div class="section-header d-flex justify-content-between align-items-center">
            <div>
                <h2>Featured Jobs</h2>
                <p>Hand-picked opportunities for you</p>
            </div>
            <a href="{{ route('website.jobs') }}" class="btn btn-outline-primary">
                View All
            </a>
        </div>

        <div class="row g-4 mt-4">
            @foreach($featured_jobs ?? [] as $job)
                <div class="col-lg-4">
                    <div class="job-card">
                        <div class="job-header">
                            <h5>{{ $job->title }}</h5>
                            <span class="badge bg-light text-dark">
                                {{ $job->job_type ?? 'Full Time' }}
                            </span>
                        </div>
                        <p class="job-company">
                            {{ $job->company->name ?? '' }}
                        </p>
                        <div class="job-meta">
                            <span><i class="fas fa-map-marker-alt"></i> {{ $job->location }}</span>
                            <span><i class="fas fa-money-bill-wave"></i> {{ $job->salary ?? 'Negotiable' }}</span>
                        </div>
                        <a href="{{ route('website.job.details', $job->slug) }}"
                           class="btn btn-sm btn-primary w-100 mt-3">
                            Apply Now
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================= HOW IT WORKS ================= --}}
<section class="section-padding bg-light">
    <div class="container text-center">
        <div class="section-header">
            <h2>How It Works</h2>
            <p>Simple steps to get started</p>
        </div>

        <div class="row mt-5">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">01</div>
                    <h5>Create Account</h5>
                    <p>Sign up and build your professional profile.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">02</div>
                    <h5>Apply or Post Job</h5>
                    <p>Search jobs or post openings easily.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">03</div>
                    <h5>Get Hired</h5>
                    <p>Connect with employers and grow your career.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ================= CALL TO ACTION ================= --}}
<section class="cta-section text-center">
    <div class="container">
        <h3>Are You Hiring?</h3>
        <p>Post your job and connect with qualified professionals today.</p>
        <a href="{{ route('website.job.create') }}"
           class="btn btn-light btn-lg">
            Post a Job
        </a>
    </div>
</section>

@endsection
