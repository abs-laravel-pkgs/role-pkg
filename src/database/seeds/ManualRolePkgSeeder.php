<?php
namespace Abs\RolePkg\Database\Seeds;

use Abs\RolePkg\Role;
use Illuminate\Database\Seeder;

class ManualRolePkgSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$no_of_records = $this->command->ask("How many records you want to create?", '1');

		for ($i = 1; $i <= $no_of_records; $i++) {
			$data = [];
			$data['name'] = $this->command->ask("Enter name?", 'Super Admin');
			Role::createFromName($data);
		}
	}
}
