<?php

namespace App\Console\Commands;

use App\Models\Seeker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RecalculateSeekerProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seeker:recalculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate profile completion scores for all seekers and verify image files exist';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $seekers = Seeker::with(['user', 'resume'])->get();
        foreach ($seekers as $seeker) {
            $score = 0;
            // 1. Check fields
            if (!empty($seeker->first_name)) $score += 10;
            
            // Check phone on user if not on seeker
            $phone = $seeker->phone ?? $seeker->user->mobile ?? null;
            if (!empty($phone)) $score += 10;
            
            // Experience (from Resume)
            $experienceCount = 0;
            if ($seeker->resume && !empty($seeker->resume->work_experience) && is_array($seeker->resume->work_experience)) {
                $experienceCount = count($seeker->resume->work_experience);
            }
            if ($experienceCount > 0) $score += 20;

            // Education (from Resume)
            $educationCount = 0;
            if ($seeker->resume && !empty($seeker->resume->education) && is_array($seeker->resume->education)) {
                $educationCount = count($seeker->resume->education);
            }
            if ($educationCount > 0) $score += 20;

            // Skills (from Seeker directly)
            $skillsCount = 0;
            if (!empty($seeker->skills) && is_array($seeker->skills)) {
                $skillsCount = count($seeker->skills);
            }
            if ($skillsCount > 0) $score += 10;
            
            // 2. Check Image
            // Sync from Resume if missing in Seeker
            if (empty($seeker->profile_image_path) && $seeker->resume && !empty($seeker->resume->basic_information['profile_photo'])) {
                $resumePhoto = $seeker->resume->basic_information['profile_photo'];
                // Clean path: remove leading /storage/ or storage/
                $cleanPath = preg_replace('#^/?(storage/)?#', '', $resumePhoto);
                
                if (Storage::disk('public')->exists($cleanPath)) {
                    $seeker->profile_image_path = $cleanPath;
                    $this->info("Synced profile image from Resume for Seeker {$seeker->id}");
                } else {
                     // Try with original path just in case
                     if (Storage::disk('public')->exists($resumePhoto)) {
                         $seeker->profile_image_path = $resumePhoto;
                         $this->info("Synced profile image (raw) from Resume for Seeker {$seeker->id}");
                     }
                }
            }

            if (!empty($seeker->profile_image_path)) {
                $score += 10;
                // Verify file exists
                if (!Storage::disk('public')->exists($seeker->profile_image_path)) {
                    $this->error("Image missing for Seeker ID {$seeker->id}: {$seeker->profile_image_path}");
                }
            }

            $seeker->profile_completion = $score;
            $seeker->save();
            $this->info("Updated Seeker {$seeker->id}: {$score}%");
        }
    }
}
