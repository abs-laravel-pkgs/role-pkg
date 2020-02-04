<?php
namespace Abs\RolePkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class RolePkgPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//ROLES
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'roles',
				'display_name' => 'Roles',
			],
			[
				'display_order' => 1,
				'parent' => 'roles',
				'name' => 'add-role',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'roles',
				'name' => 'edit-role',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'roles',
				'name' => 'delete-role',
				'display_name' => 'Delete',
			],

		];
		Permission::createFromArrays($permissions);
	}
}