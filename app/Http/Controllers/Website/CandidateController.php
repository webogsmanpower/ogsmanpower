<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Traits\CandidateAble;
use App\Http\Traits\CandidateSkillAble;
use App\Http\Traits\HasCandidateResume;
use App\Models\AppliedJob;
use App\Models\Attachment;
use App\Models\BilangualResumeSubscription;
use App\Models\Candidate;
use App\Models\CandidateLanguage;
use App\Models\CandidateResume;
use App\Models\Company;
use App\Models\ContactInfo;
use App\Models\Education;
use App\Models\Experience;
use Modules\Location\Entities\Country;
use App\Models\JobRole;
use App\Models\JobTitle;
use App\Models\CandidateAttribute;
use App\Models\CandidateDocument;
use App\Models\CandidatePlan;
use App\Models\CandidateSubscription;
use App\Models\City;
use App\Models\IndustryType;
use App\Models\JobRequirement;
use App\Models\LanguageData;
use App\Models\ManualPayment;
use App\Models\Profession;
use App\Models\SearchCountry;
use App\Models\Skill;
use App\Models\State;
use Modules\Language\Entities\Language;
use App\Services\Website\Candidate\CandidateSettingUpdateService;
use App\Services\Website\Candidate\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Mpdf\Mpdf;
use Stichoza\GoogleTranslate\GoogleTranslate;
use PDF;

class CandidateController extends Controller

{
    use CandidateAble, CandidateSkillAble, HasCandidateResume;

    public function __construct()
    {
        $this->middleware('access_limitation')->only([
            'settingUpdate',
        ]);
    }

    /**
     * Candidate dashboard
     *
     * @return \Illuminate\Http\Response
     */

