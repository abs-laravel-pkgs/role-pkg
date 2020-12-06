<?php

namespace Abs\RolePkg\Controllers\Api;
use Abs\BasicPkg\Controllers\Api\BaseController;
use Abs\BasicPkg\Traits\CrudTrait;
use App\Models\Masters\Auth\Permission;

class PermissionController extends BaseController {
	use CrudTrait;
	public $model = Permission::class;
}
