@extends('frontend.layouts.app')

@section('description')
    @php
        $data = metaData('terms-condition');
    @endphp
    {{ $data->description }}
@endsection

@section('og:image')
    {{ asset($data->image) }}
@endsection

@section('title')
    {{ $data->title }}
@endsection

@section('main')

{{-- HERO --}}
<section class="terms-hero">
    <div class="container text-center">
        <h1>{{ __('Terms & Conditions') }}</h1>
        <p>Clear guidelines for using OGS Manpower services and platform</p>
    </div>
</section>

{{-- CONTENT --}}
<section class="terms-section">
    <div class="container">

        <div class="terms-box">

            <div class="terms-header">
                <h3>OGS Manpower</h3>
                <p>Effective Date: {{ date('d M Y') }}</p>
            </div>

            <div class="terms-content">
                {!! $terms_page == null ? $termscondition->terms_page : $terms_page !!}
            </div>

        </div>

    </div>
</section>

{{-- NEWSLETTER --}}
<x-website.subscribe-newsletter />

@endsection


@section('css')
<style>

/* HERO */
.terms-hero {
    background: url('/images/world-map-dark.png');
    background-size: cover;
    background-position: center;
    padding: 90px 0;
    color: #fff;
}

.terms-hero h1 {
    font-size: 44px;
    font-weight: 700;
    color:white;
}

.terms-hero p {
    opacity: 0.8;
}

/* SECTION */
.terms-section {
    background: #0b1220;
    padding: 80px 0;
}

/* BOX */
.terms-box {
    background: #fff;
    padding: 50px;
    border-radius: 12px;
    box-shadow: 0 25px 70px rgba(0,0,0,0.2);
}

/* HEADER */
.terms-header {
    border-bottom: 2px solid #eee;
    margin-bottom: 25px;
    padding-bottom: 15px;
}

.terms-header h3 {
    font-weight: 700;
    color: #0b1220;
}

.terms-header p {
    font-size: 14px;
    color: #777;
}

/* CONTENT */
.terms-content {
    color: #444;
    line-height: 1.8;
}

/* HEADINGS */
.terms-content h1,
.terms-content h2,
.terms-content h3 {
    color: #0b1220;
    margin-top: 20px;
}

/* LIST */
.terms-content ul {
    padding-left: 20px;
}

.terms-content ul li {
    margin-bottom: 10px;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .terms-box {
        padding: 25px;
    }

    .terms-hero h1 {
        font-size: 28px;
    }
}

</style>
@endsection