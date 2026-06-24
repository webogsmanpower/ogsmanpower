<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VisaStatus;
use App\Models\VisaStep;

class VisaStepsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all visa statuses that don't have steps
        $visaStatusesWithoutSteps = VisaStatus::whereDoesntHave('steps')->get();
        
        foreach ($visaStatusesWithoutSteps as $visaStatus) {
            // Create all workflow steps for this visa status
            VisaStep::createAllForVisaStatus($visaStatus->id);
            
            // Update current step status based on visa status
            $currentStep = VisaStep::where('visa_status_id', $visaStatus->id)
                ->where('step_name', $visaStatus->current_step)
                ->first();
                
            if ($currentStep) {
                $currentStep->update(['status' => 'completed']);
            }
        }
        
        $this->command->info("Created visa steps for {$visaStatusesWithoutSteps->count()} visa statuses");
    }
}
