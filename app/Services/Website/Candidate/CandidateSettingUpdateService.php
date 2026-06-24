<?php

namespace App\Services\Website\Candidate;

use App\Mail\SendEmailUpdateVerification;
use App\Models\Candidate;
use App\Models\ContactInfo;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Profession;
use App\Models\ProfessionTranslation;
use App\Models\Setting;
use App\Models\Skill;
use App\Models\SkillTranslation;
use App\Models\User;
use App\Models\Attachment;
use App\Models\CandidateAttribute;
use App\Models\CandidateDocument;
use App\Models\JobRequirement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Language\Entities\Language;
use Illuminate\Support\Facades\Storage;

class CandidateSettingUpdateService
{
    /**
     * Candidate setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update($request)
    {


        $user = User::FindOrFail(auth()->id());
        $candidate = Candidate::where('user_id', $user->id)->first();
        $contactInfo = ContactInfo::where('user_id', auth()->id())->first();
        $request->session()->put('type', $request->type);

        if ($request->type == 'basic') {
            $this->candidateBasicInfoUpdate($request, $user, $candidate);
            $candidate->update(['profile_complete' => $candidate->profile_complete != 0 ? $candidate->profile_complete - 25 : 0]);
            flashSuccess(__('profile_updated'));

            return back();
        }
        if ($request->type == 'jobRequirements') {
            $this->jobRequirments($request, $candidate);
            flashSuccess(__('profile_updated'));

            return back();
        }
        if ($request->type == 'summary') {
            $this->candidateSummaryUpdate($request, $user, $candidate);
            flashSuccess(__('Summary Updated'));

            return back();
        }

        if ($request->type == 'skill') {
            $this->candidateSkillUpdate($request, $candidate);
            flashSuccess(__('Skills Updated'));
            return back();
        }

        if ($request->type == 'language') {
            $this->candidateLanguageUpdate($request, $candidate);
            flashSuccess(__('Languages Updated'));
            return back();
        }

        if ($request->type == 'profile') {
            $this->candidateProfileInfoUpdate($request, $candidate);
            $candidate->update(['profile_complete' => $candidate->profile_complete != 0 ? $candidate->profile_complete - 25 : 0]);
            flashSuccess(__('profile_updated'));

            return back();
        }

        if ($request->type == 'social') {
            $this->socialUpdate($request);
            $candidate->update(['profile_complete' => $candidate->profile_complete != 0 ? $candidate->profile_complete - 25 : 0]);
            flashSuccess(__('profile_updated'));

            return back();
        }

        if ($request->type == 'contact') {
            $this->contactUpdate($request, $candidate);
            $candidate->update(['profile_complete' => $candidate->profile_complete != 0 ? $candidate->profile_complete - 25 : 0]);
            flashSuccess(__('profile_updated'));

            return back();
        }

        if ($request->type == 'account') {

            $this->emailUpdate($request) ? flashSuccess(__('Mail Verification Sent')) : flashSuccess(__('profile_updated'));

            return back();
        }


        if ($request->type == 'skill') {

            $this->emailUpdate($request) ? flashSuccess(__('Mail Verification Sent')) : flashSuccess(__('profile_updated'));

            return back();
        }
        if ($request->type == 'language') {

            $this->emailUpdate($request) ? flashSuccess(__('Mail Verification Sent')) : flashSuccess(__('profile_updated'));

            return back();
        }


        if ($request->type == 'attachments') {
            // Call the attachment update function here
            $this->attachmentUpdate($request);
            flashSuccess(__('Attachments updated successfully'));
            return back();
        }

        if ($request->type == 'alert') {
            $this->alertUpdate($request, $candidate);
            flashSuccess(__('profile_updated'));

            return back();
        }

        if ($request->type == 'visibility') {
            $this->visibilityUpdate($request, $candidate);
            flashSuccess(__('profile_updated'));

            return back();
        }

        if ($request->type == 'password') {
            $this->passwordUpdate($request, $user, $candidate);
            flashSuccess(__('profile_updated'));

            return back();
        }

        if ($request->type == 'account-delete') {
            $this->accountDelete($user);
        }

        if ($request->type == 'documents') {
            $this->documentUpdate($request);
            flashSuccess(__('Document Updated Successfully'));
            return back();
        }
    }

    /**
     * Candidate basic setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  \App\Models\Candidate  $candidate
     * @return \Illuminate\Http\Response
     */
    public function candidateBasicInfoUpdate($request, $user, $candidate)
    {

        $request->validate([
            // 'name' => 'required',
            'birth_date' => 'date',
            'education' => 'required',
            'experience' => 'required',
            'gender' => 'required',
            'marital_status' => 'required',
            'profession' => 'required',
            'status' => 'required',
        ]);

        $nameParts = [$request->first_name, $request->last_name];

        $name = implode(' ', $nameParts); // Joins the names with a space

        $user->update(['name' => $name]);
        // Experience
        $experience_request = $request->experience;
        $experience = Experience::where('id', $experience_request)->first();

        if (! $experience) {
            $experience = Experience::create(['name' => $experience_request]);
        }

        // Education
        $education_request = $request->education;
        $education = Education::where('id', $education_request)->first();

        if (! $education) {
            $education = Education::create(['name' => $education_request]);
        }

        $dateTime = Carbon::parse($request->birth_date);
        $date = $request['birth_date'] = $dateTime->format('Y-m-d H:i:s');

        if ($request->custom_title !== null) {
            $title = $request->custom_title;
        } else {
            $title = $request->title;
        }
        if ($request->status == 'available_in') {
            $request->validate([
                'available_in' => 'required',
            ]);
        }

        // Profession
        $profession_request = $request->profession;
        $profession = ProfessionTranslation::where('profession_id', $profession_request)->orWhere('name', $profession_request)->first();

        if (! $profession) {
            $new_profession = Profession::create(['name' => $profession_request]);

            $languages = loadLanguage();
            foreach ($languages as $language) {
                $new_profession->translateOrNew($language->code)->name = $profession_request;
            }
            $new_profession->save();

            $profession_id = $new_profession->id;
        } else {
            $profession_id = $profession->profession_id;
        }


        $candidate->update([
            'title' => $title,
            'experience_id' => $experience->id,
            'education_id' => $education->id,
            'website' => $request->website,
            'birth_date' => $date,
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,
           
            'profession_id' => $profession_id,
            'status' => $request->status,
            'available_in' => $request->available_in ? Carbon::parse($request->available_in)->format('Y-m-d') : null,
            'passport_number' => $request->passport_number,
            'passport_issue_date' => $request->passport_issue_date,
            'passport_expiry_date' => $request->passport_expiry_date,
            'place_of_issue' => $request->place_of_issue,
            'cnic_number' => $request->cnic_number,
        ]);

        // image
        if ($request->image) {
            $request->validate([
                'image' => 'image|mimes:jpeg,png,jpg',
            ]);

            deleteImage($candidate->photo);

            $path = 'uploads/images/candidates';
            // $image = uploadImage($request->image, $path, [164, 164]);
            $image = uploadImage($request->image, $path);


            $candidate->update([
                'photo' => $image,
            ]);
        }
        // cv
        if ($request->cv) {
            $request->validate([
                'cv' => 'mimetypes:application/pdf,jpeg,docs|max:5048',
            ]);
            $pdfPath = '/file/candidates/';
            $pdf = pdfUpload($request->cv, $pdfPath);

            $candidate->update([
                'cv' => $pdf,
            ]);
        }
        if ($request->input('dynamic_inputs') != '' && $request->input('dynamic_inputs') != Null) {

            foreach ($request->input('dynamic_inputs') as $inputData) {

                $dynamicInput = CandidateAttribute::find($inputData['id']);

                if ($dynamicInput) {
                    $dynamicInput->attribute_value = $inputData['value']; // Update the value
                    $dynamicInput->save(); // Save the changes
                }
            }
        }
        updateMap(auth()->user()->candidate);
        return true;
    }


