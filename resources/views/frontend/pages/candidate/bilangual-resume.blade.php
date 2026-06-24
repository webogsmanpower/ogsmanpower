@php
    $salary = $jobRequirement->salary ?? null;
    $currency = $jobRequirement->currency ?? null;
    $searchCountry = $jobRequirement->searchcountry->name ?? null;
    $state = $jobRequirement->state->name ?? null;
    $city = $jobRequirement->city->name ?? null;
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

body{
font-family: Arial, sans-serif;
margin:0;
padding:0;
color:#333;
}

.container{
max-width:1000px;
margin:0 auto;
background:white;
padding:10px;
border-radius:5px;
border:2px solid #003366;
}

h3{
color:#003366;
margin-top:5px;
font-size:14px;
}

h1{
color:#003366;
}

table{
width:100%;
border-collapse:collapse;
}

th,td{
border:1px solid #ddd;
padding:4px;
font-size:12px;
}

.profile-image{
width:75px;
height:80px;
border-radius:50%;
border:2px solid #003366;
object-fit:cover;
margin:10px auto;
display:block;
}

.footer{
margin-top:0px;
text-align:center;
}

p{
font-size:11px;
}

</style>

</head>

<body>

<div class="container">

<!-- HEADER -->
<table style="border:0">

<tr>

<td style="width:40%;border:0;text-align:left">
<h1>{{ $candidate->user->name }}</h1>
</td>

<td style="border:0"></td>

<td style="width:40%;border:0;text-align:right">
<h1>{{ $translate->translate($candidate->user->name) }}</h1>
</td>

</tr>

</table>


<!-- BIO SECTION -->

<table>

<tr>

<td style="width:50%">

<h3>APPLIED FOR PRIVATE DRIVER</h3>

<table>

<tr>

<th>Expected Location</th>

<td>

@if($searchCountry)

{{ $searchCountry }}

@if($state) , {{ $state }} @endif
@if($city) , {{ $city }} @endif

@else

Anywhere

@endif

</td>

</tr>

<tr>

<th>Expected Salary</th>

<td>

{{ $salary ? $salary.$currency : 'N/A' }}

</td>

</tr>

</table>

{!! $candidate->bio !!}

</td>



<td style="width:50%;text-align:right">

<h3>{{ $translate->translate('APPLIED FOR PRIVATE DRIVER') }}</h3>

<table>

<tr>

<td>

@if($searchCountry)

{{ $translate->translate($searchCountry) }}

@if($state) , {{ $translate->translate($state) }} @endif
@if($city) , {{ $translate->translate($city) }} @endif

@else

{{ $translate->translate('Anywhere') }}

@endif

</td>

<th style="text-align:right">

{{ $translate->translate('Expected Location') }}

</th>

</tr>


<tr>

<td>

{{ $salary ? $translate->translate($salary.$currency) : $translate->translate('N/A') }}

</td>

<th style="text-align:right">

{{ $translate->translate('Expected Salary') }}

</th>

</tr>

</table>

@if($candidate->bio)

{!! $translate->translate($candidate->bio) !!}

@endif

</td>

</tr>

</table>



<!-- EXPERIENCE -->

<table>

<tr>

<td style="width:50%">

<h3>Experience</h3>

<table>

@foreach($candidate->experiences as $experience)

<tr>

<th>Company</th>
<td>{{ $experience->company }}</td>

</tr>

<tr>

<th>Designation</th>
<td>{{ $experience->designation }}</td>

</tr>

<tr>

<th>Period</th>

<td>

{{ formatTime($experience->start,'d M Y') }}

-

{{ $experience->currently_working ? 'Currently Working' : formatTime($experience->end,'d M Y') }}

</td>

</tr>

@endforeach

</table>

</td>



<td style="width:50%;text-align:right">

<h3>{{ $translate->translate('Experience') }}</h3>

<table>

@foreach($candidate->experiences as $experience)

<tr>

<td>{{ $translate->translate($experience->company) }}</td>

<th style="text-align:right">{{ $translate->translate('Company') }}</th>

</tr>

<tr>

<td>{{ $translate->translate($experience->designation) }}</td>

<th style="text-align:right">{{ $translate->translate('Designation') }}</th>

</tr>

<tr>

<td>

{{ $translate->translate(formatTime($experience->start,'d M Y')) }}

{{ $translate->translate($experience->currently_working ? 'Currently Working' : formatTime($experience->end,'d M Y')) }}

</td>

<th style="text-align:right">{{ $translate->translate('Period') }}</th>

</tr>

@endforeach

</table>

</td>

</tr>

</table>



<!-- EDUCATION -->

<table>

<tr>

<td style="width:50%">

<h3>Education</h3>

<table>

@foreach($candidate->educations as $education)

<tr>
<th>Level</th>
<td>{{ $education->level }}</td>
</tr>

<tr>
<th>Degree</th>
<td>{{ $education->degree }}</td>
</tr>

<tr>
<th>Year</th>
<td>{{ $education->year }}</td>
</tr>

@endforeach

</table>

</td>



<td style="width:50%;text-align:right">

<h3>{{ $translate->translate('Education') }}</h3>

<table>

@foreach($candidate->educations as $education)

<tr>

<td>{{ $translate->translate($education->level) }}</td>

<th style="text-align:right">{{ $translate->translate('Level') }}</th>

</tr>

<tr>

<td>{{ $translate->translate($education->degree) }}</td>

<th style="text-align:right">{{ $translate->translate('Degree') }}</th>

</tr>

<tr>

<td>{{ $translate->translate($education->year) }}</td>

<th style="text-align:right">{{ $translate->translate('Year') }}</th>

</tr>

@endforeach

</table>

</td>

</tr>

</table>



<!-- SKILLS -->

<table>

<tr>

<td style="width:50%">

<h3>Skills</h3>

<p>

@if($candidate->skills && $candidate->skills->count())

{{ $candidate->skills->pluck('name')->implode(', ') }}

@endif

</p>

</td>



<td style="width:50%;text-align:right">

<h3>{{ $translate->translate('Skills') }}</h3>

<p>

@if($candidate->skills && $candidate->skills->count())

{{ $translate->translate($candidate->skills->pluck('name')->implode(', ')) }}

@endif

</p>

</td>

</tr>

</table>



<!-- FOOTER -->

<div class="footer">

<p>© OGS Manpower. All rights reserved.</p>

</div>

</div>

</body>

</html>