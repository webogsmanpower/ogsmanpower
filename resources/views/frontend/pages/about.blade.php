@extends('frontend.layouts.app')

@section('title', 'About OGS Manpower')

@section('content')
@section('main')

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>

/* ===== GLOBAL ===== */
body { font-family: 'Segoe UI', sans-serif; }

/* ===== HERO ===== */
.hero {
    background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)),
                url('../icons/banner.png');
    background-size: cover;
    padding: 140px 0;
    color: #fff;
}

.hero h1 {
    font-size: 48px;
    font-weight: 700;
}

.hero p {
    font-size: 18px;
}

.btn-custom {
    padding: 12px 25px;
    border-radius: 30px;
    font-weight: 600;
}

/* ===== SECTION TITLE ===== */
.section-title {
    text-align: center;
    font-weight: 700;
    margin-bottom: 50px;
}

/* ===== CARDS ===== */
.card-modern {
    background: #fff;
    border-radius: 15px;
    padding: 20px;
    transition: 0.3s;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}
.card-modern:hover {
    transform: translateY(-5px);
}

/* ===== VIDEO ===== */
.video-section {
    background: #0b2a4a;
    padding: 80px 0;
    color: #fff;
}

.video-box {
    position: relative;
    overflow: hidden;
    border-radius: 15px;
}
.video-box img {
    width: 100%;
}
.play-btn {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    background: red;
    color: white;
    border-radius: 50%;
    padding: 12px 18px;
}

/* ===== CTA ===== */
.cta {
    background: linear-gradient(90deg,#7a1f1f,#0b2a4a);
    color: #fff;
    padding: 80px 0;
}

/* ===== COUNTER ===== */
.counter {
    font-size: 30px;
    font-weight: bold;
}
/*====icons====*/
.industries-section {
    background: #f8f9fa;
}

.industry-card {
    background: #fff;
    padding: 20px 10px;
    border-radius: 12px;
    text-align: center;
    transition: 0.3s;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.industry-card img {
    width: 45px;
    height: 45px;
    object-fit: contain;
    margin-bottom: 10px;
}

.industry-card h6 {
    font-size: 13px;
    font-weight: 600;
}

.industry-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
/*===whychoose==*/
.why-section {
    background: #f8f9fa;
}

.why-card {
    background: #fff;
    padding: 20px 10px;
    border-radius: 12px;
    text-align: center;
    transition: 0.3s;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    height: 100%;
}

.why-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}

.icon-box {
    width: 60px;
    height: 60px;
    margin: 0 auto 10px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-box img {
    width: 30px;
    height: 30px;
}

.why-card p {
    font-size: 13px;
    font-weight: 600;
    margin: 0;
}
/*======video=====*/
.video-section {
    background: url('/icons/video-bg.png') center/cover no-repeat;
    position: relative;
    padding: 80px 0;
}

/* DARK OVERLAY */
.video-section .overlay {
    background: rgba(11, 42, 74, 0.85); /* dark blue overlay */
    padding: 80px 0;
}

/* VIDEO CARD */
.video-card {
    border-radius: 15px;
    overflow: hidden;
    background: #000;
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    transition: 0.3s;
}

.video-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.6);
}

.video-card iframe {
    border: none;
}
/*=======counter======*/
.counter-section {
    background: #ffffff;
}

.counter-card {
    background: #fff;
    padding: 30px 20px;
    border-radius: 15px;
    transition: 0.3s;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.counter-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.12);
}

.counter-card .icon {
    font-size: 30px;
    color: #0b2a4a;
    margin-bottom: 10px;
}

.counter-card h3 {
    font-size: 28px;
    font-weight: 700;
    margin: 5px 0;
}

.counter-card p {
    font-size: 14px;
    color: #666;
    margin: 0;
}
/*======global=====*/
.global-card {
    background: #fff;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    overflow: hidden;
}

/* LEFT SIDE */
.portal-img {
    width: 100%;
    border-radius: 10px;
}

.portal-box ul {
    list-style: none;
    padding: 0;
    font-size: 14px;
}

.portal-box ul li {
    margin-bottom: 6px;
}

/* MAP AREA */
.map-area {
    position: relative;
}

.map-bg {
    width: 100%;
    border-radius: 15px;
    opacity: 0.95;
}

/* PIN BASE */
.map-pin {
    position: absolute;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* DOT */
.pin-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
}

