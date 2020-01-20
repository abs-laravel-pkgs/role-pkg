@if(config('custom.PKG_DEV'))
    <?php $role_pkg_prefix = '/packages/abs/role-pkg/src';?>
@else
    <?php $role_pkg_prefix = '';?>
@endif

<script type="text/javascript">
    var role_list_template_url = "{{URL::asset($role_pkg_prefix.'/public/angular/role-pkg/pages/role/list.html')}}";
    var role_get_form_data_url = "{{url('role-pkg/role/get-form-data/')}}";
    var role_form_template_url = "{{URL::asset($role_pkg_prefix.'/public/angular/role-pkg/pages/role/form.html')}}";
    var role_delete_data_url = "{{url('role-pkg/role/delete/')}}";
</script>
<script type="text/javascript" src="{{URL::asset($role_pkg_prefix.'/public/angular/role-pkg/pages/role/controller.js?v=2')}}"></script>
