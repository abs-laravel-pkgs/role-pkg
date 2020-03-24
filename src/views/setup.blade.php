@if(config('custom.PKG_DEV'))
    <?php $role_pkg_prefix = '/packages/abs/role-pkg/src';?>
@else
    <?php $role_pkg_prefix = '';?>
@endif

<script type="text/javascript">

	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
	    //PROJECTS
	    when('/role-pkg/role/list', {
	        template: '<role-list></role-list>',
	        title: 'Roles',
	    }).
	    when('/role-pkg/role/add', {
	        template: '<role-form></role-form>',
	        title: 'Add Role',
	    }).
	    when('/role-pkg/role/edit/:id', {
	        template: '<role-form></role-form>',
	        title: 'Edit Role',
	    });
	}]);

    var role_list_template_url = "{{URL::asset($role_pkg_prefix.'/public/angular/role-pkg/pages/role/list.html')}}";
    var role_get_form_data_url = "{{url('role-pkg/role/get-form-data/')}}";
    var role_form_template_url = "{{URL::asset($role_pkg_prefix.'/public/angular/role-pkg/pages/role/form.html')}}";
    var role_delete_data_url = "{{url('role-pkg/role/delete/')}}";
    var role_view_template_url = "{{URL::asset($role_pkg_prefix.'/public/angular/role-pkg/pages/role/view.html')}}";
    var role_view_data_url = "{{url('role-pkg/role/view/')}}";
    var image_scr2 = "{{URL::asset('public/themes/".+$theme+."/img/content/arrow.svg')}}";
    var image_scr3 = "{{URL::asset('public/themes/".+$theme+."/img/content/arrow.svg')}}";
</script>
<script type="text/javascript" src="{{URL::asset($role_pkg_prefix.'/public/angular/role-pkg/pages/role/controller.js?v=2')}}"></script>
