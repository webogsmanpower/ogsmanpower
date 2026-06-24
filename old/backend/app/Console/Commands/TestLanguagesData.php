<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Seeker;
use App\Models\SeekerResume;

class TestLanguagesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:languages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test languages data in database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing languages data...');
        
        // Get all seekers with resumes
        $seekers = Seeker::with('resume')->get();
        
        $foundResume = false;
        foreach ($seekers as $seeker) {
            if ($seeker->resume && !empty($seeker->resume->languages)) {
                $foundResume = true;
                break;
            }
        }
        
        if (!$foundResume) {
            $this->info('No seeker with languages found. Checking all seekers...');
            // Show all seekers and their languages
            foreach ($seekers as $seeker) {
                if ($seeker->resume) {
                    $this->info("\nSeeker ID: " . $seeker->id);
                    $this->info('Languages: ' . json_encode($seeker->resume->languages));
                    $this->info('Primary Language: ' . $seeker->resume->primary_language);
                }
            }
            return;
        }
        
        $this->info('Seeker ID: ' . $seeker->id);
        
        $resume = $seeker->resume;
        if (!$resume) {
            $this->error('No resume found for seeker');
            return;
        }
        
        $this->info('Resume ID: ' . $resume->id);
        
        // Check languages field
        $languages = $resume->languages;
        $this->info('Languages field type: ' . gettype($languages));
        $this->info('Languages field is array: ' . (is_array($languages) ? 'YES' : 'NO'));
        $this->info('Languages field value: ' . json_encode($languages));
        
        // Check primary_language field
        $primaryLanguage = $resume->primary_language;
        $this->info('Primary language: ' . $primaryLanguage);
        
        // Check if languages field exists in database
        $this->info('Raw resume data from DB:');
        $rawResume = SeekerResume::where('seeker_id', $seeker->id)->first();
        if ($rawResume) {
            $this->info('Raw languages column: ' . json_encode($rawResume->languages));
            $this->info('Raw primary_language column: ' . $rawResume->primary_language);
        }
        
        // Test the API response structure
        $this->info("\nSimulating API response...");
        $resumeData = $resume->toArray();
        $this->info('Resume toArray() languages: ' . json_encode($resumeData['languages'] ?? 'NULL'));
        
        return Command::SUCCESS;
    }
}
