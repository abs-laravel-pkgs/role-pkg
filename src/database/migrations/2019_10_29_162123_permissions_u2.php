<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PermissionsU2 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('permissions', function (Blueprint $table) {
			$table->unsignedInteger('parent_id')->nullable()->after('id');
			$table->unsignedMediumInteger('display_order')->default(999)->after('parent_id');
			$table->boolean('is_for_mobile')->default(0)->after('display_order');

			$table->foreign('parent_id')->references('id')->on('permissions')->onDelete('CASCADE')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('permissions', function (Blueprint $table) {
			$table->dropForeign('permissions_parent_id_foreign');

			$table->dropColumn('parent_id');
			$table->dropColumn('display_order');
			$table->dropColumn('is_for_mobile');
		});

	}
}
