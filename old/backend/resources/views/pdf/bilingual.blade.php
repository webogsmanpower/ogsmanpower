<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilingual Resume</title>
    <style>
        @page { margin: 20px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.5;
        }
        .layout {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .column {
            display: table-cell;
            width: 50%;
            padding: 0 12px;
        }
        .column-rtl {
            direction: rtl;
            text-align: right;
            font-family: 'DejaVu Sans', 'Cairo', sans-serif;
            background: #f8fafc;
        }
        .header {
            text-align: center;
            padding: 12px 10px;
            border-bottom: 2px solid #bfdbfe;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .section-title {
            margin-top: 18px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #1d4ed8;
        }
        .section-title-rtl {
            color: #1d4ed8;
        }
        .entry {
            margin-top: 8px;
        }
        .entry .role {
            font-weight: bold;
        }
        .entry .meta {
            color: #6b7280;
            font-size: 10px;
        }
        ul { padding-left: 16px; margin: 6px 0; }
        ul li { margin-bottom: 4px; }
    </style>
</head>
<body>
@php
    $resume = $seeker->resume;
    $targetLocale = $locale ?? request()->query('locale', 'ar');
    $translations = $resume?->translations[$targetLocale] ?? [];
    $basic = $resume->basic_information ?? [];

    $summarySource = $resume->professional_summary ?? [];
    if (is_array($summarySource)) {
        $summaryEn = $summarySource['professional_summary']
            ?? $summarySource['career_objective']
            ?? $seeker->bio
            ?? '';
    } else {
        $summaryEn = $summarySource
            ?? $seeker->bio
            ?? '';
    }

    $translatedKey = 'professional_summary_' . $targetLocale;
    $summaryAr = $resumeData[$translatedKey]
        ?? $resumeData['professional_summary_ar']
        ?? $translations['professional_summary']
        ?? $translations['professional_summary_ar']
        ?? '';

    if (is_array($summaryAr)) {
        $summaryAr = $summaryAr['professional_summary_ar']
            ?? $summaryAr['career_objective_ar']
            ?? collect($summaryAr)->flatten()->implode(' ');
    }
@endphp
<div class="layout">
    <div class="column">
        <div class="header">
            <h1>{{ trim(($seeker->first_name ?? '') . ' ' . ($seeker->last_name ?? '')) }}</h1>
            <div>{{ $basic['email'] ?? $seeker->user->email ?? '' }} | {{ $basic['phone'] ?? $seeker->user->mobile ?? '' }}</div>
            <div>{{ $basic['city'] ?? $seeker->current_location ?? '' }}</div>
        </div>

        @if($summaryEn)
            <div class="section">
                <div class="section-title">Professional Summary</div>
                <div>{{ $summaryEn }}</div>
            </div>
        @endif

        @php $experiences = $resume->work_experience ?? []; @endphp
        @if(!empty($experiences))
            <div class="section">
                <div class="section-title">Experience</div>
                @foreach ($experiences as $index => $experience)
                    <div class="entry">
                        <div class="role">{{ $experience['title'] ?? $experience['role_title'] ?? '' }}</div>
                        <div class="meta">
                            {{ $experience['company'] ?? $experience['company_name'] ?? '' }}
                            @php
                                $start = $experience['start_date'] ? \Carbon\Carbon::parse($experience['start_date'])->format('M Y') : null;
                                $end = ($experience['current'] ?? false)
                                    ? 'Present'
                                    : ($experience['end_date'] ? \Carbon\Carbon::parse($experience['end_date'])->format('M Y') : null);
                            @endphp
                            @if($start || $end)
                                | {{ trim(($start ?? '') . ' - ' . ($end ?? '')) }}
                            @endif
                        </div>
                        @if (!empty($experience['description']))
                            <div>{{ $experience['description'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @php $education = $resume->education ?? []; @endphp
        @if(!empty($education))
            <div class="section">
                <div class="section-title">Education</div>
                @foreach ($education as $entry)
                    <div class="entry">
                        <div class="role">{{ $entry['degree'] ?? $entry['degree_title'] ?? '' }}</div>
                        <div class="meta">{{ $entry['institution'] ?? $entry['institution_name'] ?? '' }}
                            @php
                                $start = $entry['start_date'] ? \Carbon\Carbon::parse($entry['start_date'])->format('Y') : null;
                                $end = $entry['end_date'] ? \Carbon\Carbon::parse($entry['end_date'])->format('Y') : null;
                            @endphp
                            @if($start || $end)
                                | {{ trim(($start ?? '') . ' - ' . ($end ?? '')) }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @php
            $skills = $resume->skills ?? $seeker->skills ?? [];
            $skillsList = [];
            if (is_array($skills) && isset($skills[0]) && is_array($skills[0])) {
                $skillsList = array_map(fn($skill) => $skill['skill'] ?? $skill['skill_name'] ?? null, $skills);
            } elseif (is_array($skills)) {
                $skillsList = $skills;
            } elseif (is_string($skills)) {
                $skillsList = array_map('trim', explode(',', $skills));
            }
            $skillsList = array_filter($skillsList);
        @endphp
        @if(!empty($skillsList))
            <div class="section">
                <div class="section-title">Skills</div>
                <ul>
                    @foreach($skillsList as $skill)
                        <li>{{ $skill }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="column column-rtl">
        <div class="header">
            <h1>{{ trim(($seeker->first_name ?? '') . ' ' . ($seeker->last_name ?? '')) }}</h1>
            <div>{{ $translations['basic_information']['job_title'] ?? $resumeData['headline_' . $targetLocale] ?? $resumeData['headline_ar'] ?? $resumeData['headline'] ?? $resume->profession ?? '' }}</div>
        </div>

        @if($summaryAr)
            <div class="section">
                <div class="section-title section-title-rtl">الملخص المهني</div>
                <div>{{ $summaryAr }}</div>
            </div>
        @elseif($summaryEn)
            <div class="section">
                <div class="section-title section-title-rtl">الملخص المهني</div>
                <div>{{ $summaryEn }}</div>
            </div>
        @endif

        @if(!empty($translations['work_experience']))
            <div class="section">
                <div class="section-title section-title-rtl">الخبرة العملية</div>
                @foreach ($translations['work_experience'] as $index => $translatedExp)
                    @php $original = $experiences[$index] ?? []; @endphp
                    <div class="entry">
                        <div class="role">{{ $translatedExp['title'] ?? ($original['title'] ?? $original['role_title'] ?? '') }}</div>
                        <div class="meta">
                            {{ $translatedExp['company'] ?? $original['company'] ?? $original['company_name'] ?? '' }}
                            @php
                                $start = $original['start_date'] ? \Carbon\Carbon::parse($original['start_date'])->format('M Y') : null;
                                $end = ($original['current'] ?? false)
                                    ? __('Present')
                                    : ($original['end_date'] ? \Carbon\Carbon::parse($original['end_date'])->format('M Y') : null);
                            @endphp
                            @if($start || $end)
                                | {{ trim(($start ?? '') . ' - ' . ($end ?? '')) }}
                            @endif
                        </div>
                        @if(!empty($translatedExp['description']))
                            <div>{{ $translatedExp['description'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @elseif(!empty($experiences))
            <div class="section">
                <div class="section-title section-title-rtl">الخبرة العملية</div>
                @foreach ($experiences as $index => $experience)
                    <div class="entry">
                        <div class="role">{{ $experience['title'] ?? $experience['role_title'] ?? '' }}</div>
                        <div class="meta">
                            {{ $experience['company'] ?? $experience['company_name'] ?? '' }}
                            @php
                                $start = $experience['start_date'] ? \Carbon\Carbon::parse($experience['start_date'])->format('M Y') : null;
                                $end = ($experience['current'] ?? false)
                                    ? __('Present')
                                    : ($experience['end_date'] ? \Carbon\Carbon::parse($experience['end_date'])->format('M Y') : null);
                            @endphp
                            @if($start || $end)
                                | {{ trim(($start ?? '') . ' - ' . ($end ?? '')) }}
                            @endif
                        </div>
                        @if (!empty($experience['description']))
                            <div>{{ $experience['description'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @php $education = $resume->education ?? []; @endphp
        @if(!empty($translations['education']))
            <div class="section">
                <div class="section-title section-title-rtl">التعليم</div>
                @foreach ($translations['education'] as $index => $translatedEdu)
                    @php $original = $education[$index] ?? []; @endphp
                    <div class="entry">
                        <div class="role">{{ $translatedEdu['degree'] ?? ($original['degree'] ?? $original['degree_title'] ?? '') }}</div>
                        <div class="meta">
                            {{ $translatedEdu['institution'] ?? $original['institution'] ?? $original['institution_name'] ?? '' }}
                            @php
                                $start = $original['start_date'] ? \Carbon\Carbon::parse($original['start_date'])->format('Y') : null;
                                $end = $original['end_date'] ? \Carbon\Carbon::parse($original['end_date'])->format('Y') : null;
                            @endphp
                            @if($start || $end)
                                | {{ trim(($start ?? '') . ' - ' . ($end ?? '')) }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif(!empty($education))
            <div class="section">
                <div class="section-title section-title-rtl">التعليم</div>
                @foreach ($education as $entry)
                    <div class="entry">
                        <div class="role">{{ $entry['degree'] ?? $entry['degree_title'] ?? '' }}</div>
                        <div class="meta">{{ $entry['institution'] ?? $entry['institution_name'] ?? '' }}
                            @php
                                $start = $entry['start_date'] ? \Carbon\Carbon::parse($entry['start_date'])->format('Y') : null;
                                $end = $entry['end_date'] ? \Carbon\Carbon::parse($entry['end_date'])->format('Y') : null;
                            @endphp
                            @if($start || $end)
                                | {{ trim(($start ?? '') . ' - ' . ($end ?? '')) }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @php
            $translatedSkills = $translations['skills'] ?? [];
            $skillsAr = [];
            foreach ($translatedSkills as $index => $translatedSkill) {
                if (is_string($translatedSkill)) {
                    $skillsAr[] = $translatedSkill;
                    continue;
                }
                if (is_array($translatedSkill)) {
                    $skillsAr[] = $translatedSkill['skill'] ?? $translatedSkill['skill_name'] ?? null;
                }
            }
            $skillsAr = array_filter($skillsAr);
        @endphp
        @if(!empty($skillsAr))
            <div class="section">
                <div class="section-title section-title-rtl">المهارات</div>
                <ul>
                    @foreach($skillsAr as $skill)
                        <li>{{ $skill }}</li>
                    @endforeach
                </ul>
            </div>
        @elseif(!empty($skillsList))
            <div class="section">
                <div class="section-title section-title-rtl">المهارات</div>
                <ul>
                    @foreach($skillsList as $skill)
                        <li>{{ $skill }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
</body>
</html>
