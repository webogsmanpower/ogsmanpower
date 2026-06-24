<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume - {{ $resumeData['first_name'] ?? '' }} {{ $resumeData['last_name'] ?? '' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        secondary: '#64748b',
                        accent: '#f59e0b',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        serif: ['Georgia', 'serif'],
                    }
                }
            }
        }
    </script>
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
</head>
<body class="bg-white text-gray-900 p-6 max-w-4xl mx-auto">
    <!-- Header Section with Modern Layout -->
    <header class="mb-8 pb-6 border-b-2 border-gray-200">
        <div class="flex items-start gap-6">
            @if($profilePhoto)
            <div class="flex-shrink-0">
                <img src="{{ $profilePhoto }}" 
                     alt="Profile Photo" 
                     class="w-24 h-28 object-cover rounded-lg border-2 border-gray-300 shadow-md">
            </div>
            @endif
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    {{ $resumeData['first_name'] ?? '' }} {{ $resumeData['last_name'] ?? '' }}
                </h1>
                @if(!empty($resumeData['headline']))
                    <p class="text-lg text-primary font-medium mb-3">{{ $resumeData['headline'] }}</p>
                @endif
                
                <div class="flex flex-wrap gap-3 text-sm text-secondary">
                    @if(!empty($resumeData['basic_information']['email']))
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            {{ $resumeData['basic_information']['email'] }}
                        </div>
                    @endif
                    @if(!empty($resumeData['basic_information']['phone']))
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            {{ $resumeData['basic_information']['phone'] }}
                        </div>
                    @endif
                    @if(!empty($resumeData['current_location']))
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ $resumeData['current_location'] }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- Professional Summary -->
    @if(!empty($resumeData['professional_summary']))
    <section class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4 pb-2 border-b border-gray-300">Professional Summary</h2>
        <div class="text-gray-700 leading-relaxed">
            @if(is_array($resumeData['professional_summary']))
                @if(!empty($resumeData['professional_summary']['career_objective']))
                    <p>{{ $resumeData['professional_summary']['career_objective'] }}</p>
                @endif
            @else
                <p>{{ $resumeData['professional_summary'] }}</p>
            @endif
        </div>
    </section>
    @endif

    <!-- Work Experience -->
    @if(!empty($resumeData['work_experience']) && is_array($resumeData['work_experience']))
    <section class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4 pb-2 border-b border-gray-300">Work Experience</h2>
        <div class="space-y-6">
            @foreach($resumeData['work_experience'] as $work)
            <div class="relative pl-6 border-l-2 border-gray-200">
                <div class="absolute -left-2 top-0 w-4 h-4 bg-primary rounded-full border-2 border-white"></div>
                <div class="mb-2">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $work['job_title'] ?? '' }}</h3>
                    <div class="flex flex-wrap justify-between items-start gap-2">
                        <div class="text-primary font-medium">{{ $work['company'] ?? '' }}</div>
                        @if(!empty($work['start_date']) || !empty($work['end_date']))
                            <div class="text-sm text-secondary bg-gray-100 px-2 py-1 rounded">
                                @if(!empty($work['start_date']))
                                    {{ date('M Y', strtotime($work['start_date'])) }}
                                @endif
                                @if(!empty($work['end_date']))
                                    - {{ date('M Y', strtotime($work['end_date'])) }}
                                @else
                                    - Present
                                @endif
                            </div>
                        @endif
                    </div>
                    @if(!empty($work['location']))
                        <div class="text-sm text-secondary">{{ $work['location'] }}</div>
                    @endif
                </div>
                @if(!empty($work['description']))
                    <div class="text-gray-700 text-sm leading-relaxed">{{ $work['description'] }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Education -->
    @if(!empty($resumeData['education']) && is_array($resumeData['education']))
    <section class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4 pb-2 border-b border-gray-300">Education</h2>
        <div class="space-y-4">
            @foreach($resumeData['education'] as $edu)
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $edu['degree'] ?? '' }}</h3>
                    <div class="text-primary font-medium">{{ $edu['institution'] ?? '' }}</div>
                    @if(!empty($edu['location']))
                        <div class="text-sm text-secondary">{{ $edu['location'] }}</div>
                    @endif
                </div>
                @if(!empty($edu['start_date']) || !empty($edu['end_date']))
                    <div class="text-sm text-secondary bg-gray-100 px-2 py-1 rounded whitespace-nowrap">
                        @if(!empty($edu['start_date'])){{ date('M Y', strtotime($edu['start_date'])) }}@endif
                        @if(!empty($edu['end_date'])) - {{ date('M Y', strtotime($edu['end_date'])) }}@endif
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Skills -->
    @if(!empty($resumeData['skills']) && is_array($resumeData['skills']))
    <section class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4 pb-2 border-b border-gray-300">Skills</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($resumeData['skills'] as $skill)
                @if(!empty($skill['name']))
                    <span class="px-3 py-1 bg-primary/10 text-primary rounded-full text-sm font-medium">
                        {{ $skill['name'] }}
                    </span>
                @endif
            @endforeach
        </div>
    </section>
    @endif

    <!-- Languages -->
    @if(!empty($resumeData['languages']) && is_array($resumeData['languages']))
    <section class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4 pb-2 border-b border-gray-300">Languages</h2>
        <div class="grid grid-cols-2 gap-3">
            @foreach($resumeData['languages'] as $lang)
                <div class="flex justify-between items-center">
                    <span class="text-gray-700">{{ $lang['name'] ?? '' }}</span>
                    <span class="text-sm text-secondary bg-gray-100 px-2 py-1 rounded">{{ $lang['proficiency'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Certifications -->
    @if(!empty($resumeData['certifications']) && is_array($resumeData['certifications']))
    <section class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4 pb-2 border-b border-gray-300">Certifications</h2>
        <div class="space-y-4">
            @foreach($resumeData['certifications'] as $cert)
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $cert['name'] ?? '' }}</h3>
                    <div class="text-primary font-medium">{{ $cert['issuing_organization'] ?? '' }}</div>
                </div>
                @if(!empty($cert['date']))
                    <div class="text-sm text-secondary bg-gray-100 px-2 py-1 rounded whitespace-nowrap">
                        {{ date('M Y', strtotime($cert['date'])) }}
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </section>
    @endif
</body>
</html>
