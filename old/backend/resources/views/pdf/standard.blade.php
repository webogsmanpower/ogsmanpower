<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $formatValue = function ($value, $fallback = '') {
            if (is_null($value) || $value === '') {
                return $fallback;
            }
            if (is_array($value)) {
                $flattened = [];
                array_walk_recursive($value, function ($item) use (&$flattened) {
                    if (!is_null($item) && $item !== '') {
                        $flattened[] = (string)$item;
                    }
                });
                return count($flattened) ? implode(', ', $flattened) : $fallback;
            }
            return (string)$value;
        };
    @endphp
    <title>Resume - {{ $formatValue($seeker->first_name ?? 'Candidate') }} {{ $formatValue($seeker->last_name ?? '') }}</title>
    <style>
        @page {
            margin: 0.5in;
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        .resume-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Header Styles */
        .header {
            margin-bottom: 24px;
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 0 0 8px 0;
            text-transform: uppercase;
        }
        
        .contact-info {
            font-size: 11px;
            color: #4b5563;
            margin: 0;
        }
        
        .contact-info span {
            margin-right: 12px;
        }
        
        .contact-info a {
            color: #4b5563;
            text-decoration: none;
        }
        
        .contact-info a:hover {
            text-decoration: underline;
        }
        
        /* Professional Title */
        .professional-title {
            margin-bottom: 24px;
        }
        
        .professional-title h2 {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 0 0 8px 0;
            text-transform: uppercase;
        }
        
        .professional-title p {
            font-size: 11px;
            color: #374151;
            line-height: 1.6;
            margin: 0;
        }
        
        /* Section Styles */
        .section {
            margin-bottom: 24px;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            background-color: #d1d5db;
            padding: 8px 12px;
            margin: 0 0 16px 0;
        }
        
        /* Experience & Education Items */
        .timeline-item {
            margin-bottom: 16px;
        }
        
        .item-header {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        
        .item-title {
            display: table-cell;
            font-weight: bold;
            font-size: 11px;
        }
        
        .item-date {
            display: table-cell;
            text-align: right;
            font-size: 11px;
            color: #4b5563;
            white-space: nowrap;
        }
        
        .item-description {
            font-size: 11px;
            color: #374151;
            margin: 4px 0 0 16px;
        }
        
        .item-description ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .item-description li {
            margin-bottom: 2px;
        }
        
        .item-description li:before {
            content: "• ";
            margin-right: 4px;
        }
        
        /* Skills Grid */
        .skills-grid {
            display: table;
            width: 100%;
        }
        
        .skills-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 24px;
        }
        
        .skills-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .skills-list li {
            margin-bottom: 4px;
            font-size: 11px;
        }
        
        .skills-list li:before {
            content: "• ";
            margin-right: 4px;
        }
        
        .skills-list a {
            color: #2563eb;
            text-decoration: none;
        }
        
        .skills-list a:hover {
            text-decoration: underline;
        }
        
        /* Interests & Awards */
        .interests-grid {
            display: table;
            width: 100%;
        }
        
        .interests-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 24px;
        }
        
        .interests-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .interests-list li {
            margin-bottom: 4px;
            font-size: 11px;
        }
        
        .interests-list li:before {
            content: "• ";
            margin-right: 4px;
        }
        
        .awards-list {
            list-style: none;
            margin: 0;
            padding: 0 0 0 16px;
        }
        
        .awards-list li {
            margin-bottom: 6px;
            font-size: 11px;
        }
        
        .awards-list li:before {
            content: "• ";
            margin-left: -16px;
            margin-right: 4px;
        }
        
        .awards-list a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="resume-container">
        {{-- Header --}}
        <header class="header">
            <h1>{{ strtoupper(trim(($formatValue($resumeData['first_name'] ?? '')) . ' ' . ($formatValue($resumeData['last_name'] ?? '')))) ?: strtoupper($formatValue($seeker->user->name ?? 'CANDIDATE')) }}</h1>
            <div class="contact-info">
                @php
                    $basicInfo = $resumeData['basic_information'] ?? [];
                    $email = $basicInfo['email'] ?? $seeker->user->email ?? null;
                    $phone = $basicInfo['phone'] ?? $seeker->user->mobile ?? null;
                    $location = $basicInfo['city'] ?? $resumeData['current_location'] ?? null;
                    $website = $basicInfo['website'] ?? null;
                @endphp
                
                @if($location)
                    <span>{{ $formatValue($location) }}</span>
                    <span>|</span>
                @endif
                
                @if($email)
                    <span><a href="mailto:{{ $formatValue($email) }}">{{ $formatValue($email) }}</a></span>
                    @if($website || $phone)
                        <span>|</span>
                    @endif
                @endif
                
                @if($website)
                    <span><a href="{{ $formatValue($website) }}">{{ $formatValue($website) }}</a></span>
                @endif
            </div>
        </header>

        {{-- Professional Title and Summary --}}
        @if($resumeData['job_title'] || (isset($resumeData['professional_summary'])))
        <section class="professional-title">
            @if($resumeData['job_title'])
                <h2>{{ strtoupper($formatValue($resumeData['job_title'])) }}</h2>
            @endif
            
            @if(isset($resumeData['professional_summary']))
                @php
                    $summary = $resumeData['professional_summary'];
                    if (is_array($summary)) {
                        $summaryText = $summary['career_objective'] ?? $summary['professional_summary'] ?? '';
                    } else {
                        $summaryText = $summary;
                    }
                @endphp
                <p>{{ $formatValue($summaryText) }}</p>
            @endif
        </section>
        @endif

        {{-- Work Experience --}}
        @if(isset($resumeData['work_experience']) && is_array($resumeData['work_experience']) && count($resumeData['work_experience']) > 0)
        <section class="section">
            <h2 class="section-title">Work Experience</h2>
            
            @foreach($resumeData['work_experience'] as $exp)
            <div class="timeline-item">
                <div class="item-header">
                    <span class="item-title">
                        {{ $formatValue($exp['role_title'] ?? 'Position') }}
                        @if(isset($exp['company_name']))
                            | {{ $formatValue($exp['company_name']) }}
                        @endif
                    </span>
                    <span class="item-date">
                        {{ $formatValue($exp['start_date'] ?? '') }}
                        @if(isset($exp['end_date']) && $exp['end_date'])
                            - {{ $formatValue($exp['end_date']) }}
                        @else
                            - present
                        @endif
                    </span>
                </div>
                
                @if(isset($exp['description']) && $exp['description'])
                <div class="item-description">
                    @php
                        // Split description by line breaks or bullet points
                        $lines = preg_split('/\r\n|\r|\n/', $exp['description']);
                        $hasMultipleLines = count($lines) > 1;
                    @endphp
                    
                    @if($hasMultipleLines)
                        <ul>
                            @foreach($lines as $line)
                                @if(trim($line))
                                    <li>{{ trim($line, "• \t\n\r\0\x0B") }}</li>
                                @endif
                            @endforeach
                        </ul>
                    @else
                        <p style="margin: 0;">{{ $formatValue($exp['description']) }}</p>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </section>
        @endif

        {{-- Skills --}}
        @if(isset($resumeData['skills']) && is_array($resumeData['skills']) && count($resumeData['skills']) > 0)
        <section class="section">
            <h2 class="section-title">Skills</h2>
            
            <div class="skills-grid">
                @php
                    // Handle nested skills structure
                    $allSkills = [];
                    if (isset($resumeData['skills']['skills']) && is_array($resumeData['skills']['skills'])) {
                        $allSkills = array_merge($allSkills, $resumeData['skills']['skills']);
                    }
                    if (isset($resumeData['skills']['must_have']) && is_array($resumeData['skills']['must_have'])) {
                        $allSkills = array_merge($allSkills, $resumeData['skills']['must_have']);
                    }
                    
                    $skills = $allSkills;
                    $halfCount = ceil(count($skills) / 2);
                    $leftColumn = array_slice($skills, 0, $halfCount);
                    $rightColumn = array_slice($skills, $halfCount);
                @endphp
                
                <div class="skills-column">
                    <ul class="skills-list">
                        @foreach($leftColumn as $skill)
                            <li>{{ $formatValue(is_array($skill) ? ($skill['name'] ?? $skill) : $skill) }}</li>
                        @endforeach
                    </ul>
                </div>
                
                <div class="skills-column">
                    <ul class="skills-list">
                        @foreach($rightColumn as $skill)
                            <li>{{ $formatValue(is_array($skill) ? ($skill['name'] ?? $skill) : $skill) }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>
        @endif

        {{-- Education --}}
        @if(isset($resumeData['education']) && is_array($resumeData['education']) && count($resumeData['education']) > 0)
        <section class="section">
            <h2 class="section-title">Education</h2>
            
            @foreach($resumeData['education'] as $edu)
            <div class="timeline-item">
                <div class="item-header">
                    <span class="item-title">
                        {{ $formatValue($edu['degree_title'] ?? 'Degree') }}
                        @if(isset($edu['institution_name']))
                            | {{ $formatValue($edu['institution_name']) }}
                        @endif
                    </span>
                    <span class="item-date">
                        {{ $formatValue($edu['graduation_year'] ?? '') }}
                    </span>
                </div>
                
                @if(isset($edu['description']) && $edu['description'])
                <div class="item-description">
                    @php
                        $lines = preg_split('/\r\n|\r|\n/', $edu['description']);
                        $hasMultipleLines = count($lines) > 1;
                    @endphp
                    
                    @if($hasMultipleLines)
                        <ul>
                            @foreach($lines as $line)
                                @if(trim($line))
                                    <li>{{ trim($line, "• \t\n\r\0\x0B") }}</li>
                                @endif
                            @endforeach
                        </ul>
                    @else
                        <p style="margin: 0;">{{ $formatValue($edu['description']) }}</p>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </section>
        @endif

        {{-- Languages --}}
        @if(isset($resumeData['languages']) && is_array($resumeData['languages']) && count($resumeData['languages']) > 0)
        <section class="section">
            <h2 class="section-title">Languages</h2>
            
            <div class="skills-grid">
                @php
                    $languages = $resumeData['languages'];
                    $halfCount = ceil(count($languages) / 2);
                    $leftColumn = array_slice($languages, 0, $halfCount);
                    $rightColumn = array_slice($languages, $halfCount);
                @endphp
                
                <div class="skills-column">
                    <ul class="skills-list">
                        @foreach($leftColumn as $lang)
                            <li>
                                @if(is_array($lang))
                                    {{ $formatValue($lang['language_name'] ?? $lang['name'] ?? 'Language') }}
                                    @if(isset($lang['proficiency_level']))
                                        - {{ $formatValue($lang['proficiency_level']) }}
                                    @endif
                                @else
                                    {{ $formatValue($lang) }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
                
                <div class="skills-column">
                    <ul class="skills-list">
                        @foreach($rightColumn as $lang)
                            <li>
                                @if(is_array($lang))
                                    {{ $formatValue($lang['language_name'] ?? $lang['name'] ?? 'Language') }}
                                    @if(isset($lang['proficiency_level']))
                                        - {{ $formatValue($lang['proficiency_level']) }}
                                    @endif
                                @else
                                    {{ $formatValue($lang) }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>
        @endif

        {{-- Interests --}}
        @php
            $interests = null;
            if (isset($resumeData['extra']['interests'])) {
                $interests = $resumeData['extra']['interests'];
            } elseif (isset($resumeData['interests'])) {
                $interests = $resumeData['interests'];
            }
        @endphp
        
        @if($interests && ((is_array($interests) && count($interests) > 0) || (is_string($interests) && trim($interests))))
        <section class="section">
            <h2 class="section-title">Interests</h2>
            
            <div class="interests-grid">
                @php
                    // Handle both string and array formats
                    if (is_string($interests)) {
                        $interestsList = array_map('trim', explode(',', $interests));
                    } else {
                        $interestsList = $interests;
                    }
                    
                    $halfCount = ceil(count($interestsList) / 2);
                    $leftColumn = array_slice($interestsList, 0, $halfCount);
                    $rightColumn = array_slice($interestsList, $halfCount);
                @endphp
                
                <div class="interests-column">
                    <ul class="interests-list">
                        @foreach($leftColumn as $interest)
                            <li>{{ $formatValue(is_array($interest) ? ($interest['name'] ?? $interest) : $interest) }}</li>
                        @endforeach
                    </ul>
                </div>
                
                <div class="interests-column">
                    <ul class="interests-list">
                        @foreach($rightColumn as $interest)
                            <li>{{ $formatValue(is_array($interest) ? ($interest['name'] ?? $interest) : $interest) }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>
        @endif

        {{-- Awards & Certifications --}}
        @php
            $awards = null;
            $certifications = $resumeData['certifications'] ?? null;
            
            // Try multiple possible locations for awards data
            if (isset($resumeData['extra']['awards'])) {
                $awards = $resumeData['extra']['awards'];
            } elseif (isset($resumeData['awards'])) {
                $awards = $resumeData['awards'];
            } elseif (isset($basicInfo['awards'])) {
                $awards = $basicInfo['awards'];
            }
            
            // Combine awards and certifications
            $allAwards = [];
            if ($awards && is_array($awards)) {
                $allAwards = array_merge($allAwards, $awards);
            }
            if ($certifications && is_array($certifications)) {
                foreach ($certifications as $cert) {
                    if (is_array($cert)) {
                        $certText = $cert['title'] ?? $cert['name'] ?? $cert['certification_name'] ?? $cert;
                        if (isset($cert['date'])) {
                            $certText .= ' (' . $cert['date'] . ')';
                        }
                        $allAwards[] = $certText;
                    } else {
                        $allAwards[] = $cert;
                    }
                }
            }
            
            // If no awards/certifications found, check if we should show the section
            $showAwardsSection = count($allAwards) > 0;
        @endphp
        
        @if($showAwardsSection)
        <section class="section">
            <h2 class="section-title">Awards & Certifications</h2>
            
            <ul class="awards-list">
                @foreach($allAwards as $award)
                    <li>{{ $formatValue(is_array($award) ? ($award['title'] ?? $award['name'] ?? $award) : $award) }}</li>
                @endforeach
            </ul>
        </section>
        @endif
    </div>
</body>
</html>
