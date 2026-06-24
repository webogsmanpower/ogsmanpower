<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employer;
use App\Models\User;
use App\Models\ContractTemplate;

class ContractTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all employers
        $employers = Employer::all();
        
        foreach ($employers as $employer) {
            // Check if employer already has templates
            $existingTemplates = ContractTemplate::where('employer_id', $employer->id)->count();
            if ($existingTemplates > 0) {
                continue; // Skip employers who already have templates
            }
            
            // Create default templates for each employer
            $this->createStandardEmploymentContract($employer);
            $this->createProbationContract($employer);
            $this->createInternshipContract($employer);
        }
    }
    
    private function createStandardEmploymentContract(Employer $employer): void
    {
        ContractTemplate::create([
            'employer_id' => $employer->id,
            'created_by' => $employer->user_id,
            'name' => 'Standard Employment Contract',
            'description' => 'Standard full-time employment contract with comprehensive terms',
            'content' => $this->getStandardContractContent(),
            'is_default' => true,
            'is_active' => true,
        ]);
    }
    
    private function createProbationContract(Employer $employer): void
    {
        ContractTemplate::create([
            'employer_id' => $employer->id,
            'created_by' => $employer->user_id,
            'name' => 'Probation Period Contract',
            'description' => 'Employment contract with probation period',
            'content' => $this->getProbationContractContent(),
            'is_default' => false,
            'is_active' => true,
        ]);
    }
    
    private function createInternshipContract(Employer $employer): void
    {
        ContractTemplate::create([
            'employer_id' => $employer->id,
            'created_by' => $employer->user_id,
            'name' => 'Internship Agreement',
            'description' => 'Internship agreement for trainees and interns',
            'content' => $this->getInternshipContractContent(),
            'is_default' => false,
            'is_active' => true,
        ]);
    }
    
    private function getStandardContractContent(): string
    {
        return '<h2>EMPLOYMENT CONTRACT</h2>
<p><strong>Employment Contract</strong></p>
<p>This Employment Contract is made and entered into on {{start_date}} ("Effective Date") by and between:</p>
<p><strong>Employer:</strong> {{company_name}}, a company organized under the laws of {{company_country}}, with its principal place of business at {{company_address}} (hereinafter referred to as the "Employer").</p>
<p><strong>Employee:</strong> {{candidate_name}}, residing at {{candidate_address}} (hereinafter referred to as the "Employee").</p>
<h3>1. Position and Duties</h3>
<p>The Employer agrees to employ the Employee in the position of <strong>{{job_title}}</strong>. The Employee shall perform such duties and responsibilities as may be assigned by the Employer, including but not limited to those described in the job description attached as Appendix A.</p>
<p>The Employee shall report to <strong>{{reporting_to}}</strong> and shall be based at <strong>{{work_location}}</strong>.</p>
<h3>2. Compensation</h3>
<p>The Employee shall receive a base salary of <strong>{{salary}} {{salary_currency}} per {{salary_period}}</strong>, payable in accordance with the Employer\'s standard payroll practices.</p>
<p>The salary is subject to applicable statutory deductions and taxes as required by law.</p>
<h3>3. Working Hours</h3>
<p>The Employee\'s normal working hours shall be [e.g., 8 hours per day, 40 hours per week], from [e.g., 9:00 AM to 6:00 PM], Sunday through Thursday.</p>
<h3>4. Duration of Employment</h3>
<p>This contract shall commence on {{start_date}} and shall continue until terminated by either party in accordance with the terms herein.</p>
{{#if end_date}}
<p>The contract is for a fixed term ending on {{end_date}}.</p>
{{/if}}
<h3>5. Probation Period</h3>
<p>The Employee shall be subject to a probation period of {{probation_months}} months from the Effective Date. During this period, the Employer may terminate the employment with {{notice_period_days}} days\' notice.</p>
<h3>6. Termination</h3>
<p>After the probation period, either party may terminate this contract by giving {{notice_period_days}} days written notice to the other party.</p>
<p>The Employer reserves the right to terminate the employment immediately for cause, including but not limited to gross misconduct, breach of confidentiality, or violation of company policies.</p>
<h3>7. Confidentiality</h3>
<p>The Employee shall maintain strict confidentiality regarding all proprietary information, trade secrets, and business matters of the Employer, both during and after employment.</p>
<h3>8. Governing Law</h3>
<p>This contract shall be governed by and construed in accordance with the laws of {{company_country}}.</p>
<h3>9. Entire Agreement</h3>
<p>This contract constitutes the entire agreement between the parties and supersedes all prior negotiations, understandings, and agreements.</p>
<p>IN WITNESS WHEREOF, the parties have executed this contract as of the Effective Date.</p>
<p><strong>For the Employer:</strong></p>
<p>_________________________<br>{{signatory_name}}<br>{{signatory_title}}<br>{{company_name}}</p>
<p><strong>For the Employee:</strong></p>
<p>_________________________<br>{{candidate_name}}<br>Date: _______________</p>';
    }
    
    private function getProbationContractContent(): string
    {
        return '<h2>PROBATION EMPLOYMENT CONTRACT</h2>
<p>This Probation Employment Contract is made on {{start_date}} between:</p>
<p><strong>Employer:</strong> {{company_name}}, {{company_address}}</p>
<p><strong>Employee:</strong> {{candidate_name}}, {{candidate_address}}</p>
<h3>1. Position</h3>
<p>The Employee is hired as <strong>{{job_title}}</strong> on a probation basis for a period of {{probation_months}} months.</p>
<h3>2. Salary</h3>
<p>During probation, the Employee shall receive {{salary}} {{salary_currency}} per {{salary_period}}.</p>
<h3>3. Probation Terms</h3>
<p>The probation period will allow both parties to assess suitability for continued employment. The Employer may terminate employment during probation with {{notice_period_days}} days notice.</p>
<p>Upon successful completion of probation, the Employee may be offered a permanent employment contract.</p>
<h3>4. Terms</h3>
<p>All other terms and conditions shall be as per the Employer\'s standard policies and applicable laws.</p>
<p>Signatures:</p>
<p>_________________________<br>Employer Representative</p>
<p>_________________________<br>Employee</p>';
    }
    
    private function getInternshipContractContent(): string
    {
        return '<h2>INTERNSHIP AGREEMENT</h2>
<p>This Internship Agreement is made on {{start_date}} between:</p>
<p><strong>Company:</strong> {{company_name}}, {{company_address}}</p>
<p><strong>Intern:</strong> {{candidate_name}}, {{candidate_address}}</p>
<h3>1. Internship Position</h3>
<p>The intern will be engaged as <strong>{{job_title}}</strong> for learning and training purposes.</p>
<h3>2. Duration</h3>
<p>The internship will run from {{start_date}} to {{end_date}}.</p>
<h3>3. Stipend</h3>
<p>A stipend of {{salary}} {{salary_currency}} per {{salary_period}} will be provided to cover expenses.</p>
<h3>4. Learning Objectives</h3>
<p>The internship focuses on providing practical experience in [relevant field/industry].</p>
<h3>5. Terms</h3>
<p>This is a training agreement and does not constitute an employment relationship. Either party may terminate with {{notice_period_days}} days notice.</p>
<p>Signatures:</p>
<p>_________________________<br>Company Representative</p>
<p>_________________________<br>Intern</p>';
    }
}