    public function jobRequirments($request, $candidate)
    {
        $request->validate([
            'jobs' => 'required|array',
            'industries' => 'required|array',
            'region' => 'required|string',
            'currency' => 'required|string',
            'salary' => 'required|numeric|min:0',
            'country' => 'required|integer',
            'state' => 'nullable|integer',
            'district' => 'nullable|integer',
        ]);

        $candidate = auth()->user()->candidate;

        JobRequirement::updateOrCreate(
            ['candidate_id' => $candidate->id], // Search condition
            [
                'jobs' => json_encode($request->jobs), // Store as JSON
                'industries' => json_encode($request->industries),
                'region' => $request->region,
                'currency' => $request->currency,
                'salary' => $request->salary,
                'search_country_id' => $request->country,
                'state_id' => $request->state,
                'city_id' => $request->district,
            ]
        );
        return true;
    }
    public function candidateSummaryUpdate($request, $user, $candidate)
    {
        $request->validate([
            'bio' => 'required',
        ]);

        $candidate->update([

            'bio' => $request->bio,

        ]);
        return true;
    }
    public function candidateSkillUpdate($request, $candidate)
    {
        $request->validate([
            'skills' => 'required',

        ]);

        $skills = $request->skills;
        DB::table('candidate_skill')->where('candidate_id', $candidate->id)->delete();

        if ($skills) {
            $skillsArray = [];

            foreach ($skills as $skill) {
                $skill_exists = SkillTranslation::where('skill_id', $skill)->orWhere('name', $skill)->first();

                if (! $skill_exists) {
                    $select_tag = Skill::create(['name' => $skill]);

                    $languages = loadLanguage();
                    foreach ($languages as $language) {
                        $select_tag->translateOrNew($language->code)->name = $skill;
                    }
                    $select_tag->save();

                    array_push($skillsArray, $select_tag->id);
                } else {
                    array_push($skillsArray, $skill_exists->skill_id);
                }
            }

            $candidate->skills()->attach($skillsArray);
        }

        return true;
    }

