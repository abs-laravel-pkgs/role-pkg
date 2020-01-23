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
		'display_order',
		'name',
		'display_name',
		'description',
		'fixed_roles',
		'created_by',
	];
	public function users() {
		return $this->belongsToMany('App\User');
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
	public function permissions() {
		return $this->belongsToMany('App\Permission', 'permission_role', 'role_id');
	}

	public function applicableRoles() {
		return $this->belongsToMany('App\Role', 'ts_type_roles', 'role_id', 'type_id');
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

}