    public function additionlSetting()
    {
        try {
            $candidate = auth()->user()->candidate;

            if (empty($candidate)) {
                Candidate::create(['user_id' => auth()->id()]);
            }
            $nameParts = explode(' ', auth()->user()->name, 2); // Splits the name into two parts
            $firstName = $nameParts[0] ?? ''; // First name
            $lastName = $nameParts[1] ?? ''; // Last name (if available)


            // for contact
            $contactInfo = ContactInfo::where('user_id', auth()->id())->first();
            $contact = [];
            if ($contactInfo) {
                $contact = $contactInfo;
            } else {
                $contact = '';
            }

            // for social link
            $socials = auth()->user()->socialInfo;
            $candidate_id = auth()->user()->candidate->id;
            // for candidate resume/cv
            $resumes = $candidate->resumes;
            $job_roles = JobRole::all()->sortBy('name');
            $experiences = Experience::all();
            $educations = Education::all();
            $industries = IndustryType::all();
            $educations = Education::all();
            $attachments = Attachment::where('candidate_id', $candidate_id)->first();
            $professions = Profession::all()->sortBy('name');
            $skills = Skill::all()->sortBy('name');
            $languages = CandidateLanguage::all(['id', 'name']);
            $bilangualLanguaes = Language::all();
            $candidate->load('skills', 'languages', 'experiences', 'educations', 'jobRoleAlerts:id,candidate_id,job_role_id');
            $countries = Country::all();
            $jobtitles = JobTitle::all();
            $dynamicInputs = CandidateAttribute::where('candidate_id', $candidate->id)->where('is_active', '1')->get();

            $jobsRequirments = JobRequirement::where('candidate_id', $candidate->id)->first();

            // Initialize empty collections
            $Jobs = collect();
            $Industries = collect();
            $country = null;
            $state = null;
            $city = null;

            if ($jobsRequirments) {
                if ($jobsRequirments->jobs) {
                    $jobIds = json_decode($jobsRequirments->jobs, true);
                    $Jobs = Profession::whereIn('id', $jobIds)->get();
                }

                if ($jobsRequirments->industries) {
                    $industryIds = json_decode($jobsRequirments->industries, true);
                    $Industries = IndustryType::whereIn('id', $industryIds)->get();
                }

                $country = SearchCountry::where('id', $jobsRequirments->search_country_id)->first();
                $city = City::where('id', $jobsRequirments->city_id)->first();
                $state = State::where('id', $jobsRequirments->state_id)->first();
                // dd($state);
            }
            return view('frontend.pages.candidate.additionl-setting', [
                'candidate' => $candidate->load('skills', 'languages'),
                'contact' => $contact,
                'industries' => $industries,
                'socials' => $socials,
                'job_roles' => $job_roles,
                'experiences' => $experiences,
                'educations' => $educations,
                'professions' => $professions,
                'resumes' => $resumes,
                'skills' => $skills,
                'candidate_languages' => $languages,
                "attachments" => $attachments,
                "bilangualLanguaes" => $bilangualLanguaes,
                "countries" => $countries,
                "jobtitles" => $jobtitles,
                "dynamicInputs" => $dynamicInputs,
                "lastName" => $lastName,
                "firstName" => $firstName,

            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function plan()
    {
        $plan = CandidatePlan::first();
        $candidate_id = auth()->user()->candidate->id;
        $plan_Subscription = CandidateSubscription::with('candidate')->where('candidate_id', $candidate_id)->first();


        return view('frontend.pages.candidate.plan', compact('plan', 'plan_Subscription'));
    }

    public function candidateDocument()
    {
        $candidate_id = auth()->user()->candidate->id;
        // for candidate resume/cv

        $attachments = CandidateDocument::where('candidate_id', $candidate_id)->first();
        return view('frontend.pages.candidate.document', ['attachments' => $attachments]);
    }

    public function viewResume(Request $request)
    {
        try {
            $candidate = auth()->user()->candidate;
// dd($request->language_code);

            if ($request->format == 'bilangual_format') {
                $bilangualPlan = BilangualResumeSubscription::where('candidate_id', $candidate->id)->where('language_code', $request->language_code)->first();
                if ($bilangualPlan && $bilangualPlan->status == 'pending') {
                    return redirect()
                        ->route('candidate.view.cv')
                        ->with('warning', 'Wait for approval.');
                }

                if (!$bilangualPlan) {

                    $plan = CandidatePlan::first();
                    $mid_token =  null;
                    $manual_payments = ManualPayment::whereStatus(1)->get();
                    $language_code =  $request->language_code;
                    $language =  $request->format;
                    return view('frontend.pages.candidate.bilangual-plan', compact('manual_payments', 'mid_token', 'plan','language_code','language'));
                }
            }

            if (empty($candidate)) {
                Candidate::create(['user_id' => auth()->id()]);
            }

            // Update resume format
            $candidate->update(['resume_format' => $request->format,
                                'language_code' => $request->language_code,
            ]);

            // Prepare data for the view/PDF
            $contactInfo = ContactInfo::where('user_id', auth()->id())->first();
            $contact = $contactInfo ? $contactInfo : '';
            $socials = auth()->user()->socialInfo;
            $candidate_id = auth()->user()->candidate->id;
            $resumes = $candidate->resumes;
            $job_roles = JobRole::all()->sortBy('name');
            $experiences = Experience::all();
            $educations = Education::all();
            $attachments = Attachment::where('candidate_id', $candidate_id)->first();
            $professions = Profession::all()->sortBy('name');
            $skills = Skill::all()->sortBy('name');
            $languages = CandidateLanguage::all(['id', 'name']);
            $translate = new GoogleTranslate($request->language_code ?? 'en');
            // $jobRequirement=JobRequirement::where('candidate_id', $candidate_id)->with('searchcountry','city','state')->first();


            $candidate->load('skills', 'languages', 'experiences', 'educations', 'expected_country', 'jobRoleAlerts:id,candidate_id,job_role_id');

            $viewMap = [
                'general_format' => 'frontend.pages.candidate.general-resume',
                'driver_format' => 'frontend.pages.candidate.driver-resume',
                'guard_format' => 'frontend.pages.candidate.security-guard-resume',
                'beautician_format' => 'frontend.pages.candidate.beautician-resume',
                'web_developer_format' => 'frontend.pages.candidate.web-developer-resume',
                'bike_rider_format' => 'frontend.pages.candidate.bike-rider-resume',
                'bilangual_format' => 'frontend.pages.candidate.bilangual-resume',
            ];

            $view = $viewMap[$request->format] ?? $viewMap['general_format'];
            // $qrCode = base64_encode(QrCode::format('png')->size(80)->generate('https://example.com/candidate/'.$candidate->id));
            $qrCode = QrCode::size(70)->generate('https://example.com/candidate/' . $candidate->id);

            $data = [
                'candidate' => $candidate,
                'contact' => $contact,
                'socials' => $socials,
                'job_roles' => $job_roles,
                'experiences' => $experiences,
                'educations' => $educations,
                'professions' => $professions,
                'resumes' => $resumes,
                'skills' => $skills,
                'candidate_languages' => $languages,
                'attachments' => $attachments,
                'qrCode' => $qrCode,
                'translate' => $translate,
                // 'jobRequirement' => $jobRequirement

            ];
            // Check action type
            // if ($request->format == 'bilangual_format') {
            if ($request->format == 'bilangual_format') {
                $htmlContent = view($view, $data)->render();
                $mpdf = new Mpdf();

                $mpdf->WriteHTML($htmlContent);

                if ($request->action_type == 'download') {
                    return $mpdf->Output('candidate_cv_' . $candidate->id . '.pdf', 'D');
                } else {
                    return $mpdf->Output('resume.pdf', 'I');
                    // return view($view, $data);

                }
            } else {
                if ($request->action_type == 'download') {
                    // Generate PDF for download
                    $pdf = PDF::loadView($view, $data);
                    return $pdf->download('candidate_cv_' . $candidate->id . '.pdf');
                    // return view($view, $data);

                } else {
                    // Render resume view (for viewing in browser)
                    // return view($view, $data);
                    $pdf = PDF::loadView($view, $data);
                    return $pdf->stream('resume.pdf');
                }
            }
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());
            return back();
        }
    }

    public function dashboard()
    {
        try {
            $data = (new DashboardService)->execute();

            return view('frontend.pages.candidate.dashboard', $data);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Candidate notification page
     *
     * @return \Illuminate\Http\Response
     */
    public function allNotification()
    {
        try {
            $notifications = auth()
                ->user()
                ->notifications()
                ->paginate(12);

            return view('frontend.pages.candidate.all-notification', compact('notifications'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Candidate job alert page
     *
     * @return \Illuminate\Http\Response
     */
    public function jobAlerts()
    {
        try {
            $notifications = auth()
                ->user()
                ->notifications()
                ->where('type', 'App\Notifications\Website\Candidate\RelatedJobNotification')
                ->paginate(12);

            return view('frontend.pages.candidate.job-alerts', compact('notifications'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Candidate applied job page
     *
     * @return \Illuminate\Http\Renderable
     */
    public function appliedjobs(Request $request)
    {
        try {
            $candidate = Candidate::where('user_id', auth()->id())->first();
            if (empty($candidate)) {
                $candidate = new Candidate;
                $candidate->user_id = auth()->id();
                $candidate->save();
            }

            $resumes = CandidateResume::where('candidate_id', $candidate->id)->get();
            $applied_jobs = AppliedJob::with('applicationGroup:id,name')
                ->where('candidate_id', $candidate->id)
                ->get();

            $appliedJobs = $candidate
                ->appliedJobs()
                ->paginate(8)
                ->through(function ($application) use ($applied_jobs, $resumes) {
                    $application_group = $applied_jobs->where('job_id', $application->id)->first();
                    $resume = $resumes->where('id', $application_group->candidate_resume_id)->first();
                    $application->application_status = $application_group->applicationGroup->name;
                    $application->cover_letter = $application_group->cover_letter;
                    $application->cv_file = $resume ? $resume->file : '';
                    $application->cv_name = $resume ? $resume->name : '';
                    return $application;
                });

            return view('frontend.pages.candidate.applied-jobs', compact('appliedJobs'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Candidate bookmark page
     *
     * @return \Illuminate\Http\Response
     */
    public function bookmarks(Request $request)
    {
        try {
            $candidate = Candidate::where('user_id', auth()->id())->first();
            if (empty($candidate)) {
                $candidate = new Candidate;
                $candidate->user_id = auth()->id();
                $candidate->save();
            }

            $jobs = $candidate
                ->bookmarkJobs()
                ->withCount([
                    'appliedJobs as applied' => function ($q) use ($candidate) {
                        $q->where('candidate_id', $candidate->id);
                    },
                ])
                ->paginate(12);

            if (auth('user')->check() && authUser()->role == 'candidate') {
                $resumes = currentCandidate()->resumes;
            } else {
                $resumes = [];
            }

            return view('frontend.pages.candidate.bookmark', compact('jobs', 'resumes'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Candidate bookmark company toggle
     *
     * @return \Illuminate\Http\Response
     */
    public function bookmarkCompany(Company $company)
    {
        try {
            $company->bookmarkCandidateCompany()->toggle(currentCandidate());

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }


    public function getStates(Request $request)
    {
        $states = State::where('country_id', $request->country_id)->get(['id', 'name']);
        return response()->json(['states' => $states]);
    }

    public function getCities(Request $request)
    {
        $cities = City::where('state_id', $request->state_id)->get(['id', 'name']);
        return response()->json(['cities' => $cities]);
    }

    /**
     * Candidate settings page
     *
     * @return \Illuminate\Http\Response
     */
    public function setting()
    {
        try {
            $candidate = auth()->user()->candidate;

            if (empty($candidate)) {
                Candidate::create(['user_id' => auth()->id()]);
            }
            $nameParts = explode(' ', auth()->user()->name, 2); // Splits the name into two parts
            $firstName = $nameParts[0] ?? ''; // First name
            $lastName = $nameParts[1] ?? ''; // Last name (if available)


            // for contact
            $contactInfo = ContactInfo::where('user_id', auth()->id())->first();
            $contact = [];
            if ($contactInfo) {
                $contact = $contactInfo;
            } else {
                $contact = '';
            }

            // for social link
            $socials = auth()->user()->socialInfo;
            $candidate_id = auth()->user()->candidate->id;
            // for candidate resume/cv
            $resumes = $candidate->resumes;
            $job_roles = JobRole::all()->sortBy('name');
            $experiences = Experience::all();
            $educations = Education::all();
            $industries = IndustryType::all();
            $attachments = Attachment::where('candidate_id', $candidate_id)->first();
            $professions = Profession::all()->sortBy('name');
            $skills = Skill::all()->sortBy('name');
            $languages = CandidateLanguage::all(['id', 'name']);
            $bilangualLanguaes = Language::all();
            $candidate->load('skills', 'languages', 'experiences', 'educations', 'jobRoleAlerts:id,candidate_id,job_role_id');
            $jobtitles = JobTitle::all();
            $searchCountries = SearchCountry::all();
            $dynamicInputs = CandidateAttribute::where('candidate_id', $candidate->id)->where('is_active', '1')->get();
            $jobRequirement = JobRequirement::where('candidate_id', $candidate->id)->first();

            return view('frontend.pages.candidate.setting', [
                'candidate' => $candidate->load('skills', 'languages'),
                'contact' => $contact,
                'industries' => $industries,
                'socials' => $socials,
                'job_roles' => $job_roles,
                'experiences' => $experiences,
                'educations' => $educations,
                'professions' => $professions,
                'resumes' => $resumes,
                'skills' => $skills,
                'candidate_languages' => $languages,
                "attachments" => $attachments,
                "bilangualLanguaes" => $bilangualLanguaes,
                // "countries" => $countries,
                "jobtitles" => $jobtitles,
                "dynamicInputs" => $dynamicInputs,
                "lastName" => $lastName,
                "firstName" => $firstName,
                "searchCountries" => $searchCountries,
                // "states" => $states,
                // "cities" => $cities,
                'jobRequirement' => $jobRequirement, // Pass Job Requirements
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
    public function candidateCV()
    {
        try {
            $candidate = auth()->user()->candidate;
            $subscription = BilangualResumeSubscription::where('candidate_id',$candidate->id)->get();
            return view('frontend.pages.candidate.cv', [
                'candidate' => $candidate->load('skills', 'languages'),
                'subscription'=> $subscription
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Candidate setting update
     *
     * @return \Illuminate\Http\Response
     */
    public function settingUpdate(Request $request)
    {
        try {
            (new CandidateSettingUpdateService)->update($request);

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    // custom code
    public function deleteAttachment($id)
    {
        $attachment = Attachment::findOrFail($id);

        // Delete the file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return redirect()->back()->with('success', 'Attachment deleted successfully.');
    }
    /**
     * Candidate username update
     *
     * @return \Illuminate\Http\Response
     */
    public function usernameUpdate(Request $request)
    {
        try {
            $request->session()->put('type', 'account');

            if ($request->type == 'candidate_username') {
                $request->validate([
                    'username' => 'required|unique:users,username,' . auth()->user()->id,
                ]);

                authUser()->update([
                    'username' => $request->username,
                ]);

                flashSuccess(__('username_updated_successfully'));

                return back();
            }
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
}
