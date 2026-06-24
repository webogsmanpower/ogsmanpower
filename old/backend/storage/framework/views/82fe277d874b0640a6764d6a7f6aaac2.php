<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Driver Resume</title>
    <style>
        @page { margin: 24px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            color: #111827;
            font-size: 10px;
            line-height: 1.3;
        }
        /* Red title header - matches React */
        .title-header {
            color: #b11217;
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 2px;
            padding: 10px 0 15px 0;
        }
        /* Top section with date/location and photo - matching React layout */
        .top-section { width: 100%; margin-bottom: 15px; }
        .top-table { width: 100%; border-collapse: collapse; }
        .top-left { width: 68%; vertical-align: top; }
        .top-right { width: 32%; vertical-align: top; text-align: right; }
        /* Photo box - exact React dimensions */
        .photo-box {
            width: 140px;
            height: 170px;
            border: 1px solid #cccccc;
            background-color: #ffffff;
            float: right;
        }
        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        /* Data tables - matching React column widths */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .data-table td {
            border: 1px solid #cccccc;
            padding: 6px 8px;
            font-size: 10px;
        }
        .data-table .label-cell {
            font-weight: bold;
            width: 18%;
            background-color: #ffffff;
        }
        .data-table .value-cell {
            font-weight: bold;
            width: 32%;
        }
        /* Section headers with red text */
        .section-header {
            background-color: #f5f5f5;
            border: 1px solid #cccccc;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 10px;
            color: #b11217;
            text-align: center;
            margin-top: 8px;
        }
        /* Clean record highlights box */
        .highlights-box {
            border: 1px solid #cccccc;
            padding: 8px;
            margin-bottom: 8px;
        }
        .highlights-title {
            font-weight: bold;
            font-size: 10px;
            color: #b11217;
            margin-bottom: 4px;
        }
        .highlights-item {
            font-size: 9px;
            margin: 2px 0;
        }
        /* Experience table */
        .exp-header-row td {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }
        /* Remarks section */
        .remarks-content {
            text-align: center;
            font-weight: bold;
            padding: 10px;
            font-size: 11px;
        }
    </style>
</head>
<body>
<?php 
    use Illuminate\Support\Facades\Storage;
    
    $basic = $resumeData->basic_information ?? [];
    $docs = $resumeData->documents ?? [];
    $driver = $resumeData->driver_license ?? [];
    $languages = $resumeData->languages ?? [];
    $experiences = $resumeData->work_experience ?? [];
    $education = $resumeData->education ?? [];
    
    $fullName = trim(($basic['first_name'] ?? $resumeData->first_name ?? '') . ' ' . ($basic['last_name'] ?? $resumeData->last_name ?? ''));
    $nationality = $basic['nationality'] ?? $basic['country'] ?? '';
    $passportNo = $docs['passport_number'] ?? $basic['passport_number'] ?? '';
    $maritalStatus = $basic['marital_status'] ?? '';
    
    // Build address
    $addressParts = array_filter([
        $basic['address'] ?? null,
        $basic['city'] ?? null,
        $basic['state_province'] ?? null,
        $basic['country'] ?? null,
    ]);
    $address = implode(', ', $addressParts);
    
    // Age and DOB
    $dob = $basic['date_of_birth'] ?? ($resumeData->date_of_birth ? $resumeData->date_of_birth->format('Y-m-d') : null);
    $dobFormatted = $dob ? \Carbon\Carbon::parse($dob)->format('d/m/Y') : '';
    $age = $dob ? \Carbon\Carbon::parse($dob)->age : '';
    
    // Expected location
    $expectedLocation = $resumeData->job_preferences['preferred_locations'] ?? $basic['expected_location'] ?? '';
    
    // Profile photo - load from disk for DomPDF
    $profilePhotoPath = $resumeData->profile_image_path ?? $basic['profile_photo'] ?? null;
    $profilePhotoAbsolute = null;
    if ($profilePhotoPath) {
        $cleanPath = str_replace('storage/', '', $profilePhotoPath);
        $diskPath = storage_path('app/public/' . $cleanPath);
        if (file_exists($diskPath)) {
            $profilePhotoAbsolute = $diskPath;
        }
    }
    
    // Driver license data - check both resume driver_license JSON and seeker model fields
    $licenseNumber = $driver['license_number'] ?? $resumeData->license_number ?? '';
    $licenseType = $driver['license_type'] ?? $resumeData->license_type ?? '';
    $licenseExpiry = $driver['license_expiry_date'] ?? ($resumeData->license_expiry_date ? $resumeData->license_expiry_date->format('Y-m-d') : null);
    $licenseExpiryFormatted = $licenseExpiry ? \Carbon\Carbon::parse($licenseExpiry)->format('d/m/Y') : '';
    $issuingCountry = $driver['license_issuing_country'] ?? $resumeData->license_issuing_country ?? '';
    $issuingAuthority = $driver['license_issuing_authority'] ?? $resumeData->license_issuing_authority ?? '';
    $accidentFreeYears = $driver['accident_free_years'] ?? $resumeData->accident_free_years ?? '';
    $hasCleanRecord = $driver['has_clean_driving_record'] ?? $resumeData->has_clean_driving_record ?? false;
    
    // Show clean record highlights if applicable
    $showHighlights = (is_numeric($accidentFreeYears) && $accidentFreeYears > 0) || $hasCleanRecord;
    
    // Handle experience as array
    $experienceArray = [];
    if (isset($experiences) && is_array($experiences)) {
        $experienceArray = $experiences;
    } elseif (isset($experiences) && is_object($experiences)) {
        $experienceArray = [$experiences];
    }
    
    // Handle education as array
    $educationArray = [];
    if (isset($education) && is_array($education)) {
        $educationArray = $education;
    } elseif (isset($education) && is_object($education)) {
        $educationArray = [$education];
    }
    
    // Handle languages as array
    $languageArray = [];
    if (isset($languages) && is_array($languages)) {
        $languageArray = $languages;
    } elseif (isset($languages) && is_object($languages)) {
        $languageArray = [$languages];
    }
