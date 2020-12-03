<?php

namespace Abs\RolePkg\Controllers\Api;
use Abs\BasicPkg\Controllers\Api\BaseController;
use Abs\BasicPkg\Traits\CrudTrait;

class RoleController extends BaseController {
	use CrudTrait;
	public $model = 'App\Models\Masters\Auth\Role';
}