    public function candidateLanguageUpdate($request, $candidate)
    {
        $request->validate([

            'languages' => 'required'

        ]);
        $candidate->languages()->sync($request->languages);
        return true;
    }

    /**
     * Candidate profile setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     * @return bool
     */
    public function candidateProfileInfoUpdate($request, $candidate)
    {
        $request->validate([

            'marital_status' => 'required',
            'profession' => 'required',
            'status' => 'required',
        ]);

        if ($request->status == 'available_in') {
            $request->validate([
                'available_in' => 'required',
            ]);
        }

        // Profession
        $profession_request = $request->profession;
        $profession = ProfessionTranslation::where('profession_id', $profession_request)->orWhere('name', $profession_request)->first();

        if (! $profession) {
            $new_profession = Profession::create(['name' => $profession_request]);

            $languages = loadLanguage();
            foreach ($languages as $language) {
                $new_profession->translateOrNew($language->code)->name = $profession_request;
            }
            $new_profession->save();

            $profession_id = $new_profession->id;
        } else {
            $profession_id = $profession->profession_id;
        }

        $candidate->update([
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,
            'bio' => $request->bio,
            'profession_id' => $profession_id,
            'status' => $request->status,
            'available_in' => $request->available_in ? Carbon::parse($request->available_in)->format('Y-m-d') : null,
            'passport_number' => $request->passport_number,
            'passport_issue_date' => $request->passport_issue_date,
            'passport_expiry_date' => $request->passport_expiry_date,
            'place_of_issue' => $request->place_of_issue,
            'cnic_number' => $request->cnic_number,
            'language_code' => $request->language_code,


        ]);

        // skill & language
        $skills = $request->skills;
        DB::table('candidate_skill')->where('candidate_id', $candidate->id)->delete();

        if ($skills) {
            $skillsArray = [];

            foreach ($skills as $skill) {
                $skill_exists = SkillTranslation::where('skill_id', $skill)->orWhere('name', $skill)->first();

                if (! $skill_exists) {
                    $select_tag = Skill::create(['name' => $skill]);

                    $languages = loadLanguage();
                    foreach ($languages as $language) {
                        $select_tag->translateOrNew($language->code)->name = $skill;
                    }
                    $select_tag->save();

                    array_push($skillsArray, $select_tag->id);
                } else {
                    array_push($skillsArray, $skill_exists->skill_id);
                }
            }

            $candidate->skills()->attach($skillsArray);
        }
        if ($request->input('dynamic_inputs') != '' && $request->input('dynamic_inputs') != Null) {

            foreach ($request->input('dynamic_inputs') as $inputData) {

                $dynamicInput = CandidateAttribute::find($inputData['id']);

                if ($dynamicInput) {
                    $dynamicInput->attribute_value = $inputData['value']; // Update the value
                    $dynamicInput->save(); // Save the changes
                }
            }
        }

        $candidate->languages()->sync($request->languages);

        return true;
    }

