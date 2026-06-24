<?php

namespace App\Http\Traits;

use App\Models\ApplicationGroup;
use App\Models\AppliedJob;
use App\Models\Education;
use App\Models\Job;
use App\Models\SearchCountry;
use Illuminate\Http\Request;
use Modules\Location\Entities\Country;

trait HasCompanyApplication
{
    /**
     * Company job application sync
     *
     * @return Response
     */
    public function applicationsSync(Request $request)
    {
        $this->validate(request(), [
            'applicationGroups' => ['required', 'array'],
        ]);

        foreach ($request->applicationGroups as $applicationGroup) {
            foreach ($applicationGroup['applications'] as $i => $application) {
                $order = $i + 1;

                if ($application['application_group_id'] !== $applicationGroup['id'] || $application['order'] != $order) {
                    $applications = AppliedJob::where('id', $application['id'])
                        ->where('application_group_id', $application['application_group_id'])
                        ->first();

                    if ($applications) {
                        $applications->update([
                            'order' => $order,
                            'application_group_id' => $applicationGroup['id'],
                        ]);
                    }
                }
            }
        }

        return $request->user()
            ->company
            ->applicationGroups()
            ->with(['applications' => function ($query) {
                $query->with(['candidate' => function ($query) {
                    return $query->select('id', 'user_id', 'profession_id', 'experience_id', 'education_id')
                        ->with('profession', 'education:id', 'experience:id', 'user:id,name,username,image');
                }]);
            }])
            ->get();
    }

    /**
     * Company job application page
     *
     * @return Response
     */
    public function jobApplications(Request $request)
    {
        $application_groups = auth()->user()
            ->company
            ->applicationGroups()
            ->with(['applications' => function ($query) use ($request) {
                $query->where('job_id', $request->job)
                    ->with(['candidate' => function ($query) use ($request) {
                        // Select the necessary candidate fields
                        $query->select('id', 'user_id', 'profession_id', 'experience_id', 'education_id', 'gender', 'country')
                            ->with('profession', 'education:id', 'experience:id,years', 'user:id,name,username,image');

                        // Filter by gender
                        if ($request->filled('gender')) {
                            $query->where('gender', $request->gender);
                        }

                        // Filter by country
                        if ($request->filled('country')) {
                            $query->where('country', $request->country);
                        }

                        // Filter by name
                        if ($request->filled('name')) {
                            $query->whereHas('user', function ($query) use ($request) {
                                $query->where('name', 'like', '%' . $request->name . '%');
                            });
                        }

                        // Filter by age range
                        if ($request->filled('age_from')) {
                            $query->where('age', '>=', $request->age_from);
                        }
                        if ($request->filled('age_to')) {
                            $query->where('age', '<=', $request->age_to);
                        }

                        // Filter by experience range
                        if ($request->filled('experience_from')) {
                            $query->whereHas('experience', function ($query) use ($request) {
                                $query->where('years', '>=', $request->experience_from);
                            });
                        }
                        if ($request->filled('experience_to')) {
                            $query->whereHas('experience', function ($query) use ($request) {
                                $query->where('years', '<=', $request->experience_to);
                            });
                        }

                        // Filter by education
                        // if ($request->filled('education')) {
                        //     $query->whereHas('education', function ($query) use ($request) {
                        //         $query->where('name', $request->education);
                        //     });
                        // }
                    }]);
            }])
            ->get();

        $directAttachments = AppliedJob::whereNull('candidate_id')
            ->where('job_id', $request->job)
            ->get();

        $job = Job::findOrFail($request->job, ['id', 'title', 'company_id']);
        abort_if(currentCompany()->id != $job->company_id, 404);
        $countries = SearchCountry::all();
        $educations = Education::all();
        $rejected = AppliedJob::where('status', 'rejected')->where('job_id', $request->job)->count();
        $shortlisted = AppliedJob::where('status', 'shortlisted')->where('job_id', $request->job)->count();
        $selected = AppliedJob::where('status', 'selected')->where('job_id', $request->job)->count();

        return view('frontend.pages.company.applications', compact('application_groups', 'job', 'countries', 'directAttachments', 'rejected', 'shortlisted', 'selected', 'educations'));
    }


    /**
     * Application Column Store
     *
     * @return \Illuminate\Http\Response
     */
    public function applicationColumnStore(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        ApplicationGroup::create([
            'company_id' => auth()->user()->company->id,
            'name' => $request->name,
        ]);

        flashSuccess(__('group_created_successfully'));

        return response()->json(['success' => true]);
    }

    /**
     * Application Column Update
     *
     * @return \Illuminate\Http\Response
     */
    public function applicationColumnUpdate(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        ApplicationGroup::find($request->id)->update([
            'name' => $request->name,
        ]);

        flashSuccess(__('group_updated_successfully'));

        return response()->json(['success' => true]);
    }

    /**
     * Application Column Delete
     *
     * @return \Illuminate\Http\Response
     */
    public function applicationColumnDelete(ApplicationGroup $group)
    {
        if ($group->is_deleteable) {
            $new_group = ApplicationGroup::where('company_id', auth()->user()->company->id)
                ->where('id', '!=', $group->id)
                ->where('is_deleteable', false)
                ->first();

            if ($new_group) {
                $group->applications()->update([
                    'application_group_id' => $new_group->id,
                ]);
            }

            $group->delete();

            response()->json(['success' => true, 'message' => __('group_deleted_successfully')]);
        }

        response()->json(['success' => false, 'message' => __('group_is_not_deletable')]);
    }

    /**
     * Company Delete Applications
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyApplication(Job $job, Request $request)
    {
        $job->appliedJobs()->detach($request->candidate_id);

        flashSuccess(__('application_removed_from_our_system'));

        return back();
    }
}
