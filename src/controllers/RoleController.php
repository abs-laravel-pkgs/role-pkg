<?php

namespace Abs\RolePkg;
use Abs\RolePkg\Role;
use App\Address;
use App\Country;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class RoleController extends Controller {

	public function __construct() {
	}

	public function getRoleList(Request $request) {
		$roles = Role::withTrashed()
			->select(
				'roles.id',
				'roles.code',
				'roles.name',
				DB::raw('IF(roles.mobile_no IS NULL,"--",roles.mobile_no) as mobile_no'),
				DB::raw('IF(roles.email IS NULL,"--",roles.email) as email'),
				DB::raw('IF(roles.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('roles.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->role_code)) {
					$query->where('roles.code', 'LIKE', '%' . $request->role_code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->role_name)) {
					$query->where('roles.name', 'LIKE', '%' . $request->role_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->mobile_no)) {
					$query->where('roles.mobile_no', 'LIKE', '%' . $request->mobile_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->email)) {
					$query->where('roles.email', 'LIKE', '%' . $request->email . '%');
				}
			})
			->orderby('roles.id', 'desc');

		return Datatables::of($roles)
			->addColumn('code', function ($role) {
				$status = $role->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $role->code;
			})
			->addColumn('action', function ($role) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				return '
					<a href="#!/role-pkg/role/edit/' . $role->id . '">
						<img src="' . $edit_img . '" alt="View" class="img-responsive">
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_role"
					onclick="angular.element(this).scope().deleteRole(' . $role->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete_img . '" alt="delete" class="img-responsive">
					</a>
					';
			})
			->make(true);
	}

	public function getRoleFormData($id = NULL) {
		if (!$id) {
			$role = new Role;
			$address = new Address;
			$action = 'Add';
		} else {
			$role = Role::withTrashed()->find($id);
			$address = Address::where('address_of_id', 24)->where('entity_id', $id)->first();
			if (!$address) {
				$address = new Address;
			}
			$action = 'Edit';
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['role'] = $role;
		$this->data['address'] = $address;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveRole(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Role Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'code.unique' => 'Role Code is already taken',
				'name.required' => 'Role Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
				'gst_number.required' => 'GST Number is Required',
				'gst_number.max' => 'Maximum 191 Numbers',
				'mobile_no.max' => 'Maximum 25 Numbers',
				// 'email.required' => 'Email is Required',
				'address_line1.required' => 'Address Line 1 is Required',
				'address_line1.max' => 'Maximum 255 Characters',
				'address_line1.min' => 'Minimum 3 Characters',
				'address_line2.max' => 'Maximum 255 Characters',
				// 'pincode.required' => 'Pincode is Required',
				// 'pincode.max' => 'Maximum 6 Characters',
				// 'pincode.min' => 'Minimum 6 Characters',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:roles,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => 'required|max:255|min:3',
				'gst_number' => 'required|max:191',
				'mobile_no' => 'nullable|max:25',
				// 'email' => 'nullable',
				'address' => 'required',
				'address_line1' => 'required|max:255|min:3',
				'address_line2' => 'max:255',
				// 'pincode' => 'required|max:6|min:6',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$role = new Role;
				$role->created_by_id = Auth::user()->id;
				$role->created_at = Carbon::now();
				$role->updated_at = NULL;
				$address = new Address;
			} else {
				$role = Role::withTrashed()->find($request->id);
				$role->updated_by_id = Auth::user()->id;
				$role->updated_at = Carbon::now();
				$address = Address::where('address_of_id', 24)->where('entity_id', $request->id)->first();
			}
			$role->fill($request->all());
			$role->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$role->deleted_at = Carbon::now();
				$role->deleted_by_id = Auth::user()->id;
			} else {
				$role->deleted_by_id = NULL;
				$role->deleted_at = NULL;
			}
			$role->gst_number = $request->gst_number;
			$role->axapta_location_id = $request->axapta_location_id;
			$role->save();

			if (!$address) {
				$address = new Address;
			}
			$address->fill($request->all());
			$address->company_id = Auth::user()->company_id;
			$address->address_of_id = 24;
			$address->entity_id = $role->id;
			$address->address_type_id = 40;
			$address->name = 'Primary Address';
			$address->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['Role Details Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Role Details Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteRole($id) {
		$delete_status = Role::withTrashed()->where('id', $id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 24)->where('entity_id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}
