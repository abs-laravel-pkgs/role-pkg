<?php

use Illuminate\Database\Seeder;

class ManualRoleSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$this->call(Abs\ManualRolePkg\Database\Seeds\ManualRolePkgSeeder::class);
	}
}
