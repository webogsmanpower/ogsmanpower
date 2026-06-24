<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">

<style>

body{
font-family: DejaVu Sans;
margin:0;
background:#f2f2f2;
}

.page{
width:210mm;
height:297mm;
margin:auto;
background:white;
}

/* GRID */

.left{
width:45%;
float:left;
padding:25px;
box-sizing:border-box;
}

.center{
width:10%;
float:left;
text-align:center;
position:relative;
}

.right{
width:45%;
float:left;
padding:25px;
box-sizing:border-box;
}

/* HEADER */

.header{
height:150px;
padding-top:20px;
}

.profile{
width:120px;
height:120px;
border-radius:50%;
border:6px solid #4f7f78;
object-fit:cover;
}

/* TIMELINE */

.line{
width:8px;
background:#4f7f78;
height:100%;
margin:auto;
}

.icon{
width:55px;
height:55px;
background:#4f7f78;
border-radius:50%;
color:white;
line-height:55px;
font-size:22px;
margin:20px auto;
}

/* SECTIONS */

.section{
margin-bottom:25px;
min-height:120px;
}

.section-title{
font-weight:bold;
border-bottom:4px solid #4f7f78;
padding-bottom:4px;
margin-bottom:10px;
color:#4f7f78;
}

p{
text-align:justify;
line-height:1.6;
margin:0;
}

ul{
margin:0;
padding-left:18px;
}

.rtl{
direction:rtl;
text-align:right;
}

.clear{
clear:both;
}

</style>

</head>

<body>

<div class="page">

<!-- HEADER -->

<div class="left rtl header">

<h2>{{ $translate->translate($candidate->user->name ?? '') }}</h2>

<p>{{ $translate->translate($candidate->profession->name ?? '') }}</p>

</div>

<div class="center header">

@if($candidate->photo)
<img src="{{ asset($candidate->photo) }}" class="profile">
@endif

</div>

<div class="right header">

<h2>{{ $candidate->user->name }}</h2>

<p>{{ $candidate->profession->name ?? '' }}</p>

<ul>

<li>Phone: {{ $contact->phone ?? '' }}</li>

<li>Email: {{ $candidate->user->email }}</li>

<li>Address: {{ $candidate->address ?? '' }}</li>

</ul>

</div>

<div class="clear"></div>

<!-- OBJECTIVE -->

<div class="left rtl section">

<div class="section-title">

{{ $translate->translate('Objective') }}

</div>

<p>

{{ $translate->translate(strip_tags($candidate->bio ?? '')) }}

</p>

</div>

<div class="center">

<div class="icon">🎯</div>

</div>

<div class="right section">

<div class="section-title">

OBJECTIVE

</div>

<p>

{{ strip_tags($candidate->bio ?? '') }}

</p>

</div>

<div class="clear"></div>

<!-- EXPERIENCE -->

<div class="left rtl section">

<div class="section-title">

{{ $translate->translate('Experience') }}

</div>

@foreach($experiences as $exp)

<p>

<strong>{{ $translate->translate($exp->position ?? '') }}</strong>

</p>

@endforeach

</div>

<div class="center">

<div class="icon">💼</div>

</div>

<div class="right section">

<div class="section-title">

EXPERIENCE

</div>

@foreach($experiences as $exp)

<p>

<strong>{{ $exp->position ?? '' }}</strong>

</p>

@endforeach

</div>

<div class="clear"></div>

<!-- EDUCATION -->

<div class="left rtl section">

<div class="section-title">

{{ $translate->translate('Education') }}

</div>

@foreach($educations as $edu)

<p>{{ $translate->translate($edu->degree ?? '') }}</p>

@endforeach

</div>

<div class="center">

<div class="icon">🎓</div>

</div>

<div class="right section">

<div class="section-title">

EDUCATION

</div>

@foreach($educations as $edu)

<p>{{ $edu->degree ?? '' }}</p>

@endforeach

</div>

<div class="clear"></div>

</div>

</body>
</html>