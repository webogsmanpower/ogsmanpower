<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Contract;
use App\Models\ContractAgreement;
use App\Models\IndustryType;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Permission\Models\Role;


class AgentController extends Controller
{
    public function saveAgreement(Request $request)
    {
        // Validate the request
        $request->validate([
            'accept_agreement' => 'required|accepted', // Ensure the checkbox is checked
        ]);

        // Save contract details (if required)
        $contract = new ContractAgreement();
        $contract->admin_id = auth()->user()->id;
        $contract->contract_content = $request->contract_content ?? ''; // Assuming contract content is dynamic
        $contract->date_signed = now();
        $contract->signature = auth()->user()->name; // User's name as the signature
        $contract->is_contract_submitted = 1;
        $contract->save();

        // Redirect with success message
        return redirect()->back()->with('success', 'Contract agreement has been saved successfully.');
    }

    public function contractForm()
    {
        $contract = Contract::first();

        return view('backend.agent.contract', compact('contract'));
    }
    public function downloadAgreement()
    {
        $contract = Contract::first();
        $pdf = Pdf::loadView('backend.agent.contract-pdf', compact('contract'));
        return $pdf->download('Contract-Agreement.pdf');
    }
    public function approvedContract($id)
    {
        // Find the contract for the given admin ID
        $contract = ContractAgreement::where('admin_id', $id)->first();

        // Check if the contract exists
        if ($contract) {
            $contract->is_approved = 1; // Approve the contract
            $contract->save(); // Save the changes
            return redirect()->back()->with('success', 'Contract Approved.');
        }

        // If contract not found, return an error response
        return redirect()->back()->with('error', 'Contract Not Found.');
    }


    public function index(Request $request)
    {
        try {

            $query = Admin::where('id', '!=', 1);
            // verified status
            if ($request->has('ev_status') && $request->ev_status != null) {
                $ev_status = null;
                if ($request->ev_status == 'true') {

                    $query->whereNotNull('email_verified_at');
                } else {

                    $query->whereNull('email_verified_at');
                }
            }

            if ($request->keyword && $request->keyword != null) {

                $query->where('name', 'LIKE', "%$request->keyword%")->orWhere('email', 'LIKE', "%$request->keyword%");
            }

            // sortby
            if ($request->sort_by == 'latest' || $request->sort_by == null) {
                $query->latest();
            } else {
                $query->oldest();
            }
            $roles = Role::where('id','!=',1)->get();
            $agents = $query->paginate(10)->withQueryString();

            return view('backend.agent.index', compact('agents','roles'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function show($id)
    {
        try {
            $user = Admin::findOrFail($id);
            $contract = Contract::first();
            $contractAgreement = ContractAgreement::where('admin_id', $id)->first();
            $industry_types = IndustryType::all()->sortBy('name');

            return view('backend.agent.show', compact('user', 'contract', 'contractAgreement', 'industry_types'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());
            return back();
        }
    }

    public function destroy($id)
    {

        try {
            $user = Admin::FindOrFail($id);

            if (file_exists($user->image)) {
                if ($user->image != 'backend/image/default.png') {
                    unlink($user->image);
                }
            }
            $user->delete();

            flashSuccess(__('Deleted successfully'));

            return redirect()->route('agent.index');
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Change Admin status
     *
     * @return \Illuminate\Http\Response
     */
    public function statusChange(Request $request)
    {
        try {
            $user = User::findOrFail($request->id);
            $user->status = $request->status;
            $user->save();

            if ($request->status == 1) {
                return responseSuccess(__('Admin_activated_successfully'));
            } else {
                return responseSuccess(__('Admin_deactivated_successfully'));
            }
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
    public function is_Admin_featured(Request $request)
    {
        try {
            $user = Admin::findOrFail($request->id);
            $user->is_Admin_featured = $request->is_Admin_featured;
            $user->save();

            if ($request->is_Admin_featured == 1) {
                return responseSuccess(__('Admin Featured Successfully'));
            } else {
                return responseSuccess(__('Admin Non-featured Successfully'));
            }
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Change Admin verification status
     *
     * @return \Illuminate\Http\Response
     */
    public function verificationChange(Request $request)
    {
        try {
            $user = User::findOrFail($request->id);

            if ($request->status) {
                $user->update(['email_verified_at' => now()]);
                $message = __('email_verified_successfully');
            } else {
                $user->update(['email_verified_at' => null]);
                $message = __('email_unverified_successfully');
            }

            return responseSuccess($message);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function AdminExport($type)
    {
        $name = time() . '_Admins.' . $type;
        try {
            return Excel::download(new AdminExport, $name);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function toggleProfileStatus(Request $request)
    {
        $agent = Admin::find($request->id);

        if ($agent) {
            // Toggle the is_profile_approved status
            $agent->is_profile_approved = !$agent->is_profile_approved;
            $agent->save();

            return response()->json([
                'status' => true,
                'message' => 'Profile status updated successfully!',
                'is_profile_approved' => $agent->is_profile_approved
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Agent not found!'
        ], 404);
    }
    public function changeRole(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:users,id',
            'role' => 'required|exists:roles,name',
        ]);

        $agent = Admin::findOrFail($request->agent_id);

        // Remove all roles and assign the new role
        $agent->syncRoles($request->role);

        return redirect()->back()->with('success', 'Role updated successfully!');
    }
}
