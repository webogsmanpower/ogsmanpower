<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bilingual Resume - {{ $resumeData['first_name'] }} {{ $resumeData['last_name'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        
        .bilingual-container {
            display: flex;
            gap: 20px;
        }
        
        .language-column {
            flex: 1;
        }
        
        .column-header {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        
        .section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }
        
        .name {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
        }
        
        .headline {
            font-size: 12px;
            font-style: italic;
            text-align: center;
            color: #666;
            margin-bottom: 8px;
        }
        
        .contact-info {
            font-size: 10px;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .work-item, .education-item {
            margin-bottom: 12px;
        }
        
        .job-title {
            font-weight: bold;
            font-size: 12px;
        }
        
        .company {
            font-style: italic;
            color: #666;
            font-size: 10px;
        }
        
        .date {
            float: right;
            font-size: 9px;
            color: #666;
        }
        
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .skill-tag {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="bilingual-container">
        <!-- English Column -->
        <div class="language-column">
            <div class="column-header">ENGLISH</div>
            
            <!-- Header -->
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

            <!-- Professional Summary -->
            @if(!empty($resumeData['professional_summary']))
            <div class="section">
                <div class="section-title">Professional Summary</div>
                <p style="font-size: 10px;">{{ $resumeData['professional_summary'] }}</p>
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
                            <p style="font-size: 9px;">{{ $work['description'] }}</p>
                        @endif
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
        </div>

        <!-- Arabic Column (Placeholder for now) -->
        <div class="language-column">
            <div class="column-header">العربية</div>
            
            <!-- Header -->
            <div class="name">{{ $resumeData['first_name'] ?? '' }} {{ $resumeData['last_name'] ?? '' }}</div>
            @if(!empty($resumeData['headline_ar']))
                <div class="headline">{{ $resumeData['headline_ar'] }}</div>
            @else
                <div class="headline">{{ $resumeData['headline'] ?? '' }}</div>
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

            <!-- Professional Summary -->
            @if(!empty($resumeData['professional_summary_ar']))
            <div class="section">
                <div class="section-title">ملخص مهني</div>
                <p style="font-size: 10px;">{{ $resumeData['professional_summary_ar'] }}</p>
            </div>
            @elseif(!empty($resumeData['professional_summary']))
            <div class="section">
                <div class="section-title">ملخص مهني</div>
                <p style="font-size: 10px;">{{ $resumeData['professional_summary'] }}</p>
            </div>
            @endif

            <!-- Work Experience -->
            @if(!empty($resumeData['work_experience']) && is_array($resumeData['work_experience']))
            <div class="section">
                <div class="section-title">الخبرة العمل</div>
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
                            <p style="font-size: 9px;">{{ $work['description'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
            @endif

            <!-- Skills -->
            @if(!empty($resumeData['skills']) && is_array($resumeData['skills']))
            <div class="section">
                <div class="section-title">المهارات</div>
                <div class="skills-list">
                    @foreach($resumeData['skills'] as $skill)
                        @if(!empty($skill['name']))
                            <div class="skill-tag">{{ $skill['name'] }}</div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