/* COLORS */
.green { background: #2e7d32; }
.red { background: #c62828; }
.dark { background: #1b5e20; }

/* LABEL */
.pin-label {
    background: #fff;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    font-weight: 600;
}

/* POSITIONS (ADJUST IF NEEDED) */
.uae {
    top: 45%;
    left: 60%;
}

.pakistan {
    top: 43%;
    left: 65%;
}

.uk {
    top: 35%;
    left: 50%;
}
.social-card {
    display: block;
    background: #fff;
    padding: 20px 10px;
    border-radius: 14px;
    text-align: center;
    transition: 0.3s;
    box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    text-decoration: none;
    color: inherit;
}

.icon-box {
    width: 60px;
    height: 60px;
    margin: 0 auto 12px;
    background: #eef3f8;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-box img {
    width: 30px;
    height: 30px;
}

.social-card h6 {
    font-size: 13px;
    font-weight: 600;
}

.social-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.12);
}

.social-card:hover .icon-box {
    background: #0b2a4a;
}

/* ===== WHY SECTION ===== */
.why-section{background:#0A1628;padding:80px 0;color:#fff;}
.section-label{color:#C9A84C;font-size:12px;letter-spacing:2px;text-transform:uppercase;}
.section-title{font-size:36px;margin-bottom:10px;}
.cards-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:40px;}

.feature-card{
    background:rgba(255,255,255,0.05);
    padding:20px;
    text-align:center;
    cursor:pointer;
    border:1px solid rgba(255,255,255,0.1);
    transition:0.3s;
}
.feature-card:hover{transform:translateY(-5px);border-color:#C9A84C;}
.fc-icon{font-size:30px;}
.fc-title{font-weight:bold;margin-top:10px;}
.fc-teaser{font-size:13px;color:#ccc;margin-top:5px;}
.fc-click{font-size:12px;color:#C9A84C;margin-top:8px;}

.modal-overlay{
    position:fixed;
    top:0;left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.85);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:999;
    padding:20px;
}

.modal-overlay.open{
    display:flex;
}

.modal-box{
    background:#122040;
    width:100%;
    max-width:700px;
    max-height:90vh;   /* 🔥 IMPORTANT */
    overflow-y:auto;   /* 🔥 ENABLE SCROLL */
    padding:25px;
    color:#fff;
    position:relative;
    border-radius:6px;
}

.modal-box::-webkit-scrollbar {
    width:6px;
}
.modal-box::-webkit-scrollbar-thumb {
    background:#C9A84C;
    border-radius:10px;
}
.side-btns{position:fixed;right:0;top:50%;transform:translateY(-50%);z-index:300;display:flex;flex-direction:column;gap:3px;}
.side-btn{display:flex;align-items:center;gap:.6rem;padding:.85rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;text-decoration:none;color:var(--white);cursor:pointer;border:none;font-family:'DM Sans',sans-serif;position:relative;}
.side-btn .sb-icon{font-size:1.2rem;flex-shrink:0;}
.side-btn .sb-label{white-space:nowrap;max-width:0;overflow:hidden;transition:max-width .4s,opacity .4s;opacity:0; color:white;}
.side-btn:hover .sb-label{max-width:140px;opacity:1;}
.side-btn.wa{background:#25D366;}
.side-btn.em{background:var(--gold);color:var(--navy);}
.side-btn.reg{background:#E05C2A;}
@media(max-width:600px){
  .cards-grid,.metrics-grid,.ind-grid,.social-grid{grid-template-columns:1fr;}
  section{padding:3.5rem 4%;}
  .side-btns{top:auto;bottom:0;right:0;flex-direction:row;transform:none;width:100%;}
  .side-btn{flex:1;justify-content:center;padding:.85rem .4rem;}
  .side-btn .sb-label{max-width:none;opacity:1;font-size:.62rem;}
}


</style>
<!-- FIXED SIDE BUTTONS -->
<div class="side-btns">
  <a href="https://wa.me/923005352636" target="_blank" class="side-btn wa">
    <span class="sb-icon">💬</span><span class="sb-label">WhatsApp Us</span>
  </a>
  <a href="mailto:ogsceo@gmail.com" class="side-btn em" style="background-color: gold;">
    <span class="sb-icon">✉️</span><span class="sb-label">Email Us</span>
  </a>
  <a href="#vendor" class="side-btn reg">
    <span class="sb-icon">📋</span><span class="sb-label">Register Now</span>
  </a>
</div>
<!-- HERO -->
<section class="hero text-center">
    <div class="container">
        <h1>Reliable Global Manpower Solutions</h1>
        <p>15+ Years | Pakistan · UAE · UK</p>

        <div class="mt-4">
            <a href="#" class="btn btn-danger btn-custom">Register as Company</a>
            <a href="#" class="btn btn-primary btn-custom">Request Manpower</a>
        </div>

        <p class="mt-4">
            ✔ Licensed OEP | ✔ 15+ Years Experience | ✔ Global Workforce Supply
        </p>
    </div>
</section>

<!-- ABOUT -->
<section class="py-5">
    <div class="container">
        <h2 class="section-title">About OGS Manpower</h2>

        <div class="row align-items-center">
            <div class="col-md-6">
                <img src="../images/about-ogs.png" class="img-fluid rounded">
            </div>
            <div class="col-md-6">
                <p>
                    <strong>OGS Manpower</strong>, a part of <strong>OGS Group of Companies</strong>, is a government-licensed overseas recruitment agency based in Pakistan, specializing in providing reliable manpower solutions to international employers.
</p><p>
Established in 2010, OGS has built a strong reputation in global recruitment by delivering skilled, semi-skilled, and professional workforce across multiple industries including construction, oil & gas, engineering, hospitality, and facility management.
                </p>
                <p>OGS Manpower is a legally registered entity under the Government of Pakistan <strong>(License No. 2978/RWP)</strong> and operates under strict compliance with international recruitment standards.

We are committed to connecting global employers with qualified talent through a transparent, ethical, and efficient recruitment process.</p>
            <div>
                <strong>Our mission is to:</strong>
<div style="margin-left:30px"><strong>
✔ Build long-term partnerships with employers<br/>
✔ Deliver reliable and skilled manpower solutions<br/>
✔ Empower candidates with global career opportunities<br/>
✔ Maintain integrity, transparency, and professionalism in every placement </strong></div>
            </div>
            </div>
            
        </div>
    </div>
</section>

<!-- WHY SECTION -->
<section class="why-section">
<div class="container text-center">

<div class="section-label">Why Choose OGS</div>
<h2 class="section-title">Why Employers <strong>Trust OGS</strong></h2>
<p>Click any card below to explore our strengths in detail.</p>

<div class="cards-grid">

@php
$features = [
['id'=>'m1','icon'=>'👔','title'=>'CEO Leadership','teaser'=>'25+ years in HR, recruitment, AI, and global workforce development.'],
        ['id'=>'m2','icon'=>'🏆','title'=>'15+ Years Experience','teaser'=>'OGS Group has been delivering complete HR solutions globally since 2010.'],
        ['id'=>'m3','icon'=>'🌍','title'=>'100+ Clients','teaser'=>'Trusted by 100+ clients across the Gulf region and international markets.'],
        ['id'=>'m4','icon'=>'⭐','title'=>'Strong Reputation','teaser'=>'R5 years of credibility and recognition in the global HR business community.'],
        ['id'=>'m5','icon'=>'💻','title'=>'Job Portal','teaser'=>'AI-powered recruitment platform with smart filters and full lifecycle tracking.'],
        ['id'=>'m6','icon'=>'📂','title'=>'Verified CV Bank','teaser'=>'Verified, deployment-ready candidates with zero documentation errors.'],
        ['id'=>'m9','icon'=>'✈️','title'=>'Travel Services','teaser'=>'Complete travel and ticketing solutions for smooth workforce mobility worldwide.'],
        ['id'=>'m10','icon'=>'🎓','title'=>'Student Consultancy','teaser'=>'Building tomorrow workforce through admissions, scholarships, and career guidance.'],
        ['id'=>'m11','icon'=>'📡','title'=>'HR News Channel','teaser'=>'Dedicated platform delivering verified HR news and global workforce intelligence'],
        ['id'=>'m12','icon'=>'📱','title'=>'Social Media Reach','teaser'=>'Massive global digital presence enabling rapid talent sourcing and branding.'],
        ['id'=>'m13','icon'=>'🖥️','title'=>'Digital Interview Lab','teaser'=>'HD video interview facility eliminating geography as a hiring barriey'],
        ['id'=>'m14','icon'=>'🔧','title'=>'Trade Test Centre','teaser'=>'Skills certification ensuring only qualified, job-ready workers are deployed.'],
      ];
@endphp

@foreach($features as $i => $f)
<div class="feature-card" onclick="openModal('{{ $f['id'] }}')">
<span>{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span>
<div class="fc-icon">{{ $f['icon'] }}</div>
<div class="fc-title">{{ $f['title'] }}</div>
<div class="fc-teaser">{{ $f['teaser'] }}</div>
<div class="fc-click">Click to Learn More →</div>
</div>
@endforeach

</div>
</div>
</section>

<!-- VIDEO TESTIMONIALS WITH BACKGROUND -->
<section class="video-section text-center">
    <div class="overlay">
        <div class="container">
            <h2 class="section-title text-white mb-5">OGS Journey</h2>

            <div class="row">

                @php
                $videos = [
                    'https://www.youtube.com/embed/VIDEO_ID_1',
                    'https://www.youtube.com/embed/VIDEO_ID_2',
                    'https://www.youtube.com/embed/VIDEO_ID_3',
                    'https://www.youtube.com/embed/VIDEO_ID_4',
                ];
                @endphp

                @foreach($videos as $video)
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="video-card">

                        <div class="ratio ratio-16x9">
                            <iframe 
                                src="{{ $video }}" 
                                allowfullscreen>
                            </iframe>
                        </div>

                    </div>
                </div>
                @endforeach

            </div>
        </div>
    </div>
</section>

<!-- PROFESSIONAL COUNTER SECTION -->
<section class="counter-section py-5">
    <div class="container">
        <div class="row text-center">

            @php
            $stats = [
                ['icon'=>'fa-calendar','number'=>'15+','label'=>'Years Experience'],
                ['icon'=>'fa-users','number'=>'10K+','label'=>'Workers Deployed'],
                ['icon'=>'fa-building','number'=>'200+','label'=>'Clients'],
                ['icon'=>'fa-globe','number'=>'5+','label'=>'Countries Served'],
            ];
            @endphp

            @foreach($stats as $item)
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="counter-card">

                    <div class="icon">
                        <i class="fas {{ $item['icon'] }}"></i>
                    </div>

                    <h3>{{ $item['number'] }}</h3>
                    <p>{{ $item['label'] }}</p>

                </div>
            </div>
            @endforeach

        </div>
    </div>
</section>

<!-- GLOBAL PRESENCE CARD (PIXEL PERFECT) -->
<section class="py-5">
    <div class="container">

        <h2 class="text-center fw-bold mb-4">Our Global Presence</h2>

        <div class="global-card">

            <div class="row align-items-center">

                <!-- LEFT CONTENT -->
                <div class="col-ng-5" style="width:25%;">
                    <div class="portal-box">

                        <img src="{{ asset('images/team.png') }}" class="portal-img">

                        <h4 class="mt-3">
                            Our Candidate <span class="text-danger">Portal</span>
                        </h4>

                        <p>Find Pre-Screened Candidates Instantly</p>

                        <ul>
                            <li>✔ Search & Filter Profiles</li>
                            <li>✔ Verified CVs</li>
                            <li>✔ Video Interviews</li>
                        </ul>

                        <a href="#" class="btn btn-primary">Explore Candidates</a>

                    </div>
                </div>

                <!-- RIGHT MAP -->
                <div class="col-ng-7" style="width:75%;">
                    <div class="map-area">

                        <!-- BACKGROUND IMAGE -->
                        <img src="{{ asset('images/global-bg.png') }}" class="map-bg">

                        <!-- UAE -->
                        <div class="map-pin uae">
                            <span class="pin-dot green"></span>
                            <div class="pin-label">UAE</div>
                        </div>

                        <!-- PAKISTAN -->
                        <div class="map-pin pakistan">
                            <span class="pin-dot dark"></span>
                            <div class="pin-label">Pakistan</div>
                        </div>

                        <!-- UK -->
                        <div class="map-pin uk">
                            <span class="pin-dot red"></span>
                            <div class="pin-label">United Kingdom</div>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>
</section>
<!-- INDUSTRIES (SMALL ICON STYLE) -->
<section class="industries2-section py-5">
    <div class="container text-center">
        <h2 class="fw-bold mb-5">Industries We Serve</h2>

        <div class="row justify-content-center">

            @php
            $industries = [
                ['name'=>'Construction','icon'=>'/icons/construction.png'],
                ['name'=>'Oil & Gas','icon'=>'/icons/oil-gas.png'],
                ['name'=>'Hospitality','icon'=>'/icons/hospitality.png'],
                ['name'=>'Facility','icon'=>'/icons/facility.png'],
                ['name'=>'Security','icon'=>'/icons/security.png'],
                ['name'=>'Engineering','icon'=>'/icons/engineering.png'],
            ];
            @endphp

            @foreach($industries as $ind)
            <div class="col-lg-2 col-md-4 col-6 mb-4">
                <div class="industry-card">

                    <img src="{{ asset($ind['icon']) }}" alt="{{ $ind['name'] }}">

                    <h6>{{ $ind['name'] }}</h6>

                </div>
            </div>
            @endforeach

        </div>
    </div>
</section>
<!-- CTA -->
<!-- SOCIAL MEDIA (OGS STYLE) -->
<section class="industries-section py-5">
    <div class="container text-center">

        <h2 class="section-title mb-5">Connect With OGS Manpower</h2>

        <div class="row justify-content-center">

            @php
            $socials = [
                ['name'=>'WhatsApp Group','icon'=>'icons/social/whatsapp.png','link'=>'https://chat.whatsapp.com/G0YIKjgkSy90j9bIN1LuDU?mode=gi_t'],
                ['name'=>'Facebook','icon'=>'icons/social/facebook.png','link'=>'https://www.facebook.com/ogs.official'],
                ['name'=>'TikTok','icon'=>'icons/social/tiktok.png','link'=>'https://www.tiktok.com/@ogs.manpower'],
                ['name'=>'Instagram','icon'=>'icons/social/instagram.png','link'=>'https://www.instagram.com/ogsmanpower'],
                ['name'=>'WhatsApp Channel','icon'=>'icons/social/whatsapp.png','link'=>'https://whatsapp.com/channel/0029VaCbduB9Gv7RhaugxS0Z'],
                ['name'=>'X (Twitter)','icon'=>'icons/social/twitter.png','link'=>'https://x.com/ogsmanpower'],
                ['name'=>'LinkedIn','icon'=>'icons/social/linkedin.png','link'=>'https://www.linkedin.com/company/ogsmanpower/'],
                ['name'=>'YouTube','icon'=>'icons/social/youtube.png','link'=>'https://youtube.com/@ogsgroupofficial'],
            ];
            @endphp

            @foreach($socials as $item)
            <div class="col-lg-2 col-md-4 col-6 mb-4">
                <a href="{{ $item['link'] }}" target="_blank" class="social-card">

                    <div class="icon-box">
                        <img src="{{ asset($item['icon']) }}" alt="{{ $item['name'] }}">
                    </div>

                    <h6>{{ $item['name'] }}</h6>

                </a>
            </div>
            @endforeach

        </div>

    </div>
</section>
<section class="cta">
    <div class="container">
        <div class="row">

            <div class="col-md-7">
                <h4>Register as Company</h4>

                <input class="form-control mb-2" placeholder="Company Name">
                <input class="form-control mb-2" placeholder="Email">
                <textarea class="form-control mb-3" placeholder="Requirement"></textarea>

                <button class="btn btn-danger w-100">Submit</button>
            </div>

            <div class="col-md-5 text-center">
                <h4>CEO Message</h4>
                <p style="text-align:justify;">
                    At OGS Manpower, we are committed to delivering reliable, skilled, and pre-screened manpower solutions to global employers. With over 15 years of experience and operations across Pakistan, UAE, and the UK, we focus on quality, transparency, and long-term partnerships. Our goal is to support business growth while creating better career opportunities worldwide.
                </p>
            </div>

        </div>
    </div>
</section>

<!-- ═══════ MODALS ═══════ -->
<div class="modal-overlay" id="m1" onclick="closeOnOverlay(event,'m1')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">👔</span><div><h3>Abdul Basit Malik — CEO &amp; Founder</h3><p class="mh-sub">25+ Years · HR Leadership · Global Recruitment Expert · AI &amp; IR 4.0</p></div></div><button class="modal-close" onclick="closeModal('m1')">✕</button></div>
  <div class="modal-body">
    <p>Abdul Basit Malik brings over <strong>25 years of expertise</strong> in Recruitment, Career Counselling, Labour Market Research, Business Migration Analysis, AI, and Industrial Revolution 4.0. Beginning in 1991 with the Pakistan Air Force's Directorate of Recruitment, Training &amp; Publicity, he built a methodical, results-driven approach to human capital development that today powers CareerWorkforce.</p>
    <h4>Current Roles &amp; Certifications</h4>
    <ul>
      <li>Authorized Overseas Employment Promoter — OGS Manpower (License No. MPD/2978/RWP)</li>
      <li>Certified Chief Master Trainer &amp; Lead Assessor — City and Guilds UK</li>
      <li>Chairman, "Pakistan Digital Transformation Vision 2025" at RCCI</li>
      <li>Former Chairman HRD &amp; Overseas Pakistani — RCCI</li>
      <li>Accreditation Expert — National Accreditation Council for TVS (NAC-TVS)</li>
      <li>Certified Career Counselor — Quality International Study Abroad Network UK</li>
      <li>Expert Curriculum Developer — Competency Based Training &amp; Assessment (CBT &amp; A)</li>
      <li>GIZ Freelance TVET Consultant — Training Package Material Developer</li>
      <li>Member, Pakistan Overseas Employment Promoters Association</li>
    </ul>
    <h4>Academic Qualifications</h4>
    <ul>
      <li>Master in Computer Science (Final Stage)</li>
      <li>Bachelor in Computer Applications</li>
      <li>Associate Engineering Diploma — Electronics &amp; Telecommunications</li>
      <li>USAID Small &amp; Medium Enterprises Training</li>
      <li>Labour Market Need Assessment for Demand Driven TVET</li>
      <li>Career Counseling &amp; Vocational Guidance Skills Certification</li>
      <li>New Dimensions of TVET with Innovation &amp; Technopreneurship</li>
    </ul>
    <h4>Core Expertise Areas</h4>
    <p>Oil &amp; Gas · Alternative Energy · Green Skills · Sustainable Development · AI &amp; IR 4.0 · Civil Defence · Trade Testing · Curriculum Design · Aptitude, Psychological &amp; Intelligent Assessment · RPL · Event Management · SEO &amp; Digital Marketing. Published author in leading Pakistani English and Urdu national newspapers.</p>
    <div><span class="modal-tag">City &amp; Guilds UK</span><span class="modal-tag">Licensed OEP</span><span class="modal-tag">AI &amp; IR 4.0</span><span class="modal-tag">Oil &amp; Gas</span><span class="modal-tag">TVET Specialist</span><span class="modal-tag">GIZ Consultant</span></div>
    <button class="modal-cta" onclick="window.location.href='{{ route('register') }}?type=company'">Register as Company →</button>
  </div>
</div></div>

<div class="modal-overlay" id="m2" onclick="closeOnOverlay(event,'m2')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">🏆</span><div><h3>15+ Years Recruitment Experience</h3><p class="mh-sub">OGS Group of Companies · Established 2010 · Complete HR Solutions</p></div></div><button class="modal-close" onclick="closeModal('m2')">✕</button></div>
  <div class="modal-body">
    <p>Since 2010, OGS Group of Companies has built an unmatched track record in international recruitment. With <strong>15+ years of continuous operation</strong>, we have evolved into a fully integrated global HR solutions provider — far beyond a traditional staffing agency.</p>
    <h4>What 15+ Years Means for You</h4>
    <ul>
      <li>Deep understanding of international compliance, visa, and immigration frameworks</li>
      <li>Established relationships with embassies, government bodies, and trade partners</li>
      <li>Battle-tested recruitment processes refined through thousands of successful deployments</li>
      <li>Industry-specific expertise across 6+ major global sectors</li>
      <li>Proven crisis management and rapid-deployment capabilities for urgent requirements</li>
      <li>15-year milestone ceremony held at OGS Pakistan HQ — a testament to longevity and credibility</li>
    </ul>
    <h4>Complete HR Solutions Portfolio</h4>
    <ul>
      <li>Talent sourcing, skills assessment, and trade testing</li>
      <li>Documentation, visa processing, and compliance management</li>
      <li>Ticketing, deployment logistics, and post-placement support</li>
      <li>HR consultancy, student advisory, and travel &amp; tourism services</li>
    </ul>
    <p>OGS Group is <strong>fully capable of providing end-to-end human resource solutions</strong> — all under one trusted roof, eliminating the need for multiple vendors.</p>
    <div><span class="modal-tag">Est. 2010</span><span class="modal-tag">Govt. Licensed</span><span class="modal-tag">15-Year Milestone</span><span class="modal-tag">End-to-End HR</span></div>
    <button class="modal-cta" onclick="window.location.href='{{ route('register') }}?type=company'">Partner With Us →</button>
  </div>
</div></div>

<div class="modal-overlay" id="m3" onclick="closeOnOverlay(event,'m3')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">🌍</span><div><h3>100+ International Clients</h3><p class="mh-sub">Gulf Region · GCC Countries · Global Markets · Long-Term Partnerships</p></div></div><button class="modal-close" onclick="closeModal('m3')">✕</button></div>
  <div class="modal-body">
    <p>OGS has proudly established long-standing partnerships with <strong>over 100 clients</strong> across the Gulf region and international markets, reflecting credibility, reliability, and consistent delivery of high-quality workforce solutions.</p>
    <h4>Industries Served Across Our Client Base</h4>
    <ul>
      <li>Construction, Oil &amp; Gas, and Engineering</li>
      <li>Healthcare and Medical Services</li>
      <li>Hospitality, Tourism, and Facility Management</li>
      <li>Logistics, Manufacturing, and Information Technology</li>
    </ul>
    <h4>Why Clients Choose to Stay</h4>
    <p>The strength of OGS lies in <strong>enduring relationships</strong> built on professionalism, transparency, and performance. Through a client-centric approach, OGS delivers tailored recruitment solutions aligned with each organization's specific operational and cultural requirements — resulting in a high rate of repeat business and long-term GCC collaborations.</p>
    <h4>Our Confidentiality Policy</h4>
    <p>To protect client privacy and uphold professional standards, OGS follows a strict non-disclosure policy. Specific client names and project details are shared only upon request with appropriate consent — reinforcing trust and professionalism at every level.</p>
    <p><strong>For HR managers, this client portfolio signals proven expertise, operational reliability, and capacity to manage large-scale recruitment assignments with precision.</strong></p>
    <div><span class="modal-tag">100+ Clients</span><span class="modal-tag">GCC Markets</span><span class="modal-tag">Repeat Business</span><span class="modal-tag">Confidentiality Assured</span></div>
    <button class="modal-cta" onclick="window.location.href='{{ route('register') }}?type=company'">Join Our Client Network →</button>
  </div>
</div></div>

<div class="modal-overlay" id="m4" onclick="closeOnOverlay(event,'m4')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">⭐</span><div><h3>Strong HR Brand Reputation</h3><p class="mh-sub">Recognized · Trusted · Respected Globally Since 2010</p></div></div><button class="modal-close" onclick="closeModal('m4')">✕</button></div>
  <div class="modal-body">
    <p>Established in 2010, OGS has grown into a <strong>recognized and trusted name</strong> in the global HR and recruitment industry. The 15-year milestone ceremony at OGS Pakistan HQ reflects not just longevity, but a journey of credibility, growth, and strong positioning within the international HR business community.</p>
    <h4>What Our Brand Delivers to HR Managers</h4>
    <ul>
      <li>Government-licensed and compliant — trusted by regulatory authorities internationally</li>
      <li>Consistent brand promise: "Right talent, at the right time, every time"</li>
      <li>Recognized specialist in oil &amp; gas, construction, and technical trades globally</li>
      <li>Strong and growing LinkedIn, Facebook, and Instagram presence</li>
      <li>Contributions to workforce development through training and skill enhancement</li>
      <li>Structured recruitment: sourcing → screening → documentation → deployment</li>
    </ul>
    <h4>More Than Recruitment</h4>
    <p>OGS is a <strong>strategic HR solutions provider</strong> committed to long-term partnerships. Its reputation is driven by proven performance, ethical standards, and a deep understanding of global workforce demands. For HR managers across different countries, OGS represents confidence, consistency, and a trusted gateway to skilled manpower from Pakistan and beyond.</p>
    <div><span class="modal-tag">15-Year Milestone</span><span class="modal-tag">Ethical Recruitment</span><span class="modal-tag">Brand Credibility</span><span class="modal-tag">Strategic HR Partner</span></div>
    <button class="modal-cta"   onclick="window.location.href='{{ route('register') }}?type=company'">Work With a Trusted Brand →</button>
  </div>
</div></div>

<div class="modal-overlay" id="m5" onclick="closeOnOverlay(event,'m5')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">💻</span><div><h3>Comprehensive Job Portal</h3><p class="mh-sub">AI-Powered · End-to-End Tracking · Built for HR Leaders Who Demand Results</p></div></div><button class="modal-close" onclick="closeModal('m5')">✕</button></div>
  <div class="modal-body">
    <p><strong>Stop wasting time on unqualified applicants and slow hiring processes.</strong> With the CareerWorkforce Talent Portal, you gain direct access to a verified, job-ready global workforce — all in one powerful, intelligent platform.</p>
    <h4>Smart Candidate Filtering</h4>
    <ul>
      <li>Skills &amp; Trade · Country &amp; City · Experience Level</li>
      <li>Age, Gender, and Job Category filters</li>
      <li>No chaos. No guesswork. Just precision hiring.</li>
    </ul>
    <h4>Complete Hiring System — Not Just a Job Board</h4>
    <ul>
      <li>Track candidates: Application → Shortlisting → Interview → Selection → Deployment</li>
      <li>Manage contracts, documentation, and visa processing in one place</li>
      <li>Collaborate with agencies, consultants, and partners globally</li>
      <li>Real-time dashboards for full candidate progress visibility</li>
    </ul>
    <h4>AI-Powered Recruitment Advantage</h4>
    <ul>
      <li>Identify top candidates instantly with AI-driven recommendations</li>
      <li>Reduce hiring time by up to 70% through intelligent automation</li>
      <li>Eliminate irrelevant applications automatically</li>
      <li>Smart matching based on your exact job specifications</li>
    </ul>
    <h4>Total Control — From Application to Deployment</h4>
    <p>Selection → Documentation → Visa → Ticketing → Deployment — all tracked in one system. Whether hiring 5 or 5,000 workers, OGS provides a structured, verified pipeline with full visibility and secure document handling.</p>
    <div><span class="modal-tag">AI-Powered</span><span class="modal-tag">Real-Time Tracking</span><span class="modal-tag">70% Faster Hiring</span><span class="modal-tag">Global Scale</span></div>
    <button class="modal-cta" onclick="window.location.href='{{ route('register') }}?type=company'">Access the Portal →</button>
  </div>
</div></div>

<div class="modal-overlay" id="m6" onclick="closeOnOverlay(event,'m6')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">📂</span><div><h3>Accurate CV Data Bank</h3><p class="mh-sub">Verified · Deployment-Ready · Zero Documentation Errors</p></div></div><button class="modal-close" onclick="closeModal('m6')">✕</button></div>
  <div class="modal-body">
    <p>Unlike common job portals where documentation inconsistencies cause costly visa rejections and delays, the OGS CV Data Bank is built around <strong>recruitment-grade accuracy</strong>. Every candidate profile is verified before it ever reaches an HR manager's desk.</p>
    <h4>What Sets Our Data Bank Apart</h4>
    <ul>
      <li><strong>Exact Name Matching:</strong> Verified across passport, certificates, and all official documents</li>
      <li><strong>Valid Passports:</strong> Expiry checked against international deployment requirements</li>
      <li><strong>Complete Documentation:</strong> Qualifications, experience letters, and IDs organized and reviewed</li>
      <li><strong>Pre-Verified Credentials:</strong> Structured vetting for authenticity before listing</li>
      <li><strong>Deployment Readiness:</strong> Many candidates cleared for medical, trade test, and orientation</li>
    </ul>
    <h4>Advanced Search Capabilities</h4>
    <p>Filter candidates by industry, job title, qualifications, experience level, and deployment readiness. Our technology-driven system enhances transparency and operational efficiency — making recruitment seamless and dependable.</p>
    <h4>The Business Case for HR Managers</h4>
    <p>Accurate documentation means <strong>no visa rejections, no costly delays, no administrative losses</strong>. OGS delivers verified talent with precision, reliability, and global compliance — protecting your time, reputation, and budget from day one.</p>
    <div><span class="modal-tag">Zero Doc Errors</span><span class="modal-tag">Visa-Ready</span><span class="modal-tag">Pre-Verified</span><span class="modal-tag">Fast Shortlisting</span></div>
    <button class="modal-cta" onclick="window.location.href='{{ route('register') }}?type=candidate'">Access Verified Candidates →</button>
  </div>
</div></div>

<div class="modal-overlay" id="m7" onclick="closeOnOverlay(event,'m7')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">🏙️</span><div><h3>Dubai HR Consultancy Office</h3><p class="mh-sub">Gulf Business Hub · Middle East Recruitment Gateway · GCC Network</p></div></div><button class="modal-close" onclick="closeModal('m7')">✕</button></div>
  <div class="modal-body">
    <p>Strategically located in Dubai — one of the world's most dynamic business and recruitment hubs — the OGS HR Consultancy Office serves as a central platform for international workforce solutions and professional networking across the Middle East, Asia, and Africa.</p>
    <h4>Multi-Country Talent Sourcing</h4>
    <ul>
      <li>Pakistan, India, Nepal, Sri Lanka, Philippines, Bangladesh</li>
      <li>South African nations, Egypt, Lebanon, and other global markets</li>
      <li>Multicultural, versatile workforce tailored for diverse Gulf industry needs</li>
    </ul>
    <h4>Business Networking &amp; Collaboration</h4>
    <ul>
      <li>Foster collaboration among recruitment agencies and HR professionals</li>
      <li>Exchange industry insights and develop strategic partnerships</li>
      <li>Face-to-face consultations for transparent and efficient recruitment</li>
      <li>Respond swiftly to market demands through established Gulf relationships</li>
    </ul>
    <h4>End-to-End Gulf Recruitment</h4>
    <p>Talent sourcing → candidate screening → documentation → visa facilitation → deployment management. Dubai's advanced infrastructure enables OGS to streamline communication, reduce turnaround times significantly, and strengthen employer trust through proximity to key GCC markets.</p>
    <div><span class="modal-tag">Dubai Office</span><span class="modal-tag">GCC Specialists</span><span class="modal-tag">Multi-Country Sourcing</span><span class="modal-tag">Fast Deployment</span></div>
    <button class="modal-cta" onclick="window.location.href='{{ route('register') }}?type=company'">Connect via Dubai Office →</button>
  </div>
</div></div>

<div class="modal-overlay" id="m8" onclick="closeOnOverlay(event,'m8')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">🇬🇧</span><div><h3>UK Office — European Network</h3><p class="mh-sub">EU Gateway · Regulatory Compliance · Professional Employer Partnerships</p></div></div><button class="modal-close" onclick="closeModal('m8')">✕</button></div>
  <div class="modal-body">
    <p>The OGS UK Office is a strategic gateway for networking and recruitment across the <strong>European Union and the wider European region</strong>, positioned within one of the world's most influential business and financial centers.</p>
    <h4>European Industries Served</h4>
    <ul>
      <li>Healthcare, Engineering, Construction, and Hospitality</li>
      <li>Logistics, Manufacturing, Agriculture, and Information Technology</li>
      <li>Access to UK and EU employers seeking compliant international talent</li>
    </ul>
    <h4>Global Talent for European Employers</h4>
    <ul>
      <li>Sourcing from Pakistan, India, Nepal, Sri Lanka, Philippines, Bangladesh</li>
      <li>South African nations, Egypt, Lebanon, and other global markets</li>
      <li>Culturally adaptable, multilingual, and skills-verified candidates</li>
    </ul>
    <h4>Compliance-First Recruitment</h4>
    <p>All workforce mobilization aligns with UK, EU, and international legal and professional frameworks. OGS's commitment to ethical recruitment and regulatory compliance builds confidence among European HR managers — positioning CareerWorkforce as a dependable, long-term partner in the region.</p>
    <div><span class="modal-tag">UK Office</span><span class="modal-tag">EU Compliance</span><span class="modal-tag">European Network</span><span class="modal-tag">Ethical Recruitment</span></div>
    <button class="modal-cta" onclick="window.location.href='{{ route('register') }}?type=company'">Connect via UK Office →</button>
  </div>
</div></div>

<div class="modal-overlay" id="m9" onclick="closeOnOverlay(event,'m9')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">✈️</span><div><h3>OGS Travel &amp; Tourism</h3><p class="mh-sub">Workforce Mobility · Ticketing · Corporate Travel Management</p></div></div><button class="modal-close" onclick="closeModal('m9')">✕</button></div>
  <div class="modal-body">
    <p>OGS Travel and Tourism is the dedicated workforce mobility division of the OGS Group, specializing in comprehensive travel and ticketing solutions designed specifically for <strong>international manpower deployment</strong> and corporate travel management.</p>
    <h4>Core Travel Services</h4>
    <ul>
      <li>Group and individual worker ticketing for international deployment</li>
      <li>Cost-effective airline bookings with preferred carrier agreements</li>
      <li>Visa facilitation and travel documentation coordination</li>
      <li>Corporate travel management for HR managers and client representatives</li>
      <li>Emergency travel arrangements and last-minute deployment support</li>
      <li>Transit assistance and layover management across major global hubs</li>
    </ul>
    <h4>Strategic Value for HR Managers</h4>
    <p>By integrating travel services within the OGS recruitment system, HR managers benefit from a seamless transition from candidate selection to physical arrival. This eliminates the complexity of coordinating with multiple vendors, reduces costs, and ensures candidates arrive on time, on budget, and fully documented.</p>
    <h4>Umrah &amp; Religious Travel</h4>
    <p>OGS also offers Umrah and religious travel packages, demonstrating a commitment to holistic service delivery and community support beyond pure recruitment.</p>
    <div><span class="modal-tag">Group Ticketing</span><span class="modal-tag">Visa Coordination</span><span class="modal-tag">Corporate Travel</span><span class="modal-tag">Umrah Services</span></div>
    <button class="modal-cta" onclick="window.location.href='{{ route('register') }}?type=company'">Register as Company →</button>
  </div>
</div></div>

<div class="modal-overlay" id="m10" onclick="closeOnOverlay(event,'m10')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">🎓</span><div><h3>OGS Student Consultancy</h3><p class="mh-sub">Admissions · Scholarships · Global Education · Future Workforce Pipeline</p></div></div><button class="modal-close" onclick="closeModal('m10')">✕</button></div>
  <div class="modal-body">
    <p>OGS Student Consultancy is a strategic initiative dedicated to <strong>developing the future global workforce</strong>. Beyond immediate recruitment, OGS nurtures tomorrow's talent by guiding students toward educational pathways that align with international employer demands.</p>
    <h4>Study Destinations &amp; Fields Supported</h4>
    <ul>
      <li>United Kingdom, Europe, Middle East, and other global destinations</li>
      <li>Internationally recognized academic and vocational programs</li>
      <li>High-demand fields: Healthcare, Engineering, IT, Hospitality, Construction, Business Management</li>
    </ul>
    <h4>Our Student Consultancy Services</h4>
    <ul>
      <li>University and college admissions guidance and application support</li>
      <li>Scholarship identification, eligibility, and application management</li>
      <li>Career planning, skill development, and professional roadmaps</li>
      <li>Language proficiency preparation and cultural orientation programs</li>
      <li>Bridging the gap between education and employment</li>
    </ul>
    <h4>Strategic Value for Employers</h4>
    <p>For HR managers, this initiative provides access to a <strong>pre-prepared, continuously growing talent pipeline</strong> trained in line with industry requirements. This reduces recruitment risks, shortens onboarding time, and enhances employee retention — making OGS a valuable long-term workforce planning partner.</p>
    <div><span class="modal-tag">UK Admissions</span><span class="modal-tag">Scholarships</span><span class="modal-tag">Career Guidance</span><span class="modal-tag">Future Workforce</span></div>
    <button class="modal-cta" onclick="window.location.href='{{ route('register') }}?type=company'">Partner With Us →</button>
  </div>
</div></div>

<div class="modal-overlay" id="m11" onclick="closeOnOverlay(event,'m11')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">📡</span><div><h3>HR International News Channel</h3><p class="mh-sub">Verified Global Workforce Intelligence · HR Industry Insights</p></div></div><button class="modal-close" onclick="closeModal('m11')">✕</button></div>
  <div class="modal-body">
    <p>The HR International News Channel is a pioneering initiative of the OGS Group, established to provide <strong>authentic, timely, and insightful information</strong> about the global human resource industry — a trusted intelligence platform for HR managers, recruiters, and employers worldwide.</p>
    <h4>What the Channel Covers</h4>
    <ul>
      <li>Global job market trends and sector-specific workforce demand forecasts</li>
      <li>International recruitment practices and industry best standards</li>
      <li>Labour laws, immigration regulations, and policy updates by region</li>
      <li>Workforce mobility and cross-border employment frameworks</li>
      <li>Emerging employment opportunities and regional market intelligence</li>
      <li>Ethical recruitment and international compliance best practices</li>
    </ul>
    <h4>Why HR Managers Subscribe</h4>
    <ul>
      <li>Every piece of information carefully reviewed for accuracy and relevance</li>
      <li>Expert interviews and real-time updates from verified global sources</li>
      <li>Rapidly growing presence across LinkedIn, Facebook, and social platforms</li>
      <li>Empowers data-driven recruitment strategy and talent market anticipation</li>
    </ul>
    <h4>OGS as an Industry Thought Leader</h4>
    <p>By launching this platform, OGS extends its role from recruitment agency to <strong>knowledge partner and global HR thought leader</strong> — contributing to a more informed, ethical, and efficient international labour market.</p>
    <div><span class="modal-tag">HR Intelligence</span><span class="modal-tag">Labour Law Updates</span><span class="modal-tag">Market Analysis</span><span class="modal-tag">Thought Leadership</span></div>
    <button class="modal-cta" onclick="window.location.href='{{ route('register') }}?type=company'">Register &amp; Stay Informed →</button>
  </div>
</div></div>

<div class="modal-overlay" id="m12" onclick="closeOnOverlay(event,'m12')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">📱</span><div><h3>Strong Social Media Network</h3><p class="mh-sub">Global Reach · Rapid Talent Sourcing · Employer Branding</p></div></div><button class="modal-close" onclick="closeModal('m12')">✕</button></div>
  <div class="modal-body">
    <p>OGS has established a powerful and dynamic social media presence serving as a <strong>strategic platform for global HR networking and rapid talent acquisition</strong> across LinkedIn, Facebook, Instagram, and other leading digital channels.</p>
    <h4>Our Global Talent Sourcing Reach</h4>
    <ul>
      <li>Pakistan, India, Nepal, Sri Lanka, Philippines, Bangladesh</li>
      <li>South African nations, Egypt, Lebanon, and international markets</li>
      <li>Skilled, semi-skilled, and professional candidates continuously updated</li>
    </ul>
    <h4>Why Social Media Accelerates Your Hiring</h4>
    <ul>
      <li>Instant dissemination of job opportunities to a highly engaged global audience</li>
      <li>Rapid candidate responses resulting in a larger, highly relevant applicant pool</li>
      <li>Industry-specific targeted campaigns for precise talent attraction</li>
      <li>Faster shortlisting capability for urgent manpower requirements</li>
    </ul>
    <h4>Integrated with Our Verified Recruitment System</h4>
    <p>Candidates sourced through social channels are funnelled directly into OGS's verified CV databank where they undergo <strong>full documentation screening</strong> before reaching HR managers. Social speed combined with recruitment-grade verification — the best of both worlds.</p>
    <div><span class="modal-tag">LinkedIn</span><span class="modal-tag">Facebook</span><span class="modal-tag">Instagram</span><span class="modal-tag">Global Reach</span><span class="modal-tag">Verified Pipeline</span></div>
    <button class="modal-cta" onclick="window.location.href='{{ route('register') }}?type=company'">Join Our Network →</button>
  </div>
</div></div>

<div class="modal-overlay" id="m13" onclick="closeOnOverlay(event,'m13')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">🖥️</span><div><h3>Online Interview Digital Lab</h3><p class="mh-sub">HD Video · Zero Geographic Barriers · Bulk Interview Capability</p></div></div><button class="modal-close" onclick="closeModal('m13')">✕</button></div>
  <div class="modal-body">
    <p>OGS operates a state-of-the-art <strong>Online Interview Digitally Modern Equipped Lab</strong>, enabling HR managers to conduct real-time interviews with candidates from anywhere in the world — eliminating geography as a hiring barrier and significantly reducing recruitment time and costs.</p>
    <h4>Facility Specifications</h4>
    <ul>
      <li>High-speed internet connectivity for uninterrupted global sessions</li>
      <li>High-definition video conferencing and professional audio-visual equipment</li>
      <li>Secure, distraction-free, professionally designed interview environment</li>
      <li>Dedicated technical coordination team for full session management</li>
    </ul>
    <h4>What This Delivers for HR Managers</h4>
    <ul>
      <li>Interview candidates in Pakistan without travel or relocation expenses</li>
      <li>Quick, confident decision-making with full candidate visibility</li>
      <li>Support for bulk recruitment and urgent manpower requirements at scale</li>
      <li>Access to a wider pool of pre-screened, verified candidates on demand</li>
    </ul>
    <h4>Interview Today. Deploy Faster.</h4>
    <p>This modern digital infrastructure accelerates the entire hiring process — from initial interview to final selection — while maintaining transparency, professionalism, and the highest standards of candidate presentation. No compromise on quality. No geographic limitations.</p>
    <div><span class="modal-tag">HD Video Lab</span><span class="modal-tag">No Travel Required</span><span class="modal-tag">Bulk Interviews</span><span class="modal-tag">Fast Decisions</span></div>
    <button class="modal-cta" onclick="window.location.href='{{ route('register') }}?type=company'">Schedule Interviews →</button>
  </div>
</div></div>

<div class="modal-overlay" id="m14" onclick="closeOnOverlay(event,'m14')"><div class="modal-box">
  <div class="modal-header"><div style="display:flex;gap:1rem;align-items:flex-start;"><span class="modal-icon-lg">🔧</span><div><h3>Trade Test Centre</h3><p class="mh-sub">Skills Certification · International Standards · Pre-Deployment Verification</p></div></div><button class="modal-close" onclick="closeModal('m14')">✕</button></div>
  <div class="modal-body">
    <p>OGS operates a professionally managed <strong>Trade Test Centre</strong> designed to evaluate and certify practical candidate skills in accordance with international industry standards — ensuring only qualified, job-ready workers are deployed to your organization.</p>
    <h4>Trades Assessed at Our Centre</h4>
    <ul>
      <li>Welding and Fabrication</li>
      <li>Electrical and Plumbing</li>
      <li>Masonry and Carpentry</li>
      <li>Mechanical and Heavy Equipment Operation</li>
      <li>Oil &amp; Gas Technical Skills</li>
      <li>Other specialized skilled professions accommodated on request</li>
    </ul>
    <h4>Assessment Process &amp; Facilities</h4>
    <ul>
      <li>Modern tools, machinery, and simulated real-world work environments</li>
      <li>Experienced and internationally certified assessors</li>
      <li>Standardized testing procedures with employer-specific criteria</li>
      <li>Detailed written evaluation reports provided per candidate</li>
    </ul>
    <h4>The Business Impact for HR Managers</h4>
    <p>Pre-deployment trade testing <strong>minimizes recruitment risks, reduces onboarding time</strong>, and enhances workforce productivity from Day 1. When a candidate arrives certified by OGS Trade Test Centre, you can deploy with complete confidence — knowing their verified skills match your operational requirements precisely.</p>
    <div><span class="modal-tag">Certified Assessors</span><span class="modal-tag">Welding &amp; Electrical</span><span class="modal-tag">Oil &amp; Gas Trades</span><span class="modal-tag">Employer-Specific Testing</span></div>
    <button class="modal-cta" onclick="window.location.href='{{ route('register') }}?type=company'">Request Trade Testing →</button>
  </div>
</div></div>
<script>
function openModal(id){
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(id){
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = 'auto';
}
</script>

@endsection
@endsection