    /**
     * Candidate contact setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     * @return bool
     */
    public function contactUpdate($request, $candidate)
    {
        $contact = ContactInfo::where('user_id', auth()->id())->first();

        if (empty($contact)) {
            ContactInfo::create([
                'user_id' => auth()->id(),
                'phone' => $request->phone,
                'secondary_phone' => $request->secondary_phone,
                'email' => $request->email,
                'secondary_email' => $request->secondary_email,

            ]);
        } else {
            $contact->update([
                'phone' => $request->phone,
                'secondary_phone' => $request->secondary_phone,
                'email' => $request->email,
                'whatsapp_number' => $request->whatsapp_number,
                'secondary_email' => $request->secondary_email,

            ]);
        }

        if (! empty($request->whatsapp_number)) {
            $candidate->update(['whatsapp_number' => $request->whatsapp_number]);
        }

        // Location


        return true;
    }

    /**
     * Candidate email setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     */
    public function emailUpdate($request): bool
    {
        $user = $request->user();
        $setting = Setting::query()->first();

        $validated = $request->validate([
            'account_email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        if ($validated['account_email'] === $user->email) {
            return false;
        }

        if (! $setting->email_verification) {
            $user->update([
                'email' => $validated['account_email'],
            ]);

            return false;
        }

        // user changed his email
        // if email verification is on in settings
        // then send verify email and mark email as un verified
        Mail::to($validated['account_email'])->send(new SendEmailUpdateVerification($user, $validated['account_email']));
        session()->put('requested_email', $validated['account_email']);

        return true;
    }

    /**
     * Candidate social setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function socialUpdate($request)
    {
        $user = User::find(auth()->id());

        $user->socialInfo()->delete();
        $social_medias = $request->social_media;
        $urls = $request->url;

        if ($social_medias && $urls) {
            foreach ($social_medias as $key => $value) {
                if ($value && $urls[$key]) {
                    $user->socialInfo()->create([
                        'social_media' => $value,
                        'url' => $urls[$key],
                    ]);
                }
            }
        }

        return true;
    }
    public function attachmentUpdate($request)
    {
        // Validate request input
        $request->validate([
            'passport_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'license_image'  => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Get the authenticated candidate
        $candidate = Candidate::findOrFail(auth()->user()->candidate->id);

        // Fetch the candidate's existing attachment or create a new one if it doesn't exist
        $attachment = Attachment::where('candidate_id', $candidate->id)->firstOrNew();

        // Handle passport image upload
        if ($request->hasFile('passport_image')) {
            // Delete old passport image if it exists
            if ($attachment->passport_image) {
                Storage::delete('public/candidates/' . $attachment->passport_image);
            }

            // Store new passport image
            $passportImagePath = $request->file('passport_image')->store('public/candidates');
            $attachment->passport_image = basename($passportImagePath);
        }

        // Handle license image upload
        if ($request->hasFile('license_image')) {
            // Delete old license image if it exists
            if ($attachment->license_image) {
                Storage::delete('public/candidates/' . $attachment->license_image);
            }

            // Store new license image
            $licenseImagePath = $request->file('license_image')->store('public/candidates');
            $attachment->license_image = basename($licenseImagePath);
        }

        // Set the candidate_id on the attachment if it's a new record
        $attachment->candidate_id = $candidate->id;

        // Save the attachment (insert or update)
        $attachment->save();

        return true;
    }
    public function documentUpdate($request)
    {
        $request->validate([
            'passport_image'               => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:5120',
            'license_image'                => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:5120',
            'cnic_front'                   => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'cnic_back'                    => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'police_character_certificate' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'medical'                      => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'navtec_report'                => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        // Get the authenticated candidate
        $candidate = Candidate::findOrFail(auth()->user()->candidate->id);

        // Fetch the candidate's existing attachment or create a new one if it doesn't exist
        $document = CandidateDocument::where('candidate_id', $candidate->id)->firstOrNew();

        // Handle passport image upload
        if ($request->hasFile('passport_image')) {
            // Delete old passport image if it exists
            if ($document->passport_image) {
                Storage::delete('public/candidates/' . $document->passport_image);
            }

            // Store new passport image
            $passportImagePath = $request->file('passport_image')->store('public/candidates');
            $document->passport_image = basename($passportImagePath);
        }


        if ($request->hasFile('cnic_front')) {

            if ($document->cnic_front) {
                Storage::delete('public/candidates/' . $document->cnic_front);
            }


            $cnicFrontPath = $request->file('cnic_front')->store('public/candidates');
            $document->cnic_front = basename($cnicFrontPath);
        }

        if ($request->hasFile('cnic_back')) {

            if ($document->cnic_back) {
                Storage::delete('public/candidates/' . $document->cnic_back);
            }


            $cnicBackPath = $request->file('cnic_back')->store('public/candidates');
            $document->cnic_back = basename($cnicBackPath);
        }

        if ($request->hasFile('police_character_certificate')) {

            if ($document->police_character_certificate) {
                Storage::delete('public/candidates/' . $document->police_character_certificate);
            }


            $policeCertificatePath = $request->file('police_character_certificate')->store('public/candidates');
            $document->police_character_certificate = basename($policeCertificatePath);
        }

        if ($request->hasFile('medical')) {

            if ($document->medical) {
                Storage::delete('public/candidates/' . $document->medical);
            }


            $medicalImagePath = $request->file('medical')->store('public/candidates');
            $document->medical = basename($medicalImagePath);
        }
        if ($request->hasFile('navtec_report')) {

            if ($document->navtec_report) {
                Storage::delete('public/candidates/' . $document->navtec_report);
            }


            $navtecImagePath = $request->file('navtec_report')->store('public/candidates');
            $document->navtec_report = basename($navtecImagePath);
        }

        // Set the candidate_id on the document if it's a new record
        $document->candidate_id = $candidate->id;

        // Save the document (insert or update)
        $document->save();

        return true;
    }

    /**
     * Candidate visibility setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     * @return bool
     */
    public function visibilityUpdate($request, $candidate)
    {
        $candidate->update([
            'visibility' => $request->profile_visibility ? 1 : 0,
            'cv_visibility' => $request->cv_visibility ? 1 : 0,
        ]);

        return true;
    }

    /**
     * Candidate password setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     * @return bool
     */
    public function passwordUpdate($request, $user, $candidate)
    {
        $request->validate([
            'password' => 'required|confirmed|min:6',
            'password_confirmation' => 'required',
        ]);

        $user->update([
            'password' => bcrypt($request->password),
        ]);
        auth()->logout();

        return true;
    }

    /**
     * Candidate account delete
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function accountDelete($user)
    {
        DB::table('candidate_cv_views')->whereIn('candidate_id', function ($query) use ($user) {
            $query->select('id')
                ->from('candidates')
                ->where('user_id', $user->id);
        })->delete();
        Candidate::where('user_id', $user->id)->delete();
        $user->delete();

        return true;
    }

    /**
     * Candidate alert setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     */
    public function alertUpdate($request, $candidate): bool
    {
        if ($request->has('received_job_alert') && $request->alert_type == 'status') {
            $candidate->update([
                'role_id' => $request->role_id,
                'received_job_alert' => $request->received_job_alert ? 1 : 0,
            ]);
        }

        if ($request->has('job_roles')) {
            $candidate->jobRoleAlerts()->delete();

            foreach ($request->job_roles as $role) {
                $candidate->jobRoleAlerts()->create([
                    'job_role_id' => $role,
                ]);
            }
        }

        if (! $request->has('job_roles') && $request->alert_type == 'role' && count($candidate->jobRoleAlerts) > 0) {
            $candidate->jobRoleAlerts()->delete();
        }

        return true;
    }
}
