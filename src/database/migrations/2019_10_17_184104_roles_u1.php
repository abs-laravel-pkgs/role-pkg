<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RolesU1 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('roles', function (Blueprint $table) {
			$table->unsignedInteger('company_id')->nullable()->after('id');
			$table->unsignedMediumInteger('display_order')->default(0)->after('name');
			$table->boolean('is_hidden')->after('description');
			$table->unsignedInteger('created_by_id')->nullable()->after('is_hidden');
			$table->unsignedInteger('updated_by_id')->nullable()->after('created_by_id');
			$table->unsignedInteger('deleted_by_id')->nullable()->after('updated_by_id');
			$table->softdeletes();

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');

			$table->dropUnique("roles_name_unique");
			$table->unique(["company_id", "name"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('roles', function (Blueprint $table) {
			$table->dropForeign('roles_company_id_foreign');
			$table->dropForeign('roles_created_by_id_foreign');
			$table->dropForeign('roles_updated_by_id_foreign');
			$table->dropForeign('roles_deleted_by_id_foreign');

			$table->dropUnique('roles_company_id_name_unique');

			$table->dropColumn('company_id');
			$table->dropColumn('display_order');
			$table->dropColumn('created_by_id');
			$table->dropColumn('updated_by_id');
			$table->dropColumn('deleted_by_id');
			$table->dropColumn('deleted_at');
			$table->dropColumn('is_hidden');

			$table->unique(["name"]);

		});
	}
}
