/*Compile*/
app.directive('compile', ['$compile', function($compile) {
    return function(scope, element, attrs) {
        scope.$watch(
            function(scope) {
                return scope.$eval(attrs.compile);
            },
            function(value) {
                element.html(value);
                $compile(element.contents())(scope);
            }
        )
    };
}]);

app.component('roleList', {
    templateUrl: role_list_template_url,
    controller: function($scope, $http, HelperService) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#list_table').DataTable({
            "dom": dom_structure,
            "language": {
                "search": "",
                "searchPlaceholder": "Search",
                "lengthMenu": "Rows Per Page _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            stateSave: true,
            pageLength: 10,
            processing: true,
            serverSide: true,
            paging: true,
            ordering: false,
            ajax: {
                url: laravel_routes['getRoleList'],
                data: function(d) {}
            },
            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'role', name: 'roles.display_name', searchable: true },
                { data: 'description', searchable: false },
                { data: 'status', name: 'status', searchable: false },
            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(total + '/' + max)
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            },
            initComplete: function() {
                $('.search label input').focus();
            },
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('Roles <span class="badge badge-secondary" id="table_info">0</span>');
        $('.page-header-content .search.display-inline-block .add_close_button').html('<button type="button" class="btn btn-img btn-add-close"><img src="' + image_scr2 + '" class="img-responsive"></button>');
        $('.page-header-content .refresh.display-inline-block').html('<button type="button" class="btn btn-refresh"><img src="' + image_scr3 + '" class="img-responsive"></button>');
        $('.add_new_button').html(
            '<a href="#!/role-pkg/role/add" type="button" class="btn btn-secondary" dusk="add-btn">' +
            'Add Role' +
            '</a>'
        );

        //var addnew_block = $('#add_new_wrap').html();
        //$('.page-header-content .alignment-right .add_new_button').html(addnew_block);

        $('.btn-add-close').on("click", function() {
            $('#list_table').DataTable().search('').draw();
        });

        $('.btn-refresh').on("click", function() {
            $('#list_table').DataTable().ajax.reload();
        });

        $scope.deleteRoleconfirm = function($id) {
            $('#role_id').val($id);
        }

        $scope.deleteRole = function() {
            var id = $('#role_id').val();
            $http.get(
                role_delete_data_url + '/' + id,
            ).then(function(response) {
                //console.log(response);
                if (response.data.success) {
                    custom_noty('success', response.data.message);
                    $('#list_table').DataTable().ajax.reload();
                    $scope.$apply();
                } else {
                    custom_noty('error', errors);
                }
            });
        }
    }
});
// //------------------------------------------------------------------------------------------------------------------------
// //------------------------------------------------------------------------------------------------------------------------
app.component('roleForm', {
    templateUrl: role_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        get_form_data_url = typeof($routeParams.id) == 'undefined' ? role_get_form_data_url : role_get_form_data_url + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            get_form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                $location.path('/role-pkg/role/list')
                $scope.$apply()
                return;
            }
            // console.log(response.data.selected_permissions);
            console.log(response.data);
            self.status = response.data.status;
            self.role = response.data.role;
            self.action = response.data.action;
            self.company_list = response.data.company_list;
            self.role_image = response.data.role;
            self.selected_permissions = response.data.selected_permissions;
            selected_permissions = response.data.selected_permissions;
            self.parent_permission_group_list = response.data.parent_permission_group_list;
            self.permission_list = response.data.permission_list;
            self.permission_sub_list = response.data.permission_sub_list;
            self.permission_sub_child_list = response.data.permission_sub_child_list;
            self.parent_group_list = response.data.parent_group_list;
            console.log(self.parent_group_list);
            $rootScope.loading = false;
        });

        $("input:text:visible:first").focus();
        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {

                'display_name': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'permission_id': {
                    required: true,
                },
                'description': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
            },
            errorPlacement: function(error, element) {
                if (element.hasClass("parent_check")) {
                    error.appendTo($('.permission_errors'));
                } else {
                    error.insertAfter(element)
                }
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveRole'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        // alert();
                        console.log(res.success);
                        if (!res.success) {
                            $('#submit').button('reset');
                            var errors = '';
                            for (var i in res.errors) {
                                errors += '<li>' + res.errors[i] + '</li>';
                            }
                            custom_noty('error', errors);
                        } else {
                            custom_noty('success', res.message);
                            $location.path('/role-pkg/role/list')
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });
        $scope.myFunction1 = function(id) {
            $scope["show_grand_child2_" + id] = $scope["show_grand_child2_" + id] ? false : true;

            if ($scope["show_grand_child2_" + id] == true) {
                $($scope["show_grand_child2_" + id]).removeClass('fa-plus');
                $($scope["show_grand_child2_" + id]).addClass('fa-minus');
            } else {
                $($scope["show_grand_child2_" + id].target).addClass('fa-plus');
                $($scope["show_grand_child2_" + id].target).removeClass('fa-minus');
            }
        }
        $scope.myFunction = function(id) {



            $scope["show_grand_child_" + id] = $scope["show_grand_child_" + id] ? false : true;
            /*$scope.selectMe = function (event){
               $(event.target).addClass('active');
            }*/
            if ($scope["show_grand_child_" + id] == true) {
                $($scope["show_grand_child_" + id]).removeClass('fa-plus');
                $($scope["show_grand_child_" + id]).addClass('fa-minus');
            } else {
                $($scope["show_grand_child_" + id].target).addClass('fa-plus');
                $($scope["show_grand_child_" + id].target).removeClass('fa-minus');
            }
        }
        $scope.showChild = function(id) {
            //alert(id);
            $scope["show_child_" + id] = $scope["show_child_" + id] ? false : true;

        }

        $scope.valueChecked = function(id) {
            //alert(id);
            var value = selected_permissions.indexOf(id);
            return value;
        }
        $(document).on("change", ".parent_checkbox", function() {
            // var id = $(this).data('ids');
            $(this).parent().next().find('input[type=checkbox]').prop('checked', this.checked);
            // var uncheck_child = $(this).parent().parent().siblings().find('input[type=checkbox]:checked').length;
            var children_uncheck = $(this).parentsUntil('ul.n_roles').siblings('li').find(':checked').length;
            // go up the hierarchy - and check/uncheck depending on number of children checked/unchecked
            $(this).parents('ul').prev('div').find('input[type=checkbox]').prop('checked', function() {
                if ($(this).prop('checked') == true && children_uncheck == 0) {
                    return $(this).parent().prev().find(':checked').length;
                }
                return $(this).parent().next().find(':checked').length;
            });
            // if ($(this).prop("checked") == true) {
            //     // $('.sub_childs_' + id).prop('checked', 'checked');
            //     // $('.sub_childs_test_' + id).prop('checked', 'checked');
            // } else if ($(this).prop("checked") == false) { //alert('uncheck');
            //     // $('.sub_childs_' + id).prop('checked', '');
            //     // $('.sub_childs_test_' + id).prop('checked', '');
            // }
        });

        // $(document).on("click", ".sub_parent", function() {
        //     var id = $(this).data('ids');
        //     var c = $(this).attr('checked');
        //     if ($(this).prop("checked") == true) {
        //         $('.sub_parent_childs_' + id).prop('checked', 'checked');
        //         $('.sub_parent_childs2_' + id).prop('checked', 'checked');
        //     } else if ($(this).prop("checked") == false) {
        //         $('.sub_parent_childs_' + id).prop('checked', '');
        //         $('.sub_parent_childs2_' + id).prop('checked', '');
        //     }
        // });


        // $(document).on("click", ".check_its_child", function() {
        //     var id = $(this).data('item');
        //     var c = $(this).attr('checked');
        //     if ($(this).prop("checked") == true) {
        //         $('.childs2_' + id).prop('checked', 'checked');
        //     } else if ($(this).prop("checked") == false) {
        //         $('.childs2_' + id).prop('checked', '');
        //     }
        // });

        // $(document).on("change", ".permission_check_class", function() {
        //     var parent_count = 0;
        //     $(this).parents('li').find('.permission_check_class').each(function() {
        //         if ($(this).is(":checked")) {
        //             // console.log(' == parent count checked ===');
        //             parent_count = 1;
        //         }
        //     });

        //     if (parent_count == 0) {
        //         // console.log(' == parent count 0 ===');
        //         $(this).parents('li').find('.parent_check').prop('checked', false);
        //     } else {
        //         $(this).parents('li').find('.parent_check').prop('checked', true);
        //     }
        // });

        // $(document).on("change", ".sub_child", function() {

        //     ids = $(this).data("ids");
        //     id = ids.split("_");
        //     var sub_parent_count = 0;
        //     if ($(this).is(":checked")) {

        //         $('.pc_' + id[1]).prop('checked', true);
        //         $('.sc_' + id[0]).prop('checked', true);
        //         $('.childs2_' + id[2]).prop('checked', true);
        //     } else {
        //         var countCheckedCheckboxes = 0;
        //         $(this).parents('li').find('.sub_child').each(function() {
        //             countCheckedCheckboxes = $(this).parents('li').find('.sub_parent_childs_' + id[0]).filter(':checked').length;
        //         });

        //         if (countCheckedCheckboxes == 0) {

        //             var subCheckedCheckboxes = 0;
        //             $('.sc_' + id[0]).prop('checked', false);
        //             $('.childs2_' + id[2]).prop('checked', false);
        //             $(this).parents('li').find('.permission_check_class').each(function() {

        //                 subCheckedCheckboxes = $(this).parents('li').find('.sub_test_' + id[1]).filter(':checked').length;
        //                 subCheckedcount = $(this).parents('li').find('.sc_' + id[0]).filter(':checked').length;

        //             });


        //         }

        //     }


        // });

        // $(document).on("change", ".super_sub_child", function() {

        //     ids = $(this).data("ids");
        //     id = ids.split("_");
        //     // console.log(ids);
        //     if ($(this).is(":checked")) {
        //         $('.pc_' + id[1]).prop('checked', true);
        //         $('.sc_' + id[0]).prop('checked', true);
        //         $('.child_' + id[2]).prop('checked', true);
        //     } else {
        //         var countCheckedCheckboxes1 = 0;
        //         $(this).parents('li').find('.super_sub_child').each(function() {
        //             countCheckedCheckboxes1 = $(this).parents('li').find('.childs2_' + id[2]).filter(':checked').length;
        //         });
        //         if (countCheckedCheckboxes1 == 0) {
        //             $('.child_' + id[2]).prop('checked', false);
        //         }

        //         var countCheckedCheckboxes2 = 0;
        //         $(this).parents('li').find('.check_its_child').each(function() {
        //             countCheckedCheckboxes2 = $(this).parents('li').find('.sub_parent_childs_' + id[0]).filter(':checked').length;
        //         });
        //         if (countCheckedCheckboxes2 == 0) {
        //             $('.sc_' + id[0]).prop('checked', false);
        //         }

        //         var countCheckedCheckboxes3 = 0;



        //         //  $(this).parents('li').find('.permission_check_class').each(function() {
        //         //      countCheckedCheckboxes3 = $(this).parents('li').find('.sc_' + id[0]).filter(':checked').length;
        //         // });
        //         //  if(countCheckedCheckboxes3 == 0)
        //         //  {
        //         //     $('.pc_' + id[1]).prop('checked', false);
        //         //  }


        //         // console.log(countCheckedCheckboxes2);


        //         // var countCheckedCheckboxes1 = 0;
        //         // $(this).parents('li').find('.super_sub_child').each(function() {
        //         //      countCheckedCheckboxes1 = $(this).parents('li').find('.childs2_' + id[2]).filter(':checked').length;
        //         // });

        //         // if (countCheckedCheckboxes1 == 0) {

        //         //     var subCheckedCheckboxes1 = 0;

        //         //     $('.child_' + id[2]).prop('checked', false);

        //         //     $(this).parents('li').find('.check_its_child').each(function() {


        //         //     subCheckedCheckboxes1 = $(this).parents('li').find('.sub_childs_test_' + id[1]).filter(':checked').length;
        //         //     // if(subCheckedCheckboxes1){
        //         //     //     subCheckedCheckboxes1 = subCheckedCheckboxes1;
        //         //     // }else{
        //         //     //     subCheckedCheckboxes1 =1;
        //         //     // }
        //         // });
        //         //      console.log("super_sub"+subCheckedCheckboxes1);
        //         //   // console.log("super_sub"+subCheckedCheckboxes1);
        //         //     if(subCheckedCheckboxes1 == 0){
        //         //         // alert("suppp");

        //         //         // $('.pc_' + id[1]).prop('checked', false);
        //         //         $('.sc_' + id[0]).prop('checked', false);
        //         //     }

        //         // } 
        //     }

        // });

        $scope.selectChilds = function(id) {

            // var value = selected_permissions.indexOf(id);
            // console.log(value);
            // return value;
        }
    }
});
// //------------------------------------------------------------------------------------------------------------------------
// //------------------------------------------------------------------------------------------------------------------------
app.component('roleView', {
    templateUrl: role_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        get_view_data_url = role_view_data_url + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            get_view_data_url
        ).then(function(response) {
            if (!response.data.success) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                $location.path('/role-pkg/role/list')
                $scope.$apply()
                return;
            }
            // console.log(response.data);
            self.status = response.data.status;
            self.role = response.data.role;
            self.action = response.data.action;
            self.company_list = response.data.company_list;
            self.role_image = response.data.role;
            self.selected_permissions = response.data.selected_permissions;
            selected_permissions = response.data.selected_permissions;
            self.parent_permission_group_list = response.data.parent_permission_group_list;
            self.permission_list = response.data.permission_list;
            self.permission_sub_list = response.data.permission_sub_list;
            self.permission_sub_child_list = response.data.permission_sub_child_list;
            self.parent_group_list = response.data.parent_group_list;
            // console.log(self.permission_sub_child_list);
            if (self.role.deleted_at == null) {
                self.status = "Active";
            } else {
                self.status = "Inactive";
            }
            $rootScope.loading = false;
        });
        $scope.myFunction1 = function(id) {
            $scope["show_grand_child2_" + id] = $scope["show_grand_child2_" + id] ? false : true;

            if ($scope["show_grand_child2_" + id] == true) {
                $($scope["show_grand_child2_" + id]).removeClass('fa-plus');
                $($scope["show_grand_child2_" + id]).addClass('fa-minus');
            } else {
                $($scope["show_grand_child2_" + id].target).addClass('fa-plus');
                $($scope["show_grand_child2_" + id].target).removeClass('fa-minus');
            }
        }
        $scope.myFunction = function(id) {



            $scope["show_grand_child_" + id] = $scope["show_grand_child_" + id] ? false : true;
            /*$scope.selectMe = function (event){
               $(event.target).addClass('active');
            }*/
            if ($scope["show_grand_child_" + id] == true) {
                $($scope["show_grand_child_" + id]).removeClass('fa-plus');
                $($scope["show_grand_child_" + id]).addClass('fa-minus');
            } else {
                $($scope["show_grand_child_" + id].target).addClass('fa-plus');
                $($scope["show_grand_child_" + id].target).removeClass('fa-minus');
            }
        }
        $scope.showChild = function(id) {
            $scope["show_child_" + id] = $scope["show_child_" + id] ? false : true;

        }

        $scope.valueChecked = function(id) {
            var value = selected_permissions.indexOf(id);
            return value;
        }

        $(document).on("change", ".parent_checkbox", function() {
            // var id = $(this).data('ids');
            $(this).parent().next().find('input[type=checkbox]').prop('checked', this.checked);
            // var uncheck_child = $(this).parent().parent().siblings().find('input[type=checkbox]:checked').length;
            var children_uncheck = $(this).parentsUntil('ul.n_roles').siblings('li').find(':checked').length;
            // go up the hierarchy - and check/uncheck depending on number of children checked/unchecked
            $(this).parents('ul').prev('div').find('input[type=checkbox]').prop('checked', function() {
                if ($(this).prop('checked') == true && children_uncheck == 0) {
                    return $(this).parent().prev().find(':checked').length;
                }
                return $(this).parent().next().find(':checked').length;
            });
        });

        // $(document).on("click", ".parent_checkbox", function() {
        //     var id = $(this).data('ids');
        //     var c = $(this).attr('checked');
        //     //parent_id = id.split('_');
        //     if ($(this).prop("checked") == true) {
        //         $('.sub_childs_' + id).prop('checked', 'checked');
        //         $('.sub_childs_test_' + id).prop('checked', 'checked');

        //     } else if ($(this).prop("checked") == false) {
        //         $('.sub_childs_' + id).prop('checked', '');
        //         $('.sub_childs_test_' + id).prop('checked', '');

        //     }
        // });

        // $(document).on("click", ".sub_parent", function() {
        //     var id = $(this).data('ids');
        //     var c = $(this).attr('checked');
        //     if ($(this).prop("checked") == true) {
        //         $('.sub_parent_childs_' + id).prop('checked', 'checked');
        //         $('.sub_parent_childs2_' + id).prop('checked', 'checked');
        //     } else if ($(this).prop("checked") == false) {
        //         $('.sub_parent_childs_' + id).prop('checked', '');
        //         $('.sub_parent_childs2_' + id).prop('checked', '');
        //     }
        // });


        // $(document).on("click", ".check_its_child", function() {
        //     var id = $(this).data('item');
        //     var c = $(this).attr('checked');
        //     if ($(this).prop("checked") == true) {
        //         $('.childs2_' + id).prop('checked', 'checked');
        //     } else if ($(this).prop("checked") == false) {
        //         $('.childs2_' + id).prop('checked', '');
        //     }
        // });

        // $(document).on("change", ".permission_check_class", function() {
        //     var parent_count = 0;
        //     $(this).parents('li').find('.permission_check_class').each(function() {
        //         if ($(this).is(":checked")) {
        //             // console.log(' == parent count checked ===');
        //             parent_count = 1;
        //         }
        //     });

        //     if (parent_count == 0) {
        //         // console.log(' == parent count 0 ===');
        //         $(this).parents('li').find('.parent_check').prop('checked', false);
        //     } else {
        //         $(this).parents('li').find('.parent_check').prop('checked', true);
        //     }
        // });

        // $(document).on("change", ".sub_child", function() {

        //     ids = $(this).data("ids");
        //     id = ids.split("_");
        //     var sub_parent_count = 0;
        //     if ($(this).is(":checked")) {

        //         $('.pc_' + id[1]).prop('checked', true);
        //         $('.sc_' + id[0]).prop('checked', true);
        //         $('.childs2_' + id[2]).prop('checked', true);
        //     } else {
        //         var countCheckedCheckboxes = 0;
        //         $(this).parents('li').find('.sub_child').each(function() {
        //             countCheckedCheckboxes = $(this).parents('li').find('.sub_parent_childs_' + id[0]).filter(':checked').length;
        //         });

        //         if (countCheckedCheckboxes == 0) {

        //             var subCheckedCheckboxes = 0;
        //             $('.sc_' + id[0]).prop('checked', false);
        //             $('.childs2_' + id[2]).prop('checked', false);
        //             $(this).parents('li').find('.permission_check_class').each(function() {

        //                 subCheckedCheckboxes = $(this).parents('li').find('.sub_test_' + id[1]).filter(':checked').length;
        //                 subCheckedcount = $(this).parents('li').find('.sc_' + id[0]).filter(':checked').length;

        //             });
        //         }
        //     }
        // });

        // $(document).on("change", ".super_sub_child", function() {

        //     ids = $(this).data("ids");
        //     id = ids.split("_");
        //     // console.log(ids);
        //     if ($(this).is(":checked")) {
        //         $('.pc_' + id[1]).prop('checked', true);
        //         $('.sc_' + id[0]).prop('checked', true);
        //         $('.child_' + id[2]).prop('checked', true);
        //     } else {
        //         var countCheckedCheckboxes1 = 0;
        //         $(this).parents('li').find('.super_sub_child').each(function() {
        //             countCheckedCheckboxes1 = $(this).parents('li').find('.childs2_' + id[2]).filter(':checked').length;
        //         });
        //         if (countCheckedCheckboxes1 == 0) {
        //             $('.child_' + id[2]).prop('checked', false);
        //         }

        //         var countCheckedCheckboxes2 = 0;
        //         $(this).parents('li').find('.check_its_child').each(function() {
        //             countCheckedCheckboxes2 = $(this).parents('li').find('.sub_parent_childs_' + id[0]).filter(':checked').length;
        //         });
        //         if (countCheckedCheckboxes2 == 0) {
        //             $('.sc_' + id[0]).prop('checked', false);
        //         }

        //         var countCheckedCheckboxes3 = 0;
        //     }

        // });

        $scope.selectChilds = function(id) {}
    }
});
