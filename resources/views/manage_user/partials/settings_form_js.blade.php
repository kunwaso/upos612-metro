<script type="text/javascript">
    $(document).ready(function() {
        __page_leave_confirmation('#user_edit_form');

        $('#selected_contacts').on('ifChecked', function(event) {
            $('div.selected_contacts_div').removeClass('hide');
        });
        $('#selected_contacts').on('ifUnchecked', function(event) {
            $('div.selected_contacts_div').addClass('hide');
        });

        $('#is_enable_service_staff_pin').on('ifChecked', function(event) {
            $('div.service_staff_pin_div').removeClass('hide');
        });

        $('#is_enable_service_staff_pin').on('ifUnchecked', function(event) {
            $('div.service_staff_pin_div').addClass('hide');
            $('#service_staff_pin').val('');
        });

        $('#allow_login').on('ifChecked', function(event) {
            $('div.user_auth_fields').removeClass('hide');
        });
        $('#allow_login').on('ifUnchecked', function(event) {
            $('div.user_auth_fields').addClass('hide');
        });

        $('#user_allowed_contacts').select2({
            ajax: {
                url: '/contacts/customers',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        page: params.page,
                        all_contact: true
                    };
                },
                processResults: function(data) {
                    return {
                        results: data,
                    };
                },
            },
            templateResult: function(data) {
                var template = '';
                if (data.supplier_business_name) {
                    template += data.supplier_business_name + "<br>";
                }
                template += data.text + "<br>" + LANG.mobile + ": " + data.mobile;

                return template;
            },
            minimumInputLength: 1,
            escapeMarkup: function(markup) {
                return markup;
            },
        });
    });

    $('form#user_edit_form').validate({
        rules: {
            first_name: {
                required: true,
            },
            email: {
                email: true,
                remote: {
                    url: "/business/register/check-email",
                    type: "post",
                    data: {
                        email: function() {
                            return $("#email").val();
                        },
                        user_id: {{$user->id}}
                    }
                }
            },
            password: {
                minlength: 5
            },
            confirm_password: {
                equalTo: "#password",
            },
            username: {
                minlength: 5,
                remote: {
                    url: "/business/register/check-username",
                    type: "post",
                    data: {
                        username: function() {
                            return $("#username").val();
                        },
                        @if(!empty($username_ext))
                            username_ext: "{{$username_ext}}"
                        @endif
                    }
                }
            }
        },
        messages: {
            password: {
                minlength: 'Password should be minimum 5 characters',
            },
            confirm_password: {
                equalTo: 'Should be same as password'
            },
            username: {
                remote: 'Invalid username or User already exist'
            },
            email: {
                remote: '{{ __("validation.unique", ["attribute" => __("business.email")]) }}'
            }
        }
    });
</script>
