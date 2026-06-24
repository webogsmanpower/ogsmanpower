<!DOCTYPE html>
<html>
<head>
    <title>OTP Verification</title>
</head>
<body>
    <h1>Hello, {{ $username }}!</h1>
    <p>Your OTP code is: <strong>{{ $otp }}</strong></p>
    <p>This OTP is valid for 15 minutes. Please use this code to complete your verification.</p>
    <p>If you didn't request this, please ignore this email.</p>
</body>
</html>
