<?php

namespace Abs\RolePkg;
use App\Company;
use App\Permission;
use Illuminate\Database\Eloquent\SoftDeletes;
use Zizaco\Entrust\EntrustRole;

class Role extends EntrustRole {
	use SoftDeletes;
	Protected $fillable = [
		'id',
		'company_id',
		'name',
		'display_order',
		'display_name',
		'description',
		'is_hidden',
		'created_by_id',
		'updated_by_id',
		'deleted_by_id',
		'fixed_roles',
	];
	public function users() {
		return $this->belongsToMany('App\User');
	}

	public function permissions() {
		return $this->belongsToMany('App\Permission', 'permission_role', 'role_id');
	}

	public function createdBy() {
		return $this->belongsTo('App\User', 'created_by_id', 'id');
	}

	public function updatedBy() {
		return $this->belongsTo('App\User', 'created_by_id', 'id');
	}

	public function deleteBy() {
		return $this->belongsTo('App\User', 'created_by_id', 'id');
	}

	public function company() {
		return $this->belongsTo('App\Company', 'company_id', 'id');
	}

	public static function addRole() {
		$role = new Role;
		$data['permission_group_list'] = Permission::select('id', 'display_name')->whereNull('parent_id')->get()->toArray();
		foreach ($data['permission_group_list'] as $key => $value) {
			$permission_group_id = $data['permission_group_list'][$key]['id'];
			$permission_list[$permission_group_id] = Permission::where('parent_id', $permission_group_id)
				->get()->toArray();
		}
		foreach ($permission_list as $key => $value) {
			if ($value) {
				foreach ($value as $key => $sub_menu) {
					$sub_list[$sub_menu['id']] = Permission::where('parent_id', $sub_menu['id'])
						->get()->toArray();
				}
			}
		}
		//dd($sub_list);
		$data['permission_list'] = $permission_list;
		$data['permission_sub_list'] = $sub_list;
		$data['selected_permissions'] = [];
		return $data;
	}

	public function applicableRoles() {
		return $this->belongsToMany('App\Role', 'ts_type_roles', 'role_id', 'type_id');
	}
	public static function checkRoleRecursively($parent_id = NULL, $action) {
		$permission_data_list = '';
		//dd($action);
		if ($action == 'View') {
			$check_disabled = "disabled";
		} else {
			$check_disabled = "";
		}
		// if ($parent_id == NULL) {
		// 	$parent_permission_group_list = Permission::select('parent_id', 'id', 'display_name')->where('parent_id', $parent_id)->where('id', 199)->get();
		// } else {
		$parent_permission_group_list = Permission::select('parent_id', 'id', 'display_name')->where('parent_id', $parent_id)->get();
		//}
		foreach ($parent_permission_group_list as $key => $value) {
			$child_data_list = Permission::where('parent_id', $value->id)->get();
			$permission_data_list .= '<li class="checkbox_parent checkbox1 roll-box"><div class="checkbox"><input type="checkbox" name="permission_ids[]" id="perm_' . $value->id . '" value="' . $value->id . '" ng-checked="valueChecked(' . $value->id . ')!=-1" class="parent_check parent_checkbox pc_' . $value->id . '" data-ids="' . $value->id . '" ' . $check_disabled . '><label for="perm_' . $value->id . '"><span class="check"></span>' . $value->display_name . '</label>';
			if (count($child_data_list) > 0) {
				$permission_data_list .= '<div class="down-btn-arrow"><span class="anchor-sub btn btn-dropdown"><i class="icon ion-ios-arrow-down" aria-hidden="true" ng-click="showChild(' . $value->id . ')"></i></span></div>';
			}
			$permission_data_list .= '</div><ul class="pcc_' . $value->id . ' permission-set-ul roll-box-sub-list" ng-show="show_child_' . $value->id . '">' . self::checkRoleRecursively($value->id, $action) . '</ul></li>';
		}
		return $permission_data_list;
	}

	public static function createFromCollection($records) {
		foreach ($records as $key => $record_data) {
			try {
				if (!$record_data->company) {
					continue;
				}
				$record = self::createFromObject($record_data);
			} catch (Exception $e) {
				dd($e);
			}
		}
	}

	public static function createFromObject($record_data) {
		$company = Company::where('code', $record_data->company)->first();
		$admin = $company->admin();

		$errors = [];
		if (!$company) {
			$company_id = $company->id;
		} else {
			$company_id = null;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'id' => $record_data->id,
		]);
		$record->name = $record_data->name;
		$record->display_name = $record_data->name;
		$record->save();
		return $record;
	}

	public static function mapPermissions($records) {
		foreach ($records as $key => $record_data) {
			try {
				if (!$record_data->role) {
					continue;
				}
				$record = self::mapPermission($record_data);
			} catch (Exception $e) {
				dd($e);
			}
		}
	}

	public static function mapPermission($record_data) {
		$errors = [];
		$role = Role::where('name', $record_data->role)->first();
		if (!$role) {
			$errors[] = 'Invalid role : ' . $record_data->role;
		}

		$permission = Permission::where('name', $record_data->permission)->first();
		if (!$permission) {
			$errors[] = 'Invalid permission : ' . $record_data->permission;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$role->perms()->syncWithoutDetaching([$permission->id]);

		return $role;
	}

	public static function createFromName($data) {
		$role = self::firstOrNew([
			'name' => $data['name'],
		]);
		$role->is_hidden = 0;
		$role->save();

		dump($role->toArray());
	}
}
