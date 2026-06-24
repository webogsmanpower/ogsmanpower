<?php

namespace App\Console\Commands;

use App\Models\SeekerResume;
use Illuminate\Console\Command;

class ConvertSkillsFormat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'skills:convert-format';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert skills from old format {name, rating, description} to new format {skill, category, proficiency}';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting skills format conversion...');
        
        $resumes = SeekerResume::whereNotNull('skills')->get();
        $convertedCount = 0;
        $skippedCount = 0;

        foreach ($resumes as $resume) {
            $skills = $resume->skills;
            
            if (!is_array($skills) || empty($skills)) {
                $skippedCount++;
                continue;
            }

            // Check if already in new format (has 'skill' field)
            $firstSkill = $skills[0] ?? null;
            if (is_array($firstSkill) && isset($firstSkill['skill'])) {
                $this->line("Resume ID {$resume->id}: Already in new format, skipping");
                $skippedCount++;
                continue;
            }

            // Convert from old format to new format
            $convertedSkills = [];
            foreach ($skills as $skill) {
                if (is_string($skill)) {
                    // Simple string format
                    $convertedSkills[] = [
                        'skill' => $skill,
                        'category' => null,
                        'proficiency' => null,
                    ];
                } elseif (is_array($skill)) {
                    // Object format - extract skill name from various possible fields
                    $skillName = $skill['skill'] ?? $skill['name'] ?? $skill['skill_name'] ?? $skill['primary_skill'] ?? null;
                    
                    if ($skillName) {
                        $convertedSkills[] = [
                            'skill' => $skillName,
                            'category' => $skill['category'] ?? null,
                            'proficiency' => $skill['proficiency'] ?? null,
                        ];
                    }
                }
            }

            if (!empty($convertedSkills)) {
                $resume->skills = $convertedSkills;
                $resume->save();
                $skillCount = count($convertedSkills);
                $this->info("Resume ID {$resume->id}: Converted {$skillCount} skills");
                $convertedCount++;
            } else {
                $this->warn("Resume ID {$resume->id}: No valid skills found");
                $skippedCount++;
            }
        }

        $this->info("\n=== Conversion Complete ===");
        $this->info("Converted: {$convertedCount} resumes");
        $this->info("Skipped: {$skippedCount} resumes");
        
        return 0;
    }
}
