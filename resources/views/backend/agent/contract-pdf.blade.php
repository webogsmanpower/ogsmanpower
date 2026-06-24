<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Agreement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <!-- Logo -->
    <div class="header">
        <img src="{{ $setting->dark_logo_url }}" alt="Company Logo" style="width: 150px;">
    </div>

    <!-- Contract Content -->
    <h3>Contract Agreement</h3>
    <p>This agreement is made between:</p>
    <ul>
        <li><strong>Name:</strong> {{ auth()->user()->name }}</li>
        <li><strong>Email:</strong> {{ auth()->user()->email }}</li>
        <li><strong>Phone/WhatsApp:</strong> {{ auth()->user()->whatsapp }}</li>
        @if (auth()->user()->hasRole('recruitment agency'))
            <li><strong>Company:</strong> {{ auth()->user()->company }}</li>
        @endif
    </ul>
    <hr>
    <p>{!! $contract->content !!}</p>
    <hr>
    <p><strong>Signature:</strong> {{ auth()->user()->name }}</p>
    <p><strong>Date:</strong> {{ \Carbon\Carbon::now()->toDateString() }}</p>

    <!-- Footer -->
    <div class="footer">
        <p>© OGS Manpower. All rights reserved.</p>
    </div>
</body>

</html>
