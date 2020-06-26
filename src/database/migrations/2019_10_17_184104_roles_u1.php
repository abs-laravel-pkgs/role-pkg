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
		Schema::table('users', function (Blueprint $table) {
			$table->increments('id')->change();
		});

		// Create table for storing roles
		if (!Schema::hasTable('roles')) {
			Schema::create('roles', function (Blueprint $table) {
				$table->increments('id');
				$table->string('name')->unique();
				$table->string('display_name')->nullable();
				$table->string('description')->nullable();
				$table->timestamps();
			});
		}
		// Create table for associating roles to users (Many-to-Many)
		if (!Schema::hasTable('role_user')) {
			Schema::create('role_user', function (Blueprint $table) {
				$table->integer('user_id')->unsigned();
				$table->integer('role_id')->unsigned();

				$table->foreign('user_id')->references('id')->on('users')
					->onUpdate('cascade')->onDelete('cascade');
				$table->foreign('role_id')->references('id')->on('roles')
					->onUpdate('cascade')->onDelete('cascade');

				$table->primary(['user_id', 'role_id']);
			});
		}

		// Create table for storing permissions
		if (!Schema::hasTable('permissions')) {
			Schema::create('permissions', function (Blueprint $table) {
				$table->increments('id');
				$table->string('name')->unique();
				$table->string('display_name')->nullable();
				$table->string('description')->nullable();
				$table->timestamps();
			});
		}
		// Create table for associating permissions to roles (Many-to-Many)
		if (!Schema::hasTable('permission_role')) {
			Schema::create('permission_role', function (Blueprint $table) {
				$table->integer('permission_id')->unsigned();
				$table->integer('role_id')->unsigned();

				$table->foreign('permission_id')->references('id')->on('permissions')
					->onUpdate('cascade')->onDelete('cascade');
				$table->foreign('role_id')->references('id')->on('roles')
					->onUpdate('cascade')->onDelete('cascade');

				$table->primary(['permission_id', 'role_id']);
			});
		}
		Schema::table('roles', function (Blueprint $table) {
			$table->unsignedInteger('company_id')->nullable()->after('id');
			$table->unsignedMediumInteger('display_order')->default(0)->after('name');
			$table->boolean('is_hidden')->after('description');
			$table->unsignedInteger('created_by_id')->nullable()->after('is_hidden');
			$table->unsignedInteger('updated_by_id')->nullable()->after('created_by_id');
			$table->unsignedInteger('deleted_by_id')->nullable()->after('updated_by_id');
			$table->softdeletes();

			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');

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

		Schema::drop('permission_role');
		Schema::drop('permissions');
		Schema::drop('role_user');
		Schema::drop('roles');

	}
}
