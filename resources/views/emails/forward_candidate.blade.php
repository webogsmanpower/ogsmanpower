<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Candidate Profile</title>
</head>
<body>

<h2>Candidate Profile</h2>

<p><strong>Name:</strong> {{ $candidate->user->name }}</p>

<p><strong>Email:</strong> {{ $candidate->user->email }}</p>

<p><strong>Profession:</strong>
{{ $candidate->profession->name ?? '-' }}
</p>

<p><strong>Location:</strong>
{{ $candidate->exact_location ?? $candidate->full_address }}
</p>

<p><strong>Experience:</strong>
{{ $candidate->experience->name ?? '-' }}
</p>

<hr>

<p>The candidate CV and documents are attached with this email.</p>

<br>

<p>Regards</p>
<p>OGS Recruitment System</p>

</body>
</html>