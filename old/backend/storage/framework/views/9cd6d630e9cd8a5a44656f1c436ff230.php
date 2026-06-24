<!DOCTYPE html>
<html lang="<?php echo e($locale ?? 'en'); ?>" dir="<?php echo e(($locale ?? 'en') === 'ar' ? 'rtl' : 'ltr'); ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Driver Resume - <?php echo e($resumeData['first_name'] ?? ''); ?> <?php echo e($resumeData['last_name'] ?? ''); ?></title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.3;
            margin: 0;
            padding: 30px 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        td, th {
            padding: 4px 6px;
            vertical-align: middle;
        }
        .text-red {
            color: #b11217;
        }
        .text-blue {
            color: #1d4ed8;
        }
        .bg-gray {
            background-color: #f5f5f5;
        }
        .bg-blue-light {
            background-color: #eff6ff;
        }
        .font-bold {
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-xs {
            font-size: 9pt;
        }
        .border {
            border: 1px solid #ccc;
        }
        .border-bottom {
            border-bottom: 1px solid #ccc;
        }
        .header-title {
            font-size: 24pt;
            font-weight: 800;
            text-align: center;
            margin-bottom: 20px;
            letter-spacing: 1px;
        }
        .section-header {
            font-weight: bold;
            font-size: 10pt;
            background-color: #f5f5f5;
            color: #b11217;
            text-align: center;
            border: 1px solid #ccc;
            padding: 5px;
        }
        .table-label {
            width: 25%;
            font-weight: bold;
            border: 1px solid #ccc;
            font-size: 9pt;
        }
        .table-value {
            width: 25%;
            border: 1px solid #ccc;
            font-weight: 600;
            font-size: 9pt;
        }
        .table-value-wide {
            width: 75%;
            border: 1px solid #ccc;
            font-weight: 600;
            font-size: 9pt;
        }
        .profile-photo {
            width: 140px;
            height: 170px;
            border: 1px solid #ccc;
            object-fit: cover;
        }
        .bilingual-cell {
            width: 100%;
        }
        .bilingual-left {
            float: left;
            text-align: left;
        }
        .bilingual-right {
            float: right;
            text-align: right;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <?php
        $isRtl = ($locale ?? 'en') === 'ar';
        $labels = [
            'date' => ['en' => 'DATE', 'ar' => 'التاريخ'],
            'expected_location' => ['en' => 'EXPECTED LOCATION', 'ar' => 'موقع منتظر'],
            'full_names' => ['en' => 'FULL NAMES', 'ar' => 'الاسم الكامل'],
            'passport_no' => ['en' => 'PASSPORT NO', 'ar' => 'رقم جواز السفر'],
            'nationality' => ['en' => 'NATIONALITY', 'ar' => 'الجنسية'],
            'marital_status' => ['en' => 'MARITAL STATUS', 'ar' => 'الحالة الاجتماعية'],
            'age' => ['en' => 'AGE', 'ar' => 'العمر'],
            'date_of_birth' => ['en' => 'DATE OF BIRTH', 'ar' => 'تاريخ الميلاد'],
            'address' => ['en' => 'ADDRESS', 'ar' => 'عنوان'],
            'driver_license_record' => ['en' => 'DRIVER LICENSE & RECORD', 'ar' => 'رخصة القيادة والسجل'],
            'license_no' => ['en' => 'LICENSE NO', 'ar' => 'رقم الرخصة'],
            'issuing_country' => ['en' => 'ISSUING COUNTRY', 'ar' => 'بلد الإصدار'],
            'issuing_authority' => ['en' => 'ISSUING AUTHORITY', 'ar' => 'جهة الإصدار'],
            'license_type' => ['en' => 'LICENSE TYPE', 'ar' => 'نوع الرخصة'],
            'license_expiry' => ['en' => 'LICENSE EXPIRY', 'ar' => 'انتهاء الرخصة'],
            'clean_record' => ['en' => 'CLEAN RECORD', 'ar' => 'سجل نظيف'],
            'accident_free_years' => ['en' => 'ACCIDENT FREE YEARS', 'ar' => 'سنوات بدون حوادث'],
            'clean_record_highlights' => ['en' => 'CLEAN RECORD HIGHLIGHTS', 'ar' => 'مميزات السجل النظيف'],
            'previous_employment_abroad' => ['en' => 'PREVIOUS EMPLOYMENT ABROAD', 'ar' => 'الخبرة السابقة خارج البلد'],
            'period' => ['en' => 'PERIOD', 'ar' => 'فترة'],
            'position' => ['en' => 'POSITION', 'ar' => 'منصب'],
            'country' => ['en' => 'COUNTRY', 'ar' => 'بلد'],
            'educational_qualifications' => ['en' => 'EDUCATIONAL QUALIFICATIONS', 'ar' => 'المؤهلات التعليمية'],
            'languages' => ['en' => 'LANGUAGES', 'ar' => 'اللغات'],
            'remarks' => ['en' => 'REMARKS(M)', 'ar' => 'ملاحظات'],
            'ready_to_work' => ['en' => 'READY TO WORK', 'ar' => 'جاهز للعمل'],
            'yes' => ['en' => 'Yes', 'ar' => 'نعم'],
            'no' => ['en' => 'No', 'ar' => 'لا'],
        ];

        function getLabel($key, $locale, $labels) {
            $l = $labels[$key] ?? ['en' => $key, 'ar' => $key];
            if ($locale === 'en') return $l['en'];
            // Bilingual return
            return '<div class="bilingual-cell clearfix"><span class="bilingual-left">'.$l['en'].'</span><span class="bilingual-right">'.$l['ar'].'</span></div>';
        }

        // Helper to safely get nested array values
        $safeGet = function($array, $key, $default = '') {
            return $array[$key] ?? $default;
        };
        
        // Helper for formatting dates
        $formatDate = function($dateStr) {
            if (empty($dateStr)) return '';
            try {
                $ts = strtotime($dateStr);
                if (!$ts) return $dateStr;
                return date('d/m/Y', $ts);
            } catch (\Exception $e) {
                return $dateStr;
            }
        };

        // Calculate Age
        $basicInfo = $resumeData['basic_information'] ?? [];
        $dob = $resumeData['date_of_birth'] ?? $basicInfo['date_of_birth'] ?? '';
        $age = '';
        if ($dob) {
            try {
                $dobDate = new DateTime($dob);
                $now = new DateTime();
                $age = $now->diff($dobDate)->y;
            } catch (\Exception $e) {}
        }

        // Address construction - filter out empty values
        $addrParts = [];
        $basicInfo = $resumeData['basic_information'] ?? [];
        if (!empty($basicInfo['address'])) $addrParts[] = $basicInfo['address'];
        if (!empty($basicInfo['city'])) $addrParts[] = $basicInfo['city'];
        if (!empty($basicInfo['state_province'])) $addrParts[] = $basicInfo['state_province'];
        if (!empty($basicInfo['country'])) $addrParts[] = $basicInfo['country'];
        $fullAddress = implode(', ', $addrParts);

        // Profile Photo Logic - For PDF generation, use file:// protocol
        $profilePhoto = null;
        
        // Priority 1: Use pre-computed absolute file path (from prepareResumeDataForPdf)
        if (!empty($resumeData['profile_image_file']) && file_exists($resumeData['profile_image_file'])) {
            $profilePhoto = $resumeData['profile_image_file'];
        }
        // Priority 2: Try raw path from Seeker model
        elseif (!empty($resumeData['profile_image_path'])) {
            $rawPath = $resumeData['profile_image_path'];
            $profilePhoto = resolveImagePath($rawPath);
        }
        // Priority 3: Fallback to basic_information profile_photo
        elseif (!empty($resumeData['basic_information']['profile_photo'])) {
            $rawPath = $resumeData['basic_information']['profile_photo'];
            $profilePhoto = resolveImagePath($rawPath);
        }

        // Helper function to resolve image path to absolute file path
        function resolveImagePath($rawPath) {
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
        
        // Expected Location
        $jobPrefs = $resumeData['job_preferences'] ?? [];
        $basicInfo = $resumeData['basic_information'] ?? [];
        $preferredLocations = $jobPrefs['preferred_locations'] ?? [];
        if (is_array($preferredLocations)) {
            $expectedLocation = implode(', ', $preferredLocations);
        } else {
            $expectedLocation = $preferredLocations ?? $jobPrefs['preferred_location'] ?? $basicInfo['expected_location'] ?? '';
        }
    ?>

    <!-- Title -->
    <div class="header-title text-red">
        RESUME (DRIVER)
    </div>

    <!-- Top Block -->
    <table style="border: none;">
        <tr>
            <td style="vertical-align: top; width: 70%; padding: 0;">
                <table style="border: none;">
                    <tr>
                        <td class="table-label"><?php echo getLabel('date', $locale, $labels); ?></td>
                        <td class="table-value" style="width: 75%;"><?php echo e(date('d/m/Y')); ?></td>
                    </tr>
                    <tr>
                        <td class="table-label"><?php echo getLabel('expected_location', $locale, $labels); ?></td>
                        <td class="table-value" style="width: 75%;"><?php echo e($expectedLocation); ?></td>
                    </tr>
                </table>
            </td>
            <td style="vertical-align: top; width: 30%; text-align: right; padding: 0;">
                <div style="width: 140px; height: 170px; margin-left: auto; border: 1px solid #ccc; overflow: hidden;">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($profilePhoto): ?>
                    <img src="<?php echo e($profilePhoto); ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Profile Photo">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; background: #eee; display: flex; align-items: center; justify-content: center; text-align: center; line-height: 170px; color: #999;">
                        No Photo
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- Personal Info Block -->
    <table style="margin-top: 20px;">
        <tr>
            <td class="table-label"><?php echo getLabel('full_names', $locale, $labels); ?></td>
            <td class="table-value" style="width: 35%;">
                <?php echo e($resumeData['first_name'] ?? ''); ?> <?php echo e($resumeData['last_name'] ?? ''); ?>

            </td>
            <td class="table-label" style="width: 15%;"><?php echo getLabel('passport_no', $locale, $labels); ?></td>
            <td class="table-value" style="width: 25%;">
                <?php echo e($resumeData['documents']['passport_number'] ?? $resumeData['basic_information']['passport_number'] ?? ''); ?>

            </td>
        </tr>
        <tr>
            <td class="table-label"><?php echo getLabel('nationality', $locale, $labels); ?></td>
            <td class="table-value">
                <?php echo e($basicInfo['nationality'] ?? $basicInfo['country'] ?? ''); ?>

            </td>
            <td class="table-label"><?php echo getLabel('marital_status', $locale, $labels); ?></td>
            <td class="table-value">
                <?php echo e($basicInfo['marital_status'] ?? ''); ?>

            </td>
        </tr>
        <tr>
            <td class="table-label"><?php echo getLabel('age', $locale, $labels); ?></td>
            <td class="table-value"><?php echo e($age); ?></td>
            <td class="table-label"><?php echo getLabel('date_of_birth', $locale, $labels); ?></td>
            <td class="table-value"><?php echo e($formatDate($dob)); ?></td>
        </tr>
        <tr>
            <td class="table-label"><?php echo getLabel('address', $locale, $labels); ?></td>
            <td class="table-value" colspan="3" style="width: 75%;"><?php echo e($fullAddress); ?></td>
        </tr>
    </table>

    <!-- License Block -->
    <div class="section-header">
        <?php echo getLabel('driver_license_record', $locale, $labels); ?>

    </div>
    <table style="margin-top: -1px;">
        <tr>
            <td class="table-label"><?php echo getLabel('license_no', $locale, $labels); ?></td>
            <td class="table-value" style="width: 30%;"><?php echo e($resumeData['license_number'] ?? ''); ?></td>
            <td class="table-label" style="width: 15%;"><?php echo getLabel('issuing_country', $locale, $labels); ?></td>
            <td class="table-value" style="width: 30%;"><?php echo e($resumeData['license_issuing_country'] ?? ''); ?></td>
        </tr>
        <tr>
            <td class="table-label"><?php echo getLabel('issuing_authority', $locale, $labels); ?></td>
            <td class="table-value"><?php echo e($resumeData['license_issuing_authority'] ?? ''); ?></td>
            <td class="table-label"><?php echo getLabel('license_type', $locale, $labels); ?></td>
            <td class="table-value"><?php echo e($resumeData['license_type'] ?? ''); ?></td>
        </tr>
        <tr>
            <td class="table-label"><?php echo getLabel('license_expiry', $locale, $labels); ?></td>
            <td class="table-value"><?php echo e($formatDate($resumeData['license_expiry_date'] ?? $resumeData['license_expiry'] ?? '')); ?></td>
            <td class="table-label"><?php echo getLabel('clean_record', $locale, $labels); ?></td>
            <td class="table-value">
                <?php echo e(($resumeData['has_clean_driving_record'] ?? false) ? getLabel('yes', $locale, $labels) : getLabel('no', $locale, $labels)); ?>

            </td>
        </tr>
        <tr>
            <td class="table-label"><?php echo getLabel('accident_free_years', $locale, $labels); ?></td>
            <td class="table-value" colspan="3"><?php echo e($resumeData['accident_free_years'] ?? '0'); ?></td>
        </tr>
    </table>

    <!-- Employment Block -->
    <div class="section-header">
        <?php echo getLabel('previous_employment_abroad', $locale, $labels); ?>

    </div>
    <table style="margin-top: -1px;">
        <tr class="bg-gray">
            <th class="border text-center" style="width: 25%;"><?php echo getLabel('period', $locale, $labels); ?></th>
            <th class="border text-center" style="width: 45%;"><?php echo getLabel('position', $locale, $labels); ?></th>
            <th class="border text-center" style="width: 30%;"><?php echo getLabel('country', $locale, $labels); ?></th>
        </tr>
        <?php
            $workExp = $resumeData['work_experience'] ?? [];
            if (!is_array($workExp)) $workExp = [];
            // Fill empty rows if needed to look good? React template slices 5.
            $workExp = array_slice($workExp, 0, 5);
        ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $workExp; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $exp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
        <tr>
            <td class="border text-center">
                <?php echo e($formatDate($exp['start_date'] ?? '')); ?> - <?php echo e(!empty($exp['end_date']) ? $formatDate($exp['end_date']) : 'Present'); ?>

            </td>
            <td class="border text-center font-bold"><?php echo e($exp['role_title'] ?? $exp['job_title'] ?? ''); ?></td>
            <td class="border text-center"><?php echo e($exp['location'] ?? $exp['country'] ?? ''); ?></td>
        </tr>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        <tr>
            <td class="border text-center">&nbsp;</td>
            <td class="border text-center">&nbsp;</td>
            <td class="border text-center">&nbsp;</td>
        </tr>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </table>

    <!-- Education Block -->
    <div class="section-header">
        <?php echo getLabel('educational_qualifications', $locale, $labels); ?>

    </div>
    <table style="margin-top: -1px;">
        <?php
            $edu = $resumeData['education'] ?? [];
            if (!is_array($edu)) $edu = [];
            $edu = array_slice($edu, 0, 5);
        ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $edu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
        <tr>
            <td class="border text-center" style="width: 20%;"><?php echo e($e['graduation_year'] ?? $e['year'] ?? ''); ?></td>
            <td class="border text-center font-bold" style="width: 40%;"><?php echo e($e['degree_title'] ?? $e['degree'] ?? $e['qualification'] ?? ''); ?></td>
            <td class="border text-center" style="width: 40%;"><?php echo e($e['institution_name'] ?? $e['school'] ?? ''); ?></td>
        </tr>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        <tr>
            <td class="border text-center" colspan="3">&nbsp;</td>
        </tr>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </table>

    <!-- Languages Block -->
    <div class="section-header">
        <?php echo getLabel('languages', $locale, $labels); ?>

    </div>
    <table style="margin-top: -1px;">
        <?php
            $langs = $resumeData['languages'] ?? [];
            if (!is_array($langs)) $langs = [];
            $langs = array_slice($langs, 0, 5);
        ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $langs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
        <tr>
            <td class="border font-bold" style="width: 30%;"><?php echo e(strtoupper($l['language_name'] ?? '')); ?></td>
            <td class="border font-bold"><?php echo e($l['proficiency_level'] ?? $l['level'] ?? ''); ?></td>
        </tr>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        <tr>
            <td class="border" colspan="2">&nbsp;</td>
        </tr>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </table>

    <!-- Remarks -->
    <div class="section-header">
        <?php echo getLabel('remarks', $locale, $labels); ?>

    </div>
    <table style="margin-top: -1px;">
        <tr>
            <td class="border text-center font-bold" style="padding: 20px;">
                <?php echo getLabel('ready_to_work', $locale, $labels); ?>

            </td>
        </tr>
    </table>

</body>
</html>
<?php /**PATH /home/abdulbas/public_html/ogsmanpower.com/backend/resources/views/templates/resume/driver.blade.php ENDPATH**/ ?>