?>

<!-- Title Header -->
<div class="title-header">RESUME (DRIVER)</div>

<!-- Top Section: Date/Location + Photo -->
<table class="top-table">
    <tr>
        <td class="top-left">
            <table class="data-table">
                <tr>
                    <td class="label-cell">DATE</td>
                    <td class="value-cell"><?php echo e(now()->format('d/m/Y')); ?></td>
                </tr>
                <tr>
                    <td class="label-cell">EXPECTED LOCATION</td>
                    <td class="value-cell"><?php echo e($expectedLocation); ?></td>
                </tr>
            </table>
        </td>
        <td class="top-right">
            <div class="photo-box">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($profilePhotoAbsolute): ?>
                    <img src="<?php echo e($profilePhotoAbsolute); ?>" alt="Photo">
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </td>
    </tr>
</table>

<!-- Main Information Grid - matching React structure exactly -->
<table class="data-table">
    <tr>
        <td class="label-cell">FULL NAMES</td>
        <td class="value-cell"><?php echo e($fullName); ?></td>
        <td class="label-cell">PASSPORT NO</td>
        <td class="value-cell" colspan="3"><?php echo e($passportNo); ?></td>
    </tr>
    <tr>
        <td class="label-cell">NATIONALITY</td>
        <td class="value-cell"><?php echo e($nationality); ?></td>
        <td class="label-cell">MARITAL STATUS</td>
        <td class="value-cell" colspan="3"><?php echo e($maritalStatus); ?></td>
    </tr>
    <tr>
        <td class="label-cell">AGE</td>
        <td class="value-cell"><?php echo e($age); ?></td>
        <td class="label-cell">DATE OF BIRTH</td>
        <td class="value-cell" colspan="3"><?php echo e($dobFormatted); ?></td>
    </tr>
    <tr>
        <td class="label-cell">ADDRESS</td>
        <td class="value-cell" colspan="5"><?php echo e($address); ?></td>
    </tr>

    <tr>
        <td class="section-header" colspan="6" style="color: #b11217;">DRIVER LICENSE & RECORD</td>
    </tr>
    <tr>
        <td class="label-cell">LICENSE NO</td>
        <td class="value-cell"><?php echo e($licenseNumber); ?></td>
        <td class="label-cell">ISSUING COUNTRY</td>
        <td class="value-cell"><?php echo e($issuingCountry); ?></td>
        <td class="label-cell">ISSUING AUTHORITY</td>
        <td class="value-cell"><?php echo e($issuingAuthority); ?></td>
    </tr>
    <tr>
        <td class="label-cell">LICENSE TYPE</td>
        <td class="value-cell"><?php echo e($licenseType); ?></td>
        <td class="label-cell">LICENSE EXPIRY</td>
        <td class="value-cell"><?php echo e($licenseExpiryFormatted); ?></td>
        <td class="label-cell">CLEAN RECORD</td>
        <td class="value-cell"><?php echo e($hasCleanRecord ? 'Yes' : 'No'); ?></td>
    </tr>
    <tr>
        <td class="label-cell">ACCIDENT FREE YEARS</td>
        <td class="value-cell" colspan="5"><?php echo e($accidentFreeYears); ?></td>
    </tr>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showHighlights): ?>
    <tr>
        <td class="value-cell" colspan="6">
            <div style="border: 1px solid #cccccc; padding: 8px;">
                <div style="font-weight: bold; font-size: 10px; color: #b11217; margin-bottom: 4px;">
                    CLEAN RECORD HIGHLIGHTS
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(is_numeric($accidentFreeYears) && $accidentFreeYears > 0): ?>
                    <div style="font-size: 9px; margin: 2px 0;">Accident free years: <?php echo e($accidentFreeYears); ?></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasCleanRecord): ?>
                    <div style="font-size: 9px; margin: 2px 0;">Clean driving record</div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </td>
    </tr>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <tr>
        <td class="section-header" colspan="6" style="color: #b11217;">PREVIOUS EMPLOYMENT ABROAD</td>
    </tr>
    <tr class="exp-header-row">
        <td style="background-color: #f5f5f5; font-weight: bold; text-align: center; font-size: 9px;">PERIOD</td>
        <td style="background-color: #f5f5f5; font-weight: bold; text-align: center; font-size: 9px;" colspan="3">POSITION</td>
        <td style="background-color: #f5f5f5; font-weight: bold; text-align: center; font-size: 9px;" colspan="2">COUNTRY</td>
    </tr>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $experienceArray; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $exp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
    <tr>
        <td style="text-align: center;">
            <?php
                $startDate = $exp['start_date'] ?? '';
                $endDate = $exp['end_date'] ?? '';
                $period = $startDate;
                if ($endDate) $period .= ' - ' . $endDate;
            ?>
            <?php echo e($period); ?>

        </td>
        <td style="text-align: center;" colspan="3"><?php echo e($exp['role_title'] ?? $exp['title'] ?? ''); ?></td>
        <td style="text-align: center;" colspan="2"><?php echo e($exp['location'] ?? $exp['country'] ?? ''); ?></td>
    </tr>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    <tr>
        <td colspan="6" style="text-align: center;">No experience provided</td>
    </tr>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <tr>
        <td class="section-header" colspan="6" style="color: #b11217;">EDUCATIONAL QUALIFICATIONS</td>
    </tr>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $educationArray; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $edu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
    <tr>
        <td style="text-align: center;" colspan="2"><?php echo e($edu['graduation_year'] ?? $edu['year'] ?? ''); ?></td>
        <td style="text-align: center;" colspan="2"><?php echo e($edu['degree_title'] ?? $edu['degree'] ?? $edu['qualification'] ?? ''); ?></td>
        <td style="text-align: center;" colspan="2"><?php echo e($edu['institution_name'] ?? $edu['school'] ?? ''); ?></td>
    </tr>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    <tr>
        <td colspan="6" style="text-align: center;">No education provided</td>
    </tr>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <tr>
        <td class="section-header" colspan="6" style="color: #b11217;">LANGUAGES</td>
    </tr>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $languageArray; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
    <tr>
        <td colspan="2"><?php echo e(strtoupper($lang['language_name'] ?? $lang['language'] ?? '')); ?></td>
        <td colspan="4"><?php echo e($lang['proficiency_level'] ?? $lang['level'] ?? ''); ?></td>
    </tr>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    <tr>
        <td colspan="6" style="text-align: center;">No languages specified</td>
    </tr>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <tr>
        <td class="section-header" colspan="6" style="color: #b11217;">REMARKS(M)</td>
    </tr>
    <tr>
        <td style="text-align: center; font-weight: bold; padding: 10px; font-size: 11px;" colspan="6">READY TO WORK</td>
    </tr>
</table>

</body>
</html>
<?php /**PATH /home/abdulbas/public_html/ogsmanpower.com/backend/resources/views/pdf/driver.blade.php ENDPATH**/ ?>