<?php

namespace App\Services\Website\Company;

use App\Http\Traits\JobAble;
use App\Models\Admin;
use App\Models\CandidateJobAlert;
use App\Models\CompanyAttributeTranslation;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobCategoryTranslation;
use App\Models\JobRole;
use App\Models\JobRoleTranslation;
use App\Notifications\Admin\NewJobAvailableNotification;
use App\Notifications\Website\Candidate\RelatedJobNotification;
use App\Notifications\Website\Company\JobCreatedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;


class CompanyStoreService
{
    use JobAble;

    /**
     * Store job
     *
     * @return Job $jobCreated
     */
    public function execute($request): Job
    {
        // Check if user has reached the job limit
        storePlanInformation();
        $userPlan = session('user_plan');

        if ((int) $userPlan->job_limit < 1) {
            session()->flash('error', __('you_have_reached_your_plan_limit_please_upgrade_your_plan'));

            return redirect()->route('company.plan');
        }

        $min = $request->min_salary;
        $max = $request->max_salary;

        $request->validate([
            'min_salary' => 'nullable|numeric|between:0,' . $max,
            'max_salary' => 'nullable|numeric|min:' . $min,
        ]);

        if ($request->apply_on === 'custom_url') {
            $request->validate([
                'apply_url' => 'required|url',
            ]);
        }
        if ($request->apply_on === 'email') {
            $request->validate([
                'apply_email' => 'required|email',
            ]);
        }

        // Highlight & featured
        $highlight = $request->badge == 'highlight' ? 1 : 0;
        $featured = $request->badge == 'featured' ? 1 : 0;

        // Job Category
        $job_category_request = $request->category_id;

        $job_category = JobCategoryTranslation::where('job_category_id', $job_category_request)->orWhere('name', $job_category_request)->first();
        if (! $job_category) {
            $new_job_category = JobCategory::create(['name' => $job_category_request]);

            $languages = loadLanguage();
            foreach ($languages as $language) {
                $new_job_category->translateOrNew($language->code)->name = $job_category_request;
            }
            $new_job_category->save();

            $job_category_id = $new_job_category->id;
        } else {
            $job_category_id = $job_category->job_category_id;
        }

        // Job Role
        $job_role_request = $request->role_id;

        $job_category = JobRoleTranslation::where('job_role_id', $job_role_request)->orWhere('name', $job_role_request)->first();

        if (! $job_category) {
            $new_job_role = JobRole::create(['name' => $job_role_request]);

            $languages = loadLanguage();
            foreach ($languages as $language) {
                $new_job_role->translateOrNew($language->code)->name = $job_role_request;
            }
            $new_job_role->save();

            $job_role_id = $new_job_role->id;
        } else {
            $job_role_id = $job_category->job_role_id;
        }

        $deadline = Carbon::parse(now()->addDays(setting('job_deadline_expiration_limit')))->format('Y-m-d');
        if ($request->custom_title !== null) {
            $title = $request->custom_title;
        } else {
            $title = $request->title;
        }

        $ip = $request->ip();

        if (app()->environment('local')) {
            $ip = '8.8.8.8'; // Example public IP for testing
        }

        $location = Cache::remember("ip_location_{$ip}", now()->addMinutes(30), function () use ($ip) {
            return Http::get("http://ip-api.com/json/{$ip}")->json();
        });

        $country = $location['country'] ?? null;
        $city = $location['city'] ?? null;

        $vpnCheck = Cache::remember("vpn_check_{$ip}", now()->addMinutes(30), function () use ($ip) {
            return Http::get("https://ipqualityscore.com/api/json/ip/YOUR_API_KEY/{$ip}")->json();
        });

        $isVpn = $vpnCheck['vpn'] ?? false; // Detect VPN usage

        if ($isVpn) {
            return response()->json([
                'message' => 'VPN usage detected. Please disable VPN to continue.',
                'ip' => $ip,
                'location' => $location
            ], 403);
        }

        $jobCreated = Job::create([
            'title' => $title,
            'company_id' => currentCompany()->id,
            'category_id' => $job_category_id,
            'role_id' => $job_role_id,
            'education_id' => $request->education,
            'experience_id' => $request->experience,
            'salary_mode' => $request->salary_mode,
            'custom_salary' => $request->custom_salary,
            'min_salary' => $request->min_salary,
            'max_salary' => $request->max_salary,
            'salary_type_id' => $request->salary_type,
            'deadline' => $deadline,
            'job_type_id' => $request->job_type,
            'vacancies' => $request->vacancies,
            'apply_on' => $request->apply_on,
            'apply_email' => $request->apply_email ?? null,
            'apply_url' => $request->apply_url ?? null,
            'description' => $request->description,
            'featured' => $featured,
            'highlight' => $highlight,
            'is_remote' => $request->is_remote ?? 0,
            'status' => setting('job_auto_approved') ? 'active' : 'pending',


            'currency'=>$request->currency,
            'min_age' => $request->min_age,
            'max_age' => $request->max_age,
            'gender' => $request->gender,
            'city_limit' => $request->city_limit ?? 0,
            'education_limit' => $request->education_limit ?? 0,
            'experience_limit' => $request->experience_limit ?? 0,
            'age_limit' => $request->age_limit ?? 0,
            'gender_limit' => $request->gender_limit ?? 0,
            'ip_address' => $ip,
            'ip_country' => $country,
        ]);

        // Location
        updateMap($jobCreated);

        // Question
        if (isset($request->companyQuestions) && $request->has('companyQuestions')) {
            $jobCreated->questions()->attach($request->get('companyQuestions'));
        }

        // Benefits
        $benefits = $request->benefits ?? null;
        if ($benefits) {
            $this->jobBenefitsInsert($request->benefits, $jobCreated);
        }

        // Tags
        $tags = $request->tags ?? null;
        if ($tags) {
            $this->jobTagsInsert($request->tags, $jobCreated);
        }

        // skills
        $skills = $request->skills ?? null;
        if ($skills) {
            $this->jobSkillsInsert($request->skills, $jobCreated);
        }

        // custom addition by moeed
        if ($request->has('dynamic_inputs')) {
            foreach ($request->dynamic_inputs as $input) {
                // Validate that the necessary fields are present
                if (isset($input['id']) && isset($input['value'])) {
                    CompanyAttributeTranslation::create([
                        'company_id' => currentCompany()->id, // Assuming this function returns the current company
                        'job_id' => $jobCreated->id,
                        'company_attribute_id' => $input['id'], // This is the attribute ID
                        'attribute_value' => $input['value'],  // This is the input value
                    ]);
                }
            }
        }

        if ($jobCreated) {
            $user_plan = currentCompany()->userPlan()->first();

            $user_plan->job_limit = $user_plan->job_limit - 1;
            if ($featured) {
                $user_plan->featured_job_limit = $user_plan->featured_job_limit - 1;
            }
            if ($highlight) {
                $user_plan->highlight_job_limit = $user_plan->highlight_job_limit - 1;
            }
            $user_plan->save();

            storePlanInformation();

            Notification::send(authUser(), new JobCreatedNotification($jobCreated));

            if ($jobCreated->status == 'active') {
                $candidates = CandidateJobAlert::where('job_role_id', $jobCreated->role_id)->get();

                foreach ($candidates as $candidate) {
                    if ($candidate->candidate->received_job_alert) {
                        $candidate->candidate->user->notify(new RelatedJobNotification($jobCreated));
                    }
                }
            }

            if (checkMailConfig()) {
                // make notification to admins for approved
                $admins = Admin::all();
                foreach ($admins as $admin) {
                    Notification::send($admin, new NewJobAvailableNotification($admin, $jobCreated));
                }
            }
        }

        return $jobCreated;
    }
}
