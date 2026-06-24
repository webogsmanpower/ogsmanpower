<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resume - {{ $resumeData['first_name'] ?? '' }} {{ $resumeData['last_name'] ?? '' }}</title>
    @php
        // Helper function to resolve image path to absolute file path for PDF rendering
        if (!function_exists('resolveImagePathForPdf')) {
            function resolveImagePathForPdf($rawPath) {
                if (!$rawPath) return null;
                
                // Already an absolute file path
                if (str_starts_with($rawPath, '/') && !str_starts_with($rawPath, '/storage') && file_exists($rawPath)) {
                    return $rawPath;
                }
                
                // Full URL - extract path and convert to file path
                if (str_starts_with($rawPath, 'http')) {
                    $parsed = parse_url($rawPath);
                    $urlPath = $parsed['path'] ?? '';
                    $urlPath = preg_replace('/^\/storage\//', '', $urlPath);
                    $fullPath = storage_path('app/public/' . $urlPath);
                    if (file_exists($fullPath)) {
                        return $fullPath;
                    }
                }
                
                // Storage relative path (e.g. /storage/uploads/...)
                if (str_starts_with($rawPath, '/storage/')) {
                    $relativePath = substr($rawPath, 9);
                    $fullPath = storage_path('app/public/' . $relativePath);
                    if (file_exists($fullPath)) {
                        return $fullPath;
                    }
                }
                
                // Raw storage path (e.g. uploads/... or resume_uploads/...)
                $cleanPath = preg_replace('/^storage\//', '', $rawPath);
                $fullPath = storage_path('app/public/' . $cleanPath);
                if (file_exists($fullPath)) {
                    return $fullPath;
                }
                
                return null;
            }
        }

        // Resolve profile photo for PDF
        $profilePhoto = null;
        if (!empty($resumeData['profile_image_file']) && file_exists($resumeData['profile_image_file'])) {
            $profilePhoto = $resumeData['profile_image_file'];
        } elseif (!empty($resumeData['profile_image_path'])) {
            $profilePhoto = resolveImagePathForPdf($resumeData['profile_image_path']);
        } elseif (!empty($resumeData['basic_information']['profile_photo'])) {
            $profilePhoto = resolveImagePathForPdf($resumeData['basic_information']['profile_photo']);
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
        
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .skill-tag {
            background: #f0f0f0;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .two-column {
            display: flex;
            gap: 20px;
        }
        
        .column {
            flex: 1;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        @if($profilePhoto)
        <div style="float: right; margin-left: 20px;">
            <img src="{{ $profilePhoto }}" style="width: 100px; height: 120px; object-fit: cover; border: 1px solid #ccc;" alt="Profile Photo">
        </div>
        @endif
        <div class="name">{{ $resumeData['first_name'] ?? '' }} {{ $resumeData['last_name'] ?? '' }}</div>
        @if(!empty($resumeData['headline']))
            <div class="headline">{{ $resumeData['headline'] }}</div>
        @endif
        
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

    <!-- Professional Summary -->
    @if(!empty($resumeData['professional_summary']))
    <div class="section">
        <div class="section-title">Professional Summary</div>
        @if(is_array($resumeData['professional_summary']))
            @if(!empty($resumeData['professional_summary']['career_objective']))
                <p>{{ $resumeData['professional_summary']['career_objective'] }}</p>
            @endif
        @else
            <p>{{ $resumeData['professional_summary'] }}</p>
        @endif
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
        <div class="section-title">Skills</div>
        <div class="skills-list">
            @foreach($resumeData['skills'] as $skill)
                @if(!empty($skill['name']))
                    <div class="skill-tag">{{ $skill['name'] }}</div>
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

    <!-- Certifications -->
    @if(!empty($resumeData['certifications']) && is_array($resumeData['certifications']))
    <div class="section">
        <div class="section-title">Certifications</div>
        @foreach($resumeData['certifications'] as $cert)
            <div class="education-item">
                <div class="job-title">{{ $cert['name'] ?? '' }}</div>
                <div class="company">
                    {{ $cert['issuing_organization'] ?? '' }}
                    @if(!empty($cert['date']))
                        <span class="date">{{ date('M Y', strtotime($cert['date'])) }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    @endif
</body>
</html>
