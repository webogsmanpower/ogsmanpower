@extends('frontend.layouts.app')

@section('title')
    {{ __('Privacy Policy') }}
@endsection

@section('main')

{{-- HERO --}}
<section class="policy-hero">
    <div class="container text-center">
        <h1>Privacy Policy</h1>
        <p>Protecting your data and ensuring transparency in our International recruitment services</p>
    </div>
</section>

{{-- CONTENT --}}
<section class="policy-section">
    <div class="container">

        <div class="policy-box">

            <div class="policy-header">
                <h3>OGS Manpower</h3>
                <p>Effective Date: {{ date('d M Y') }}</p>
            </div>

            <div class="policy-content">
                {!! $privacy_page == null ? $privacy_page_default->privary_page : $privacy_page !!}
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
.policy-hero {
    background: url('/images/world-map-dark.png');
    background-size: cover;
    background-position: center;
    padding: 90px 0;
    color: #fff;
}

.policy-hero h1 {
    font-size: 44px;
    font-weight: 700;
    color:white;
}

.policy-hero p {
    opacity: 0.8;
}

/* SECTION */
.policy-section {
    background: #0b1220;
    padding: 80px 0;
}

/* BOX */
.policy-box {
    background: #fff;
    padding: 50px;
    border-radius: 12px;
    box-shadow: 0 25px 70px rgba(0,0,0,0.2);
}

/* HEADER */
.policy-header {
    border-bottom: 2px solid #f1f1f1;
    margin-bottom: 25px;
    padding-bottom: 15px;
}

.policy-header h3 {
    font-weight: 700;
    color: #0b1220;
}

.policy-header p {
    font-size: 14px;
    color: #777;
}

/* CONTENT */
.policy-content {
    color: #444;
    line-height: 1.8;
}

/* HEADINGS */
.policy-content h1,
.policy-content h2,
.policy-content h3 {
    color: #0b1220;
    margin-top: 20px;
}

/* LIST */
.policy-content ul {
    padding-left: 20px;
}

.policy-content ul li {
    margin-bottom: 10px;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .policy-box {
        padding: 25px;
    }

    .policy-hero h1 {
        font-size: 28px;
    }
}

</style>
@endsection