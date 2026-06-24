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
<?php
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
?>
<div class="layout">
    <div class="column">
        <div class="header">
            <h1><?php echo e(trim(($seeker->first_name ?? '') . ' ' . ($seeker->last_name ?? ''))); ?></h1>
            <div><?php echo e($basic['email'] ?? $seeker->user->email ?? ''); ?> | <?php echo e($basic['phone'] ?? $seeker->user->mobile ?? ''); ?></div>
            <div><?php echo e($basic['city'] ?? $seeker->current_location ?? ''); ?></div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($summaryEn): ?>
            <div class="section">
                <div class="section-title">Professional Summary</div>
                <div><?php echo e($summaryEn); ?></div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php $experiences = $resume->work_experience ?? []; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($experiences)): ?>
            <div class="section">
                <div class="section-title">Experience</div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $experiences; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $experience): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <div class="entry">
                        <div class="role"><?php echo e($experience['title'] ?? $experience['role_title'] ?? ''); ?></div>
                        <div class="meta">
                            <?php echo e($experience['company'] ?? $experience['company_name'] ?? ''); ?>

                            <?php
                                $start = $experience['start_date'] ? \Carbon\Carbon::parse($experience['start_date'])->format('M Y') : null;
                                $end = ($experience['current'] ?? false)
                                    ? 'Present'
                                    : ($experience['end_date'] ? \Carbon\Carbon::parse($experience['end_date'])->format('M Y') : null);
                            ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($start || $end): ?>
                                | <?php echo e(trim(($start ?? '') . ' - ' . ($end ?? ''))); ?>

                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($experience['description'])): ?>
                            <div><?php echo e($experience['description']); ?></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php $education = $resume->education ?? []; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($education)): ?>
            <div class="section">
                <div class="section-title">Education</div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $education; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <div class="entry">
                        <div class="role"><?php echo e($entry['degree'] ?? $entry['degree_title'] ?? ''); ?></div>
                        <div class="meta"><?php echo e($entry['institution'] ?? $entry['institution_name'] ?? ''); ?>

                            <?php
                                $start = $entry['start_date'] ? \Carbon\Carbon::parse($entry['start_date'])->format('Y') : null;
                                $end = $entry['end_date'] ? \Carbon\Carbon::parse($entry['end_date'])->format('Y') : null;
                            ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($start || $end): ?>
                                | <?php echo e(trim(($start ?? '') . ' - ' . ($end ?? ''))); ?>

                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php
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
        ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($skillsList)): ?>
            <div class="section">
                <div class="section-title">Skills</div>
                <ul>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $skillsList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <li><?php echo e($skill); ?></li>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </ul>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <div class="column column-rtl">
        <div class="header">
            <h1><?php echo e(trim(($seeker->first_name ?? '') . ' ' . ($seeker->last_name ?? ''))); ?></h1>
            <div><?php echo e($translations['basic_information']['job_title'] ?? $resumeData['headline_' . $targetLocale] ?? $resumeData['headline_ar'] ?? $resumeData['headline'] ?? $resume->profession ?? ''); ?></div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($summaryAr): ?>
            <div class="section">
                <div class="section-title section-title-rtl">الملخص المهني</div>
                <div><?php echo e($summaryAr); ?></div>
            </div>
        <?php elseif($summaryEn): ?>
            <div class="section">
                <div class="section-title section-title-rtl">الملخص المهني</div>
                <div><?php echo e($summaryEn); ?></div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($translations['work_experience'])): ?>
            <div class="section">
                <div class="section-title section-title-rtl">الخبرة العملية</div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $translations['work_experience']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $translatedExp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <?php $original = $experiences[$index] ?? []; ?>
                    <div class="entry">
                        <div class="role"><?php echo e($translatedExp['title'] ?? ($original['title'] ?? $original['role_title'] ?? '')); ?></div>
                        <div class="meta">
                            <?php echo e($translatedExp['company'] ?? $original['company'] ?? $original['company_name'] ?? ''); ?>

                            <?php
                                $start = $original['start_date'] ? \Carbon\Carbon::parse($original['start_date'])->format('M Y') : null;
                                $end = ($original['current'] ?? false)
                                    ? __('Present')
                                    : ($original['end_date'] ? \Carbon\Carbon::parse($original['end_date'])->format('M Y') : null);
                            ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($start || $end): ?>
                                | <?php echo e(trim(($start ?? '') . ' - ' . ($end ?? ''))); ?>

                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($translatedExp['description'])): ?>
                            <div><?php echo e($translatedExp['description']); ?></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php elseif(!empty($experiences)): ?>
            <div class="section">
                <div class="section-title section-title-rtl">الخبرة العملية</div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $experiences; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $experience): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <div class="entry">
                        <div class="role"><?php echo e($experience['title'] ?? $experience['role_title'] ?? ''); ?></div>
                        <div class="meta">
                            <?php echo e($experience['company'] ?? $experience['company_name'] ?? ''); ?>

                            <?php
                                $start = $experience['start_date'] ? \Carbon\Carbon::parse($experience['start_date'])->format('M Y') : null;
                                $end = ($experience['current'] ?? false)
                                    ? __('Present')
                                    : ($experience['end_date'] ? \Carbon\Carbon::parse($experience['end_date'])->format('M Y') : null);
                            ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($start || $end): ?>
                                | <?php echo e(trim(($start ?? '') . ' - ' . ($end ?? ''))); ?>

                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($experience['description'])): ?>
                            <div><?php echo e($experience['description']); ?></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php $education = $resume->education ?? []; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($translations['education'])): ?>
            <div class="section">
                <div class="section-title section-title-rtl">التعليم</div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $translations['education']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $translatedEdu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <?php $original = $education[$index] ?? []; ?>
                    <div class="entry">
                        <div class="role"><?php echo e($translatedEdu['degree'] ?? ($original['degree'] ?? $original['degree_title'] ?? '')); ?></div>
                        <div class="meta">
                            <?php echo e($translatedEdu['institution'] ?? $original['institution'] ?? $original['institution_name'] ?? ''); ?>

                            <?php
                                $start = $original['start_date'] ? \Carbon\Carbon::parse($original['start_date'])->format('Y') : null;
                                $end = $original['end_date'] ? \Carbon\Carbon::parse($original['end_date'])->format('Y') : null;
                            ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($start || $end): ?>
                                | <?php echo e(trim(($start ?? '') . ' - ' . ($end ?? ''))); ?>

                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php elseif(!empty($education)): ?>
            <div class="section">
                <div class="section-title section-title-rtl">التعليم</div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $education; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <div class="entry">
                        <div class="role"><?php echo e($entry['degree'] ?? $entry['degree_title'] ?? ''); ?></div>
                        <div class="meta"><?php echo e($entry['institution'] ?? $entry['institution_name'] ?? ''); ?>

                            <?php
                                $start = $entry['start_date'] ? \Carbon\Carbon::parse($entry['start_date'])->format('Y') : null;
                                $end = $entry['end_date'] ? \Carbon\Carbon::parse($entry['end_date'])->format('Y') : null;
                            ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($start || $end): ?>
                                | <?php echo e(trim(($start ?? '') . ' - ' . ($end ?? ''))); ?>

                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php
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
        ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($skillsAr)): ?>
            <div class="section">
                <div class="section-title section-title-rtl">المهارات</div>
                <ul>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $skillsAr; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <li><?php echo e($skill); ?></li>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </ul>
            </div>
        <?php elseif(!empty($skillsList)): ?>
            <div class="section">
                <div class="section-title section-title-rtl">المهارات</div>
                <ul>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $skillsList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <li><?php echo e($skill); ?></li>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </ul>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
</body>
</html>
<?php /**PATH /home/abdulbas/public_html/ogsmanpower.com/backend/resources/views/pdf/bilingual.blade.php ENDPATH**/ ?>