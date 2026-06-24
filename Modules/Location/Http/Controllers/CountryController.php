<?php

namespace Modules\Location\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Location\Entities\Country;

class CountryController extends Controller
{
    public function toggleVisibility(Request $request)
    {
        // Validate the incoming request
        // dd($request);

        $request->validate([
            'id' => 'required|exists:countries,id',  // Ensure the country ID exists
            // 'column' => 'required|in:candidates_by_country, jobs_by_country',  // Ensure the column is valid
            'value' => 'required|boolean',  // Ensure the value is boolean (0 or 1)
        ]);

        // Find the country by ID
        $country = Country::find($request->id);

        // Update the specified column with the new value (1 for checked, 0 for unchecked)
        $country->{$request->column} = $request->value;

        // Save the updated country data
        $country->save();

        // Return a success response
        return response()->json(['status' => 'success']);
    }



    public function index(Request $request)
    {
        try {
            abort_if(! userCan('country.view'), 403);

            $query = Country::query();

            // name filter
            if ($request->has('name') && $request->name != null) {
                $query->where('name', 'LIKE', "%$request->name%");
            }

            // country
            if ($request->has('country') && $request->country != null) {
                $query->where('id', $request->country);
            }

            $allCountries = Country::all(['id', 'name']);

            $countries = $query
                ->select('id', 'name', 'image', 'slug','candidates_by_country','jobs_by_country')
                ->paginate(20)
                ->onEachSide(0);

            if ($request->perpage != 'all') {
                $countries = $countries->withQueryString();
            }

            return view('location::country.index', compact('countries', 'allCountries'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function create()
    {
        try {
            abort_if(! userCan('country.create'), 403);

            $countrys = Country::all();

            return view('location::country.create', compact('countrys'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function store(Request $request)
    {

        abort_if(! userCan('country.create'), 403);

        //Validation
        $request->validate([
            'name' => 'required|unique:countries,name',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif',
            'icon' => 'required',
        ]);

        try {
            if ($request->file('image')) {
                $path = 'country';
                $image = uploadImage($request->image, $path);
            }
            //return $request->icon;
            Country::create([
                'name' => $request->name,
                'description' => $request->description,

                'image' => $request->file('image') ? $image : 'backend/image/default.png',
                'icon' => $request->icon,
            ]);

            flashSuccess(__('country_created_successfully'));

            return redirect()->route('module.country.index');
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function show(Country $country)
    {
        try {
            $states = $country
                ->states()
                ->withCount('cities')
                ->paginate(20);

            return view('location::country.show', compact('country', 'states'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function edit(Country $country)
    {
        try {
            abort_if(! userCan('country.update'), 403);

            $countries = Country::all();

            return view('location::country.edit', compact('country', 'countries'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function update(Request $request, Country $country)
    {
        abort_if(! userCan('country.update'), 403);

        $request->validate([
            'name' => 'required',
        ]);

        try {
            if ($request->file('image')) {
                //image validation
                $request->validate([
                    'image' => 'required|image|mimes:jpeg,png,jpg,gif',
                ]);

                $oldImg = $country->image;
                if (file_exists($oldImg)) {
                    deleteImage($oldImg);
                }

                $path = 'country';
                $image = uploadImage($request->image, $path);
                $country->update([
                    'image' => $image,
                ]);
            }

            $country->update([
                'name' => $request->name,
                'description' => $request->description,
                'icon' => $request->icon,
            ]);

            flashSuccess(__('country_updated_successfully'));

            return redirect()->route('module.country.index');
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function destroy(Country $country)
    {
        try {
            abort_if(! userCan('country.delete'), 403);

            $country->delete();
            flashSuccess(__('country_deleted_successfully'));

            return redirect()->back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function multipleDestroy(Request $request)
    {
        try {
            abort_if(! userCan('country.delete'), 403);
            $countries = Country::whereIn('id', $request->ids)->get();

            foreach ($countries as $country) {
                $oldimg = $country->image;
                if ($country->image) {
                    deleteImage($oldimg);
                }
                $country->delete();
            }

            flashSuccess(__('country_deleted_successfully'));

            return true;
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function setAppCountry(Request $request)
    {
        try {
            $country = Country::FindOrFail($request->country);

            $setting = Setting::first();
            $setting->update([
                'app_country' => $country->id,
                'default_long' => $country->longitude,
                'default_lat' => $country->latitude,
            ]);

            flashSuccess(__('app_country_set'));

            return redirect()->back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
}
