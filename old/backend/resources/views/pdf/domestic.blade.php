<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Domestic Worker CV</title>
    <style>
        @page { margin: 20px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            color: #1e293b;
            font-size: 10px;
            line-height: 1.3;
        }
        .wrapper { 
            width: 100%; 
            border: 2px solid #e5e7eb;
            padding: 15px;
        }
        /* Header with red bottom border - matches React */
        .header-section {
            border-bottom: 4px solid #dc2626;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-left { vertical-align: middle; }
        .header-right { 
            width: 130px; 
            vertical-align: top; 
            text-align: right;
        }
        .name-title {
            font-size: 22px;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .profession-text {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }
        /* Passport photo with sky-blue border */
        .passport-photo {
            width: 128px;
            height: 160px;
            border: 4px solid #0ea5e9;
            overflow: hidden;
        }
        .passport-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        /* Main content layout */
        .main-content { width: 100%; }
        .main-table { width: 100%; border-collapse: collapse; }
        .left-column { width: 38%; vertical-align: top; padding-right: 15px; }
        .right-column { width: 62%; vertical-align: top; }
        /* Full body photo with sky-blue background */
        .full-body-container {
            width: 100%;
            height: 400px;
            background-color: #0ea5e9;
            text-align: center;
            margin-bottom: 12px;
        }
        .full-body-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .no-photo-text {
            color: #ffffff;
            font-size: 12px;
            padding-top: 140px;
        }
        /* Gray bordered details box - matches React frontend */
        .details-box {
            border: 2px solid #374151;
            margin-bottom: 12px;
        }
        .details-header {
            background-color: #374151;
            color: #ffffff;
            font-weight: bold;
            font-size: 11px;
            padding: 6px 8px;
        }
        .details-content { padding: 8px; }
        .detail-row {
            border-bottom: 1px solid #e5e7eb;
            padding: 4px 0;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { 
            font-weight: bold; 
            font-size: 9px;
            color: #475569;
        }
        .detail-value { 
            font-weight: bold; 
            font-size: 10px;
            color: #1e293b;
            float: right;
        }
        .detail-arabic {
            font-size: 8px;
            color: #64748b;
            text-align: right;
            clear: both;
        }
        /* Data tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #9ca3af;
            margin-bottom: 10px;
        }
        .data-table td {
            border: 1px solid #9ca3af;
            padding: 6px 8px;
            font-size: 10px;
        }
        .data-table .label-cell {
            font-weight: bold;
            width: 30%;
            background-color: #f8fafc;
        }
        .data-table .value-cell {
            font-weight: bold;
            text-align: center;
        }
        .data-table .arabic-cell {
            background-color: #f1f5f9;
            text-align: right;
            font-size: 9px;
            color: #64748b;
            width: 25%;
        }
        /* Section headers */
        .section-header {
            background-color: #f1f5f9;
            font-weight: bold;
            padding: 6px 8px;
            border: 1px solid #9ca3af;
            border-bottom: none;
            font-size: 10px;
        }
        /* Gray work experience box - matches React frontend */
        .experience-box {
            border: 2px solid #374151;
            margin-top: 10px;
        }
        .experience-header {
            background-color: #374151;
            color: #ffffff;
            font-weight: bold;
            font-size: 10px;
            padding: 6px 8px;
            text-align: center;
        }
        .experience-content {
            padding: 8px;
        }
        .experience-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .experience-grid td {
            width: 50%;
            vertical-align: top;
            padding: 4px;
            border: 1px solid #e5e7eb;
        }
        .exp-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }
        .exp-label {
            font-weight: bold;
            color: #475569;
        }
        .exp-value {
            font-weight: bold;
            color: #1e293b;
        }
        /* New 4-column work experience grid */
        .work-experience-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        .work-experience-grid .col-header {
            background-color: #374151;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 4px;
            border: 1px solid #374151;
        }
        .work-experience-grid .col-data {
            text-align: center;
            padding: 4px;
            border: 1px solid #e5e7eb;
            font-weight: bold;
        }
    </style>
</head>
<body>
@php 
    use Illuminate\Support\Facades\Storage;
    
    $basic = $seeker->resume->basic_information ?? [];
    $docs = $seeker->resume->documents ?? [];
    $languages = $seeker->resume->languages ?? [];
    $experiences = $seeker->resume->work_experience ?? [];
    $education = $seeker->resume->education ?? [];
    
    $fullName = trim(($basic['first_name'] ?? $seeker->first_name ?? '') . ' ' . ($basic['last_name'] ?? $seeker->last_name ?? ''));
    $profession = $basic['job_title'] ?? $seeker->resume->profession ?? 'DOMESTIC WORKER';
    
    $age = $seeker->date_of_birth ? \Carbon\Carbon::parse($seeker->date_of_birth)->age : ($basic['date_of_birth'] ? \Carbon\Carbon::parse($basic['date_of_birth'])->age : '');
    
    // Profile photo - embed as Base64 for Browsershot
    $profilePhotoAbsolute = $resumeData['profile_image_file'] ?? null;
    if (!$profilePhotoAbsolute && !empty($seeker->profile_image_path)) {
        $cleanPath = str_replace('storage/', '', $seeker->profile_image_path);
        $diskPath = storage_path('app/public/' . $cleanPath);
        if (file_exists($diskPath)) {
            $profilePhotoAbsolute = $diskPath;
        }
    }
    if (!$profilePhotoAbsolute && !empty($basic['profile_photo'])) {
        $cleanPath = str_replace('storage/', '', $basic['profile_photo']);
        $diskPath = storage_path('app/public/' . $cleanPath);
        if (file_exists($diskPath)) {
            $profilePhotoAbsolute = $diskPath;
        }
    }

    $profilePhotoBase64 = null;
    $profilePhotoMime = null;
    if ($profilePhotoAbsolute && file_exists($profilePhotoAbsolute)) {
        $profilePhotoMime = function_exists('mime_content_type') ? mime_content_type($profilePhotoAbsolute) : 'image/jpeg';
        if (!$profilePhotoMime) {
            $profilePhotoMime = 'image/jpeg';
        }
        $profilePhotoBase64 = base64_encode(file_get_contents($profilePhotoAbsolute));
    }

    // Full body photo - embed as Base64 for Browsershot
    $fullBodyAbsolute = $resumeData['full_body_image_file'] ?? null;
    if (!$fullBodyAbsolute && !empty($seeker->full_body_image_path)) {
        $cleanPath = str_replace('storage/', '', $seeker->full_body_image_path);
        $diskPath = storage_path('app/public/' . $cleanPath);
        if (file_exists($diskPath)) {
            $fullBodyAbsolute = $diskPath;
        }
    }
    if (!$fullBodyAbsolute && !empty($seeker->resume->full_body_photo)) {
        $cleanPath = str_replace('storage/', '', $seeker->resume->full_body_photo);
        $diskPath = storage_path('app/public/' . $cleanPath);
        if (file_exists($diskPath)) {
            $fullBodyAbsolute = $diskPath;
        }
    }

    $fullBodyBase64 = null;
    $fullBodyMime = null;
    if ($fullBodyAbsolute && file_exists($fullBodyAbsolute)) {
        $fullBodyMime = function_exists('mime_content_type') ? mime_content_type($fullBodyAbsolute) : 'image/jpeg';
        if (!$fullBodyMime) {
            $fullBodyMime = 'image/jpeg';
        }
        $fullBodyBase64 = base64_encode(file_get_contents($fullBodyAbsolute));
    }
    
    // Language list
    $languageList = [];
    foreach ($languages as $lang) {
        $name = $lang['language_name'] ?? $lang['language'] ?? null;
        $level = $lang['proficiency_level'] ?? $lang['proficiency'] ?? $lang['level'] ?? '';
        if ($name) {
            $languageList[] = ['name' => strtoupper($name), 'level' => strtoupper($level)];
        }
    }
    
    // Passport data
    $passportNumber = $docs['passport_number'] ?? $basic['passport_number'] ?? '';
    $passportIssue = isset($docs['passport_issue_date']) ? \Carbon\Carbon::parse($docs['passport_issue_date'])->format('d M Y') : '';
    $passportExpiry = isset($docs['passport_expiry_date']) ? \Carbon\Carbon::parse($docs['passport_expiry_date'])->format('d M Y') : '';
    $passportPlace = $docs['passport_issue_place'] ?? $basic['passport_issue_place'] ?? '';
    
    // Education summary
    $eduSummary = '';
    if (!empty($education)) {
        $firstEdu = is_array($education) ? (isset($education[0]) ? $education[0] : $education) : [];
        $degree = $firstEdu['degree_title'] ?? $firstEdu['degree'] ?? '';
        $institution = $firstEdu['institution_name'] ?? $firstEdu['institution'] ?? '';
        $eduSummary = trim($degree . ($degree && $institution ? ' - ' : '') . $institution);
    }
@endphp

<div class="wrapper">
    <!-- Header Section with Red Bottom Border -->
    <div class="header-section">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="name-title">{{ strtoupper($fullName) ?: 'YOUR NAME' }}</div>
                    <div class="profession-text">Profession: {{ strtoupper($profession) }}</div>
                </td>
                <td class="header-right">
                    <div class="passport-photo">
                        @if($profilePhotoBase64)
                            <img src="data:{{ $profilePhotoMime }};base64,{{ $profilePhotoBase64 }}" alt="Photo">
                        @else
                            <div style="padding-top: 60px; color: #9ca3af; font-size: 10px;">Photo</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Main Content Grid -->
    <table class="main-table">
        <tr>
            <!-- Left Column - Photo and Details -->
            <td class="left-column">
                <!-- Full Body Photo -->
                <div class="full-body-container">
                    @if($fullBodyBase64)
                        <img src="data:{{ $fullBodyMime }};base64,{{ $fullBodyBase64 }}" alt="Full Body">
                    @else
                        <div class="no-photo-text">Full Body Photo</div>
                    @endif
                </div>

                <!-- Details of Applicant - Orange Border -->
                <div class="details-box">
                    <div class="details-header">DETAILS OF APPLICANT:</div>
                    <div class="details-content">
                        <div class="detail-row">
                            <span class="detail-label">NATIONALITY</span>
                            <span class="detail-value">{{ strtoupper($basic['nationality'] ?? '') }}</span>
                            <div class="detail-arabic">الجنسية</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">DATE OF BIRTH</span>
                            <span class="detail-value">{{ isset($basic['date_of_birth']) ? \Carbon\Carbon::parse($basic['date_of_birth'])->format('d M Y') : ($seeker->date_of_birth ? $seeker->date_of_birth->format('d M Y') : '') }}</span>
                            <div class="detail-arabic">تاريخ الميلاد</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">PLACE OF BIRTH</span>
                            <span class="detail-value">{{ strtoupper($basic['place_of_birth'] ?? $basic['city'] ?? '') }}</span>
                            <div class="detail-arabic">مكان الولادة</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">CIVIL STATUS</span>
                            <span class="detail-value">{{ strtoupper($basic['marital_status'] ?? '') }}</span>
                            <div class="detail-arabic">الحالة الإجتماعية</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">NO. OF CHILDREN</span>
                            <span class="detail-value">{{ $seeker->number_of_children ?? '' }}</span>
                            <div class="detail-arabic">عدد الأولاد</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">WEIGHT</span>
                            <span class="detail-value">{{ ($weight = $seeker->weight ?? $basic['weight'] ?? '') ? $weight . ' KG' : '' }}</span>
                            <div class="detail-arabic">الوزن</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">HEIGHT</span>
                            <span class="detail-value">{{ ($height = $seeker->height ?? $basic['height'] ?? '') ? $height . ' CM' : '' }}</span>
                            <div class="detail-arabic">الطول</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">AGE</span>
                            <span class="detail-value">{{ $age }}</span>
                            <div class="detail-arabic">العمر</div>
                        </div>
                    </div>
                </div>
            </td>

            <!-- Right Column - Job Details and Experience -->
            <td class="right-column">
                <!-- Job Information Table -->
                <table class="data-table">
                    <tr>
                        <td class="label-cell">JOB</td>
                        <td class="value-cell">{{ strtoupper($profession) }}</td>
                        <td class="arabic-cell">الوظيفة</td>
                    </tr>
                    <tr>
                        <td class="label-cell">NAME</td>
                        <td class="value-cell">{{ strtoupper($fullName) }}</td>
                        <td class="arabic-cell">الإسم الكامل</td>
                    </tr>
                    <tr>
                        <td class="label-cell">CONTACT NUMBER</td>
                        <td class="value-cell">{{ $basic['phone'] ?? $seeker->user->mobile ?? '' }}</td>
                        <td class="arabic-cell">رقم التليفون</td>
                    </tr>
                </table>

                <!-- Passport Details -->
                <div class="section-header">PASSPORT DETAILS:</div>
                <table class="data-table">
                    <tr>
                        <td class="label-cell">NUMBER</td>
                        <td class="value-cell">{{ $passportNumber ?: '-' }}</td>
                        <td class="arabic-cell">رقم جواز السفر</td>
                    </tr>
                    <tr>
                        <td class="label-cell">ISSUE DATE</td>
                        <td class="value-cell">{{ $passportIssue ?: '-' }}</td>
                        <td class="arabic-cell">تاريخ الإصدار</td>
                    </tr>
                    <tr>
                        <td class="label-cell">EXP. DATE</td>
                        <td class="value-cell">{{ $passportExpiry ?: '-' }}</td>
                        <td class="arabic-cell">تاريخ الإنتهاء</td>
                    </tr>
                    <tr>
                        <td class="label-cell">PLACE</td>
                        <td class="value-cell">{{ strtoupper($passportPlace) ?: '-' }}</td>
                        <td class="arabic-cell">مكان الإصدار</td>
                    </tr>
                </table>

                <!-- Language Skills -->
                <div class="section-header">LANGUAGE:</div>
                <table class="data-table">
                    @forelse($languageList as $lang)
                    <tr>
                        <td class="label-cell">{{ $lang['name'] }}</td>
                        <td class="value-cell">{{ $lang['level'] }}</td>
                        <td class="arabic-cell">{{ $lang['name'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align: center;">No languages specified</td>
                    </tr>
                    @endforelse
                </table>

                <div class="section-header" style="display: table; width: 100%;">
                    <span style="float: left;">SKILLS:</span>
                    <span style="float: right;">المهارات</span>
                </div>
                <table class="data-table">
                    <tr>
                        <td class="label-cell">WASHING</td>
                        <td class="value-cell">{{ ($resumeData['skill_washing'] ?? $seeker->skill_washing ?? false) ? 'YES' : 'NO' }}</td>
                        <td class="arabic-cell">الغسيل</td>
                    </tr>
                    <tr>
                        <td class="label-cell">COOKING</td>
                        <td class="value-cell">{{ ($resumeData['skill_cooking'] ?? $seeker->skill_cooking ?? false) ? 'YES' : 'NO' }}</td>
                        <td class="arabic-cell">الطبخ</td>
                    </tr>
                    <tr>
                        <td class="label-cell">BABY SITTING</td>
                        <td class="value-cell">{{ ($resumeData['skill_babysitting'] ?? $seeker->skill_babysitting ?? false) ? 'YES' : 'NO' }}</td>
                        <td class="arabic-cell">رعاية الأطفال</td>
                    </tr>
                    <tr>
                        <td class="label-cell">CLEANING</td>
                        <td class="value-cell">{{ ($resumeData['skill_cleaning'] ?? $seeker->skill_cleaning ?? false) ? 'YES' : 'NO' }}</td>
                        <td class="arabic-cell">التنظيف</td>
                    </tr>
                </table>

                <!-- Educational Qualification -->
                <div class="section-header" style="display: table; width: 100%;">
                    <span style="float: left;">EDUCATIONAL QUALIFICATION:</span>
                    <span style="float: right;">المؤهلات التعليمية</span>
                </div>
                <table class="data-table">
                    <tr>
                        <td colspan="3" class="value-cell">{{ strtoupper($eduSummary) ?: '-' }}</td>
                    </tr>
                </table>

                <!-- Work Experience - Full Width 4-Column Table -->
                <div class="experience-box">
                    <div class="experience-header">PLACE OF WORK EXPERIENCED:</div>
                    <div class="experience-content">
                        <table class="work-experience-grid">
                            <tr>
                                <td class="col-header">COUNTRY</td>
                                <td class="col-header">JOB TITLE</td>
                                <td class="col-header">DURATION</td>
                                <td class="col-header">COMPANY</td>
                            </tr>
                            @forelse($experiences as $exp)
                            <tr>
                                <td class="col-data">{{ strtoupper($exp['location'] ?? $exp['country'] ?? 'N/A') }}</td>
                                <td class="col-data">{{ strtoupper($exp['role_title'] ?? $exp['role'] ?? $exp['job_title'] ?? 'N/A') }}</td>
                                <td class="col-data">
                                    @php
                                        $duration = '';
                                        if (!empty($exp['start_date'])) {
                                            $start = \Carbon\Carbon::parse($exp['start_date']);
                                            $end = !empty($exp['is_current_role']) && $exp['is_current_role'] ? now() : 
                                                   (!empty($exp['end_date']) ? \Carbon\Carbon::parse($exp['end_date']) : now());
                                            $years = $start->diffInYears($end);
                                            $duration = $years > 0 ? $years . ' YEAR' . ($years > 1 ? 'S' : '') : '';
                                        }
                                    @endphp
                                    {{ $duration ?: 'N/A' }}
                                </td>
                                <td class="col-data">{{ strtoupper($exp['company_name'] ?? $exp['company'] ?? 'N/A') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="col-data" style="text-align: center;">No Experience</td>
                            </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
