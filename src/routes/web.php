<?php

Route::group(['namespace' => 'Abs\RolePkg', 'middleware' => ['web', 'auth'], 'prefix' => 'role-pkg'], function () {
	Route::get('/roles/get-list', 'RoleController@getRoleList')->name('getRoleList');
	Route::get('/role/get-form-data/{id?}', 'RoleController@getRoleFormData')->name('getRoleFormData');
	Route::post('/role/save', 'RoleController@saveRole')->name('saveRole');
	Route::get('/role/delete/{id}', 'RoleController@deleteRole')->name('deleteRole');

});