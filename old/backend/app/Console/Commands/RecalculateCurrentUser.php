<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RecalculateCurrentUser extends Command
{
    protected $signature = 'seeker:recalculate-current {email} {--save} {--debug}';
    protected $description = 'Recalculate profile completion for a specific user';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found with email: {$email}");
            return;
        }

        $seeker = $user->seeker;
        if (!$seeker) {
            $this->error("Seeker profile not found for user: {$email}");
            return;
        }

        $score = $seeker->calculateProfileCompletion();
        
        if ($this->option('save')) {
            $seeker->save();
            $this->info("Recalculated and saved profile for {$email}. New score: {$score}%");
        } else if ($this->option('debug')) {
            $this->info("Debug calculation for {$email}:");
            $this->table(
                ['Field', 'Value', 'Points'],
                [
                    ['First Name', $seeker->first_name, !empty($seeker->first_name) ? 5 : 0],
                    ['Last Name', $seeker->last_name, !empty($seeker->last_name) ? 5 : 0],
                    ['Mobile', $user->mobile, !empty($user->mobile) ? 5 : 0],
                    ['Profile Image', $seeker->profile_image_path, !empty($seeker->profile_image_path) ? 10 : 0],
                    ['Date of Birth', $seeker->date_of_birth, !empty($seeker->date_of_birth) ? 5 : 0],
                    ['Current Location', $seeker->current_location, !empty($seeker->current_location) ? 5 : 0],
                    ['Bio', $seeker->bio, !empty($seeker->bio) ? 5 : 0],
                    ['Profession', $seeker->profession, !empty($seeker->profession) ? 10 : 0],
                    ['Headline', $seeker->headline, !empty($seeker->headline) ? 10 : 0],
                    ['Experience Years', $seeker->experience_years, !empty($seeker->experience_years) ? 10 : 0],
                    ['Skills', json_encode($seeker->skills), !empty($seeker->skills) ? 10 : 0],
                    ['Resume Path', $seeker->resume_path, !empty($seeker->resume_path) ? 10 : 0],
                    ['License Number', $seeker->license_number, !empty($seeker->license_number) ? 5 : 0],
                    ['License Expiry', $seeker->license_expiry_date, !empty($seeker->license_expiry_date) ? 5 : 0],
                ]
            );
            $this->info("Calculated score: {$score}%");
        } else {
            $this->info("Recalculated profile for {$email}. New score: {$score}% (use --save to persist)");
        }
    }
}
