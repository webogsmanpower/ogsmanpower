<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Domestic Worker Resume - {{ $resumeData['first_name'] ?? '' }} {{ $resumeData['last_name'] ?? '' }}</title>
    @php
        // Helper function to resolve image path to absolute file path for PDF rendering
        if (!function_exists('resolveImagePathForPdf')) {
            function resolveImagePathForPdf($rawPath) {
                if (!$rawPath) return null;
                
                if (str_starts_with($rawPath, '/') && !str_starts_with($rawPath, '/storage') && file_exists($rawPath)) {
                    return $rawPath;
                }
                
                if (str_starts_with($rawPath, 'http')) {
                    $parsed = parse_url($rawPath);
                    $urlPath = $parsed['path'] ?? '';
                    $urlPath = preg_replace('/^\/storage\//', '', $urlPath);
                    $fullPath = storage_path('app/public/' . $urlPath);
                    if (file_exists($fullPath)) return $fullPath;
                }
                
                if (str_starts_with($rawPath, '/storage/')) {
                    $relativePath = substr($rawPath, 9);
                    $fullPath = storage_path('app/public/' . $relativePath);
                    if (file_exists($fullPath)) return $fullPath;
                }
                
                $cleanPath = preg_replace('/^storage\//', '', $rawPath);
                $fullPath = storage_path('app/public/' . $cleanPath);
                if (file_exists($fullPath)) return $fullPath;
                
                return null;
            }
        }

        // Resolve profile photo
        $profilePhoto = null;
        if (!empty($resumeData['profile_image_file']) && file_exists($resumeData['profile_image_file'])) {
            $profilePhoto = $resumeData['profile_image_file'];
        } elseif (!empty($resumeData['profile_image_path'])) {
            $profilePhoto = resolveImagePathForPdf($resumeData['profile_image_path']);
        } elseif (!empty($resumeData['basic_information']['profile_photo'])) {
            $profilePhoto = resolveImagePathForPdf($resumeData['basic_information']['profile_photo']);
        }

        // Resolve full body photo (important for domestic worker)
        $fullBodyPhoto = null;
        if (!empty($resumeData['full_body_image_file']) && file_exists($resumeData['full_body_image_file'])) {
            $fullBodyPhoto = $resumeData['full_body_image_file'];
        } elseif (!empty($resumeData['full_body_image_path'])) {
            $fullBodyPhoto = resolveImagePathForPdf($resumeData['full_body_image_path']);
        }
    @endphp
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .headline {
            font-size: 14px;
            font-style: italic;
            color: #666;
            margin-bottom: 10px;
        }
        
        .contact-info {
            font-size: 11px;
            margin-bottom: 5px;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .skill-category {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
        }
        
        .skill-category-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .skill-item {
            display: flex;
            align-items: center;
            margin-bottom: 3px;
        }
        
        .skill-checkbox {
            width: 12px;
            height: 12px;
            border: 1px solid #666;
            margin-right: 8px;
            display: inline-block;
        }
        
        .skill-checkbox.checked {
            background: #4CAF50;
            border-color: #4CAF50;
        }
        
        .work-item, .education-item {
            margin-bottom: 15px;
        }
        
        .job-title {
            font-weight: bold;
            font-size: 13px;
        }
        
        .company {
            font-style: italic;
            color: #666;
        }
        
        .date {
            float: right;
            font-size: 11px;
            color: #666;
        }
        
        .personal-qualities {
            background: #e8f5e8;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .qualities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .quality-tag {
            background: #4CAF50;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
        }
        
        .physical-info {
            background: #f0f8ff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .info-row {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        @if($profilePhoto || $fullBodyPhoto)
        <div style="float: right; margin-left: 20px; display: flex; gap: 10px;">
            @if($profilePhoto)
            <div>
                <img src="{{ $profilePhoto }}" style="width: 100px; height: 120px; object-fit: cover; border: 1px solid #ccc;" alt="Profile Photo">
                <div style="text-align: center; font-size: 9px; color: #666;">Profile</div>
            </div>
            @endif
            @if($fullBodyPhoto)
            <div>
                <img src="{{ $fullBodyPhoto }}" style="width: 80px; height: 120px; object-fit: cover; border: 1px solid #ccc;" alt="Full Body Photo">
                <div style="text-align: center; font-size: 9px; color: #666;">Full Body</div>
            </div>
            @endif
        </div>
        @endif
        <div class="name">{{ $resumeData['first_name'] ?? '' }} {{ $resumeData['last_name'] ?? '' }}</div>
        <div class="headline">Domestic Worker</div>
        
        <div class="contact-info">
            @if(!empty($resumeData['basic_information']['email']))
                {{ $resumeData['basic_information']['email'] }} 
            @endif
            @if(!empty($resumeData['basic_information']['phone']))
                • {{ $resumeData['basic_information']['phone'] }}
            @endif
            @if(!empty($resumeData['current_location']))
                • {{ $resumeData['current_location'] }}
            @endif
        </div>
        <div style="clear: both;"></div>
    </div>

    <!-- Skills Assessment -->
    <div class="section">
        <div class="section-title">Skills Assessment</div>
        <div class="skills-grid">
            <div class="skill-category">
                <div class="skill-category-title">Household Skills</div>
                <div class="skill-item">
                    <span class="skill-checkbox {{ $resumeData['skill_washing'] ? 'checked' : '' }}"></span>
                    Laundry & Washing
                </div>
                <div class="skill-item">
                    <span class="skill-checkbox {{ $resumeData['skill_cooking'] ? 'checked' : '' }}"></span>
                    Cooking
                </div>
                <div class="skill-item">
                    <span class="skill-checkbox {{ $resumeData['skill_cleaning'] ? 'checked' : '' }}"></span>
                    House Cleaning
                </div>
                <div class="skill-item">
                    <span class="skill-checkbox {{ $resumeData['skill_babysitting'] ? 'checked' : '' }}"></span>
                    Babysitting
                </div>
            </div>
            
            <div class="skill-category">
                <div class="skill-category-title">Personal Information</div>
                @if(!empty($resumeData['number_of_children']))
                    <div class="info-row">
                        <span class="info-label">Children:</span>
                        {{ $resumeData['number_of_children'] }}
                    </div>
                @endif
                @if(!empty($resumeData['date_of_birth']))
                    <div class="info-row">
                        <span class="info-label">Age:</span>
                        {{ \Carbon\Carbon::parse($resumeData['date_of_birth'])->age }} years
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Physical Information -->
    @if(!empty($resumeData['height']) || !empty($resumeData['weight']))
    <div class="section">
        <div class="section-title">Physical Information</div>
        <div class="physical-info">
            @if(!empty($resumeData['height']))
                <div class="info-row">
                    <span class="info-label">Height:</span>
                    {{ $resumeData['height'] }} cm
                </div>
            @endif
            @if(!empty($resumeData['weight']))
                <div class="info-row">
                    <span class="info-label">Weight:</span>
                    {{ $resumeData['weight'] }} kg
                </div>
            @endif
            @if(!empty($resumeData['chest_measurement']))
                <div class="info-row">
                    <span class="info-label">Chest:</span>
                    {{ $resumeData['chest_measurement'] }} cm
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Personal Qualities -->
    @if(!empty($resumeData['personal_qualities']) && is_array($resumeData['personal_qualities']))
    <div class="section">
        <div class="section-title">Personal Qualities</div>
        <div class="personal-qualities">
            <div class="qualities-list">
                @foreach($resumeData['personal_qualities'] as $quality)
                    <div class="quality-tag">{{ $quality }}</div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Professional Summary -->
    @if(!empty($resumeData['professional_summary']))
    <div class="section">
        <div class="section-title">Professional Summary</div>
        <p>{{ is_array($resumeData['professional_summary']) ? (isset($resumeData['professional_summary']['summary']) ? $resumeData['professional_summary']['summary'] : (isset($resumeData['professional_summary']['text']) ? $resumeData['professional_summary']['text'] : collect($resumeData['professional_summary'])->flatten()->implode(' '))) : $resumeData['professional_summary'] }}</p>
    </div>
    @endif

    <!-- Work Experience -->
    @if(!empty($resumeData['work_experience']) && is_array($resumeData['work_experience']))
    <div class="section">
        <div class="section-title">Work Experience</div>
        @foreach($resumeData['work_experience'] as $work)
            <div class="work-item">
                <div class="job-title">{{ $work['job_title'] ?? '' }}</div>
                <div class="company">
                    {{ $work['company'] ?? '' }}
                    @if(!empty($work['location']))
                        • {{ $work['location'] }}
                    @endif
                    <span class="date">
                        @if(!empty($work['start_date']))
                            {{ date('M Y', strtotime($work['start_date'])) }}
                        @endif
                        @if(!empty($work['end_date']))
                            - {{ date('M Y', strtotime($work['end_date'])) }}
                        @else
                            - Present
                        @endif
                    </span>
                </div>
                @if(!empty($work['description']))
                    <p>{{ $work['description'] }}</p>
                @endif
            </div>
        @endforeach
    </div>
    @endif

    <!-- Education -->
    @if(!empty($resumeData['education']) && is_array($resumeData['education']))
    <div class="section">
        <div class="section-title">Education</div>
        @foreach($resumeData['education'] as $edu)
            <div class="education-item">
                <div class="job-title">{{ $edu['degree'] ?? '' }}</div>
                <div class="company">
                    {{ $edu['institution'] ?? '' }}
                    @if(!empty($edu['location']))
                        • {{ $edu['location'] }}
                    @endif
                    <span class="date">
                        @if(!empty($edu['start_date'])){{ date('M Y', strtotime($edu['start_date'])) }}@endif
                        @if(!empty($edu['end_date'])) - {{ date('M Y', strtotime($edu['end_date'])) }}@endif
                    </span>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Skills -->
    @if(!empty($resumeData['skills']) && is_array($resumeData['skills']))
    <div class="section">
        <div class="section-title">Additional Skills</div>
        <div class="qualities-list">
            @foreach($resumeData['skills'] as $skill)
                @if(!empty($skill['name']))
                    <div class="quality-tag">{{ $skill['name'] }}</div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <!-- Languages -->
    @if(!empty($resumeData['languages']) && is_array($resumeData['languages']))
    <div class="section">
        <div class="section-title">Languages</div>
        @foreach($resumeData['languages'] as $lang)
            <div>{{ $lang['name'] ?? '' }} - {{ $lang['proficiency'] ?? '' }}</div>
        @endforeach
    </div>
    @endif

    <!-- Availability Notes -->
    @if(!empty($resumeData['availability_notes']))
    <div class="section">
        <div class="section-title">Availability</div>
        <p>{{ $resumeData['availability_notes'] }}</p>
    </div>
    @endif
</body>
</html>
