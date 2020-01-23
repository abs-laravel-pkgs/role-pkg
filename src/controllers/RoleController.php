<?php

namespace Abs\RolePkg;
use Abs\RolePkg\Role;
use App\Http\Controllers\Controller;
use App\Permission;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;
use Yajra\Datatables\Datatables;

class RoleController extends Controller {

	public function __construct() {
	}

	public function getRolesList(Request $request) {

		$roles = Role::withTrashed()->select('roles.id', 'roles.display_name as role', DB::raw('IF(roles.deleted_at IS NULL,"Active","Inactive") as status'),
			DB::raw('IF(roles.description IS NULL,"N/A",roles.description) as description'),
			'roles.fixed_roles')
			->orderBy('roles.display_order', 'ASC');
		return Datatables::of($roles)
			->addColumn('action', function ($roles) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img_delete = asset('public/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/img/content/table/delete-active.svg');
				$output = '';
				if ($roles->fixed_roles == 0) {
					$output .= '<a href="#!/role-pkg/role/edit/' . $roles->id . '" id = "" ><img src="' . $img1 . '" alt="Account Management" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a>
					<a href="#!/role-pkg/role/view/' . $roles->id . '" id = "" ><img src="' . $img2 . '" alt="Account Management" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '"></a>
					<a href="javascript:;"  data-toggle="modal" data-target="#role-delete-modal" onclick="angular.element(this).scope().deleteRoleconfirm(' . $roles->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>';
				} else {
					$output .= '<a href="#!/role-pkg/role/view/' . $roles->id . '" id = "" ><img src="' . $img2 . '" alt="Account Management" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '"></a>';
				}
				return $output;
			})
			->addColumn('status', function ($role) {
				$status = $role->status == 'Active' ? 'color-green' : 'color-red';
				return '<span class="status-indigator ' . $status . '">' . $role->status . '</span>';

			})
			->make(true);
	}

	public function getRoleFormData($id = NULL) {
		if (!$id) {
			$data['role'] = new Role;
			$this->data['status'] = 'Active';
			$data['action'] = 'Add';
			$data['selected_permissions'] = [];
		} else {
			$data['role'] = $role = Role::withTrashed()->where('id', $id)->first();
			if (!$data['role']) {
				return response()->json(['success' => false, 'error' => 'Roles Not Found']);
			}
			$data['selected_permissions'] = $role->permissions()->pluck('id')->toArray();
			$data['action'] = 'Edit';
			if ($role->deleted_at == NULL) {
				$this->data['status'] = 'Active';
			} else {
				$this->data['status'] = 'Inactive';
			}
		}
		$data['parent_permission_group_list'] = Permission::select('parent_id', 'id', 'display_name')->whereNull('parent_id')->get();
		foreach ($data['parent_permission_group_list'] as $key => $value) {
			$permission_group_id = $data['parent_permission_group_list'][$key]['id'];
			$permission_list[$permission_group_id] = Permission::where('parent_id', $permission_group_id)->get();

			foreach ($permission_list[$permission_group_id] as $permission_list_key => $permission_list_value) {
				$permission_group_sub_id = $permission_list_value['id'];
				$permission_sub_list[$permission_group_sub_id] = Permission::where('parent_id', $permission_group_sub_id)
				//->where('display_order', '!=', 0)
					->orderBy('display_order', 'ASC')->get();

				foreach ($permission_sub_list[$permission_group_sub_id] as $key => $sub_value) {
					$permission_group_sub_child_id = $sub_value['id'];
					$permission_sub_child_list[$permission_group_sub_child_id] = Permission::where('parent_id', $permission_group_sub_child_id)

						->orderBy('display_order', 'ASC')->get();

				}

			}

		}

		$data['permission_list'] = $permission_list;
		$data['permission_sub_list'] = $permission_sub_list;
		$data['permission_sub_child_list'] = $permission_sub_child_list;
		$data['success'] = true;
		return response()->json($data);
	}

	public function saveRole(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'display_name.required' => 'Role name is required',
				'display_name.unique' => 'Role name has already been taken',
				'display_name.max' => 'Maximum length of Role name is 255',
				'permission_id.required' => 'select atleast one page to set permission',
				'description.required' => 'Description is required',
				'description.max' => 'Maximum length of description is 255',

			];
			$validator = Validator::make($request->all(), [
				'display_name' => [
					'required',
					Rule::unique('roles')->ignore($request->id),
					'max:255',
				],
				'description' => [
					'required',
					'max:255',
				],
			]);
			DB::beginTransaction();
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			if (empty($request->id)) {
				$roles = new Role;
			} else {
				$roles = Role::withTrashed()->where('id', $request->id)->first();

				$roles->permissions()->sync([]);
			}
			if ($request->status == '1') {

				$roles->deleted_at = NULL;
				$roles->deleted_by = NULL;
			} else {
				$roles->deleted_at = date('Y-m-d H:i:s');
				$roles->deleted_by = Auth::user()->id;
			}
			// $role_name = ucfirst(str_replace(' ', '_', strtolower($request->display_name)));
			// dd($role_name);
			// $roles->name = $role_name;
			$roles->created_by = Auth::user()->id;
			$roles->fill($request->all());
			$roles->display_name = $request->display_name;
			$roles->name = $request->display_name;
			$roles->description = $request->description;
			// if ($request->deleted_at == 1) {
			// 	$roles->deleted_at = null;
			// } else {
			// 	$roles->deleted_at = date('Y-m-d');
			// }
			$roles->save();
			$roles->permissions()->attach($request->permission_ids);
			//dd($request->permission_ids);
			DB::commit();
			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'Role added successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Role updated successfully']);
			}
			// $request->session()->flash('success', 'Role is saved successfully');
			// return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function viewRole($id) {
		$data['role'] = $role = Role::withTrashed()->where('id', $id)->first();
		if (!$data['role']) {
			return response()->json(['success' => false, 'error' => 'Roles Not Found']);
		}
		$data['selected_permissions'] = $role->permissions()->pluck('id')->toArray();
		$data['action'] = 'Edit';
		if ($role->deleted_at == NULL) {
			$this->data['status'] = 'Active';
		} else {
			$this->data['status'] = 'Inactive';
		}
		$data['parent_permission_group_list'] = Permission::select('parent_id', 'id', 'display_name')->whereNull('parent_id')->get();
		foreach ($data['parent_permission_group_list'] as $key => $value) {
			$permission_group_id = $data['parent_permission_group_list'][$key]['id'];
			$permission_list[$permission_group_id] = Permission::where('parent_id', $permission_group_id)->get();

			foreach ($permission_list[$permission_group_id] as $permission_list_key => $permission_list_value) {
				$permission_group_sub_id = $permission_list_value['id'];
				$permission_sub_list[$permission_group_sub_id] = Permission::where('parent_id', $permission_group_sub_id)
				//->where('display_order', '!=', 0)
					->orderBy('display_order', 'ASC')->get();

				foreach ($permission_sub_list[$permission_group_sub_id] as $key => $sub_value) {
					$permission_group_sub_child_id = $sub_value['id'];
					$permission_sub_child_list[$permission_group_sub_child_id] = Permission::where('parent_id', $permission_group_sub_child_id)

						->orderBy('display_order', 'ASC')->get();

				}

			}

		}

		$data['permission_list'] = $permission_list;
		$data['permission_sub_list'] = $permission_sub_list;
		$data['permission_sub_child_list'] = $permission_sub_child_list;
		$data['action'] = 'View';
		$data['success'] = true;
		return response()->json($data);
	}
	public function deleteRole($id) {
		DB::beginTransaction();
		try {
			$delete_status = Role::withTrashed()->where('id', $id)->forceDelete();
			DB::commit();
			return response()->json(['success' => true, 'message' => 'Role deleted successfully']);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
