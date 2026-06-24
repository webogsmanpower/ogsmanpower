<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Security Guard CV - DEBUG Jan 2026 v2</title>
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
    </style>
</head>
<body>
<?php 
    use Illuminate\Support\Facades\Storage;
    
    $basic = $seeker->resume->basic_information ?? [];
    $docs = $seeker->resume->documents ?? [];
    $languages = $seeker->resume->languages ?? [];
    $education = $seeker->resume->education ?? [];
    
    // Security guard specific data
    $securityDetails = $seeker->resume->security_guard_details ?? [];
    
    $fullName = trim(($basic['first_name'] ?? $seeker->first_name ?? '') . ' ' . ($basic['last_name'] ?? $seeker->last_name ?? ''));
    $profession = $basic['job_title'] ?? $seeker->resume->profession ?? 'SECURITY GUARD';
    
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
    // Security guard full body image takes priority
    if (!$fullBodyAbsolute && !empty($securityDetails['full_body_image_path'])) {
        $cleanPath = str_replace('storage/', '', $securityDetails['full_body_image_path']);
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
?>

<div class="wrapper">
    <!-- DEBUG: Security Template Loaded - Jan 2026 v2 -->
    <!-- Header Section with Red Bottom Border -->
    <div class="header-section">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="name-title"><?php echo e(strtoupper($fullName) ?: 'YOUR NAME'); ?></div>
                    <div class="profession-text">Profession: <?php echo e(strtoupper($profession)); ?></div>
                </td>
                <td class="header-right">
                    <div class="passport-photo">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($profilePhotoBase64): ?>
                            <img src="data:<?php echo e($profilePhotoMime); ?>;base64,<?php echo e($profilePhotoBase64); ?>" alt="Photo">
                        <?php else: ?>
                            <div style="padding-top: 60px; color: #9ca3af; font-size: 10px;">Photo</div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($fullBodyBase64): ?>
                        <img src="data:<?php echo e($fullBodyMime); ?>;base64,<?php echo e($fullBodyBase64); ?>" alt="Full Body">
                    <?php else: ?>
                        <div class="no-photo-text">Full Body Photo</div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <!-- Details of Applicant - Gray Border -->
                <div class="details-box">
                    <div class="details-header">DETAILS OF APPLICANT:</div>
                    <div class="details-content">
                        <div class="detail-row">
                            <span class="detail-label">NATIONALITY</span>
                            <span class="detail-value"><?php echo e(strtoupper($basic['nationality'] ?? '')); ?></span>
                            <div class="detail-arabic">الجنسية</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">DATE OF BIRTH</span>
                            <span class="detail-value"><?php echo e(isset($basic['date_of_birth']) ? \Carbon\Carbon::parse($basic['date_of_birth'])->format('d M Y') : ($seeker->date_of_birth ? $seeker->date_of_birth->format('d M Y') : '')); ?></span>
                            <div class="detail-arabic">تاريخ الميلاد</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">PLACE OF BIRTH</span>
                            <span class="detail-value"><?php echo e(strtoupper($basic['place_of_birth'] ?? $basic['city'] ?? '')); ?></span>
                            <div class="detail-arabic">مكان الولادة</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">CIVIL STATUS</span>
                            <span class="detail-value"><?php echo e(strtoupper($basic['marital_status'] ?? '')); ?></span>
                            <div class="detail-arabic">الحالة الإجتماعية</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">HEIGHT</span>
                            <span class="detail-value"><?php echo e(($height = $securityDetails['height'] ?? $seeker->height ?? $basic['height'] ?? '') ? $height . ' CM' : ''); ?></span>
                            <div class="detail-arabic">الطول</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">WEIGHT</span>
                            <span class="detail-value"><?php echo e(($weight = $securityDetails['weight'] ?? $seeker->weight ?? $basic['weight'] ?? '') ? $weight . ' KG' : ''); ?></span>
                            <div class="detail-arabic">الوزن</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">CHEST SIZE</span>
                            <span class="detail-value"><?php echo e(($chestSize = $securityDetails['chest_size'] ?? $basic['chest_size'] ?? '') ? $chestSize . ' CM' : ''); ?></span>
                            <div class="detail-arabic">مقاس الصدر</div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">AGE</span>
                            <span class="detail-value"><?php echo e($age); ?></span>
                            <div class="detail-arabic">العمر</div>
                        </div>
                    </div>
                </div>
            </td>

            <!-- Right Column - Job Details and Information -->
            <td class="right-column">
                <!-- Job Information Table -->
                <table class="data-table">
                    <tr>
                        <td class="label-cell">JOB</td>
                        <td class="value-cell"><?php echo e(strtoupper($profession)); ?></td>
                        <td class="arabic-cell">الوظيفة</td>
                    </tr>
                    <tr>
                        <td class="label-cell">NAME</td>
                        <td class="value-cell"><?php echo e(strtoupper($fullName)); ?></td>
                        <td class="arabic-cell">الإسم الكامل</td>
                    </tr>
                    <tr>
                        <td class="label-cell">CONTACT NUMBER</td>
                        <td class="value-cell"><?php echo e($basic['phone'] ?? $seeker->user->mobile ?? ''); ?></td>
                        <td class="arabic-cell">رقم التليفون</td>
                    </tr>
                </table>

                <!-- Passport Details -->
                <div class="section-header">PASSPORT DETAILS:</div>
                <table class="data-table">
                    <tr>
                        <td class="label-cell">NUMBER</td>
                        <td class="value-cell"><?php echo e($passportNumber ?: '-'); ?></td>
                        <td class="arabic-cell">رقم جواز السفر</td>
                    </tr>
                    <tr>
                        <td class="label-cell">ISSUE DATE</td>
                        <td class="value-cell"><?php echo e($passportIssue ?: '-'); ?></td>
                        <td class="arabic-cell">تاريخ الإصدار</td>
                    </tr>
                    <tr>
                        <td class="label-cell">EXP. DATE</td>
                        <td class="value-cell"><?php echo e($passportExpiry ?: '-'); ?></td>
                        <td class="arabic-cell">تاريخ الإنتهاء</td>
                    </tr>
                    <tr>
                        <td class="label-cell">PLACE</td>
                        <td class="value-cell"><?php echo e(strtoupper($passportPlace) ?: '-'); ?></td>
                        <td class="arabic-cell">مكان الإصدار</td>
                    </tr>
                </table>

                <!-- Language Skills -->
                <div class="section-header">LANGUAGE:</div>
                <table class="data-table">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $languageList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <tr>
                        <td class="label-cell"><?php echo e($lang['name']); ?></td>
                        <td class="value-cell"><?php echo e($lang['level']); ?></td>
                        <td class="arabic-cell"><?php echo e($lang['name']); ?></td>
                    </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">No languages specified</td>
                    </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </table>

                <!-- Educational Qualification -->
                <div class="section-header" style="display: table; width: 100%;">
                    <span style="float: left;">EDUCATIONAL QUALIFICATION:</span>
                    <span style="float: right;">المؤهلات التعليمية</span>
                </div>
                <table class="data-table">
                    <tr>
                        <td colspan="3" class="value-cell"><?php echo e(strtoupper($eduSummary) ?: '-'); ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
<?php /**PATH /home/abdulbas/public_html/ogsmanpower.com/backend/resources/views/pdf/security.blade.php ENDPATH**/ ?>