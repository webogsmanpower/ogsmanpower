<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume - {{ $seeker->user->first_name ?? 'Candidate' }}</title>
    <style>
        @page {
            margin: 32px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.5;
        }
        .resume {
            width: 100%;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0b7285;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .header h1 {
            font-size: 26px;
            margin: 0;
            letter-spacing: 1px;
        }
        .contact {
            margin-top: 6px;
            font-size: 11px;
            color: #475569;
        }
        .section {
            margin-bottom: 18px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #0b7285;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }
        .summary {
            white-space: pre-line;
        }
        .experience-item,
        .education-item {
            border-left: 3px solid #e2e8f0;
            padding-left: 10px;
            margin-bottom: 10px;
        }
        .item-header {
            font-weight: bold;
        }
        .item-subheader {
            color: #475569;
            font-style: italic;
            font-size: 11px;
        }
        .skills-table {
            width: 100%;
            border-collapse: collapse;
        }
        .skills-table td {
            padding: 2px 6px 2px 0;
        }
        .skill-chip {
            border: 1px solid #0b7285;
            padding: 4px 8px;
            font-size: 11px;
            display: inline-block;
        }
        .muted {
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="resume">
        <header class="header">
            <h1>{{ trim(($seeker->first_name ?? '') . ' ' . ($seeker->last_name ?? '')) ?: ($seeker->user->name ?? 'Candidate') }}</h1>
            <div class="contact">
                @php
                    $basicInfo = $seeker->resume->basic_information ?? [];
                    $contactParts = array_filter([
                        $basicInfo['email'] ?? $seeker->user->email ?? null,
                        $seeker->user->mobile ?? null,
                        $basicInfo['city'] ?? $seeker->current_location ?? null,
                    ]);
                @endphp
                {{ implode(' • ', $contactParts) }}
            </div>
        </header>

        @if($seeker->resume && isset($seeker->resume->professional_summary))
        <section class="section">
            <h2 class="section-title">Professional Summary</h2>
            <div class="summary">{{ $seeker->resume->professional_summary }}</div>
        </section>
        @endif

        @if($seeker->resume && isset($seeker->resume->work_experience) && is_array($seeker->resume->work_experience) && count($seeker->resume->work_experience) > 0)
        <section class="section">
            <h2 class="section-title">Work Experience</h2>
            @foreach($seeker->resume->work_experience as $exp)
            <div class="experience-item">
                <div class="item-header">
                    <table width="100%" style="border-collapse: collapse;">
                        <tr>
                            <td style="font-weight: bold;">{{ $exp['job_title'] ?? '' }}</td>
                            <td style="text-align: right; font-weight: bold;">
                                {{ $exp['start_date'] ?? '' }} - {{ $exp['end_date'] ?? 'Present' }}
                            </td>
                        </tr>
                    </table>
                </div>
                @if(isset($exp['company']) || isset($exp['location']))
                <div class="item-subheader">
                    {{ $exp['company'] ?? '' }}@if(isset($exp['location'])) • {{ $exp['location'] }}@endif
                </div>
                @endif
                @if(isset($exp['description']))
                <div>{{ $exp['description'] }}</div>
                @endif
            </div>
            @endforeach
        </section>
        @endif

        @if($seeker->resume && isset($seeker->resume->education) && is_array($seeker->resume->education) && count($seeker->resume->education) > 0)
        <section class="section">
            <h2 class="section-title">Education</h2>
            @foreach($seeker->resume->education as $edu)
            <div class="education-item">
                <div class="item-header">
                    <table width="100%" style="border-collapse: collapse;">
                        <tr>
                            <td style="font-weight: bold;">{{ $edu['degree'] ?? '' }}</td>
                            <td style="text-align: right; font-weight: bold;">
                                {{ $edu['start_date'] ?? '' }} - {{ $edu['end_date'] ?? 'Present' }}
                            </td>
                        </tr>
                    </table>
                </div>
                @if(isset($edu['institution']) || isset($edu['location']))
                <div class="item-subheader">
                    {{ $edu['institution'] ?? '' }}@if(isset($edu['location'])) • {{ $edu['location'] }}@endif
                </div>
                @endif
            </div>
            @endforeach
        </section>
        @endif

        @if($seeker->resume && isset($seeker->resume->skills) && is_array($seeker->resume->skills) && count($seeker->resume->skills) > 0)
        <section class="section">
            <h2 class="section-title">Skills</h2>
            <table class="skills-table">
                <tr>
                    @foreach($seeker->resume->skills as $skill)
                    <td><span class="skill-chip">{{ $skill }}</span></td>
                    @endforeach
                </tr>
            </table>
        </section>
        @endif
    </div>
</body>
</html>
