<?php

namespace Abs\RolePkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Permission;
use Illuminate\Database\Eloquent\SoftDeletes;
use Zizaco\Entrust\EntrustRole;

class Role extends EntrustRole {
	use SoftDeletes;
	use SeederTrait;
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

	protected static $excelColumnRules = [
		'Name' => [
			'table_column_name' => 'name',
			'rules' => [
				'required' => [
				],
			],
		],
		'Display Name' => [
			'table_column_name' => 'display_name',
			'rules' => [
				'required' => [
				],
			],
		],
		'Description' => [
			'table_column_name' => 'description',
			'rules' => [
			],
		],
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


	public static function mapPermissions($records) {
		$success = 0;
		$error_records = [];
		foreach ($records as $key => $record_data) {
			try {
				if (!$record_data->role_name) {
					continue;
				}
				//$record = [
				//	'Role Name' => $record_data->role_name,
				//	'Permission Name' => $record_data->permission_name,
				//];
				//$result = self::mapPermission($record);

				$status = self::mapPermission($record_data);
				if (!$status['success']) {
					$error_records[] = array_merge($record_data->toArray(), [
						'Record No' => $key + 1,
						'Errors' => implode(',', $status['errors']),
					]);
					continue;
				}
				$success++;
			} catch (Exception $e) {
				dump($e);
			}
		}
		dump($success . ' Records Success');
		dump(count($error_records) . ' Errors');
		dump($error_records);
		return $error_records;

	}

	public static function mapPermission($record_data) {
		try {
			$errors = [];
			//$company = Company::where('code', $record_data['Company Code'])->first();
			//if (!$company) {
			//	return [
			//		'success' => false,
			//		'errors' => ['Invalid Company : ' . $record_data['Company Code']],
			//	];
			//}
			$role = Role::where('name', $record_data->role_name)->first();
			if (!$role) {
				$errors[] = 'Invalid role : ' . $record_data->role_name;
			}

			$permission = Permission::where('name', $record_data->permission_name)->first();
			if (!$permission) {
				$errors[] = 'Invalid permission : ' . $record_data->permission_name;
			}

			if (count($errors) > 0) {
				return [
					'success' => false,
					'errors' => $errors,
				];
			}
			$role->perms()->syncWithoutDetaching([$permission->id]);

			return [
				'success' => true,
			];
		} catch (\Exception $e) {
			return [
				'success' => false,
				'errors' => [
					$e->getMessage(),
				],
			];
		}
	}

	public static function createFromName($data) {
		$role = self::firstOrNew([
			'name' => $data['name'],
		]);
		$role->is_hidden = 0;
		$role->save();

		$permissions = Permission::pluck('id')->toArray();
		$role->perms()->sync($permissions);

		dump($role->toArray());
	}

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Name' => $record_data->name,
			'Display Name' => $record_data->display_name,
			'Description' => $record_data->description,
		];
		return static::saveFromExcelArray($record);
	}

	public static function saveFromExcelArray($record_data) {
		try {
			$errors = [];
			$company = Company::where('code', $record_data['Company Code'])->first();
			if (!$company) {
				return [
					'success' => false,
					'errors' => ['Invalid Company : ' . $record_data['Company Code']],
				];
			}

			if (!isset($record_data['created_by'])) {
				$admin = $company->admin();

				if (!$admin) {
					return [
						'success' => false,
						'errors' => ['Default Admin user not found'],
					];
				}
				$created_by_id = $admin->id;
			} else {
				$created_by_id = $record_data['created_by'];
			}

			if (count($errors) > 0) {
				return [
					'success' => false,
					'errors' => $errors,
				];
			}

			$record = Self::firstOrNew([
				'company_id' => $company->id,
				'name' => $record_data['Name'],
			]);

			$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
			if (!$result['success']) {
				return $result;
			}

			$record->display_name = $record_data['Display Name'];
			$record->description = $record_data['Description'];
			$record->company_id = $company->id;
			$record->created_by = $created_by_id;
			$record->save();
			return [
				'success' => true,
			];
		} catch (\Exception $e) {
			return [
				'success' => false,
				'errors' => [
					$e->getMessage(),
				],
			];
		}
	}
}
