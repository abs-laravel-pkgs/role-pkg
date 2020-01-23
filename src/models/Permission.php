<?php

namespace Abs\RolePkg;

use Abs\HelperPkg\Traits\PermissionTrait;
use Zizaco\Entrust\EntrustPermission;

class Permission extends EntrustPermission {
	use PermissionTrait;
	protected $fillable = [
		'is_for_mobile',
		'parent_id',
		'display_order',
		'name',
		'display_name',
		'description',
	];
	public function childs() {
		return $this->hasMany('App\Permission', 'parent_id', 'id')->orderBy('display_order');
	}

	public static function delete_permission($permission_data, $parent_id) {
		if ($parent_id) {
			$permission = Permission::where('name', $permission_data['name'])->forceDelete();
		} else {
			$permission = Permission::where('name', $permission_data['name'])->first();
		}

		if (isset($permission_data['sub_permissions'])) {
			foreach ($permission_data['sub_permissions'] as $key => $sub_permission) {
				Self::delete_permission($sub_permission, $permission->id);
			}
		}

	}

	public static function create_permission($permission_data, $parent_id) {
		$permission = Permission::where('name', $permission_data['name'])->first();
		if (!$permission) {
			$permission = Permission::create([
				'parent_id' => $parent_id,
				'name' => $permission_data['name'],
				'display_name' => $permission_data['display_name'],
				'route' => '',
			]);
		}

		if (isset($permission_data['sub_permissions'])) {
			foreach ($permission_data['sub_permissions'] as $key => $sub_permission) {
				Self::create_permission($sub_permission, $permission->id);
			}
		}
	}

	public function display() {
		$permission_item = '<li>';
		$permission_item .= $this->display_name;
		if (count($this->childs) > 0) {
			$permission_item .= '<ul style="margin-left:20px;">';
			foreach ($this->childs as $child) {
				$permission_item .= $child->display();
			}
			$permission_item .= '</ul>';

		}

		$permission_item .= '</li>';
		return $permission_item;

	}

}
