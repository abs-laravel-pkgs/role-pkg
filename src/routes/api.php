<?php
Route::group(['namespace' => 'Abs\RolePkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'role-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});