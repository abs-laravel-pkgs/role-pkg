<?php

use App\Http\Controllers\Api\Masters\Auth\PermissionController;
use App\Http\Controllers\Api\Masters\Auth\RoleController;

Route::group(['middleware' => ['api']], function () {
	Route::group(['prefix' => '/api/master/auth/role'], function () {
		$className = RoleController::class;
		Route::get('index', $className.'@index');
		Route::get('read/{id}', $className.'@read');
		Route::post('save', $className.'@save');
		Route::get('options', $className.'@options');
		Route::get('delete/{User}', $className.'@delete');
	});

	Route::group(['prefix' => '/api/master/auth/permission'], function () {
		$className = PermissionController::class;
		Route::get('index', $className.'@index');
		Route::get('read/{id}', $className.'@read');
		Route::post('save', $className.'@save');
		Route::get('options', $className.'@options');
		Route::get('delete/{User}', $className.'@delete');
	});
});
