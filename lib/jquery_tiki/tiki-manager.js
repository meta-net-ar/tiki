/**
 * (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 *
 */

(function () {
    var $container = $('.tiki-manager-command');

    $('#check', $container).hide();
    $('#copy', $container).show();

    $('#copy').tiki('copy')(() => $('#command', $container).html(), function() {
        $(this).hide();
        $('#check', $container).show();
    });

    var $form = $('#tiki-manager-create-instance');

    $('#host', $form).on('change', function() {
        $('#command', $container).text($('#command', $container).text().replace(/@.*$/, '@'+$(this).val()));
    });

    $('#user', $form).on('change', function() {
        $('#command', $container).text($('#command', $container).text().replace(/ [^ ]+@/, ' '+$(this).val()+'@'));
    });

    $('#port', $form).on('change', function() {
        $('#command', $container).text($('#command', $container).text().replace(/ -p [^ ]+/, ' -p '+$(this).val()));
    });

    var generate_password = function() {
        var length = 13;
        chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        password = "";
        for (var i = 0, n = chars.length; i < length; ++i) {
            password += chars.charAt(Math.floor(Math.random() * n));
        }
        return password;
    };

    function tikipassword_options() {
        $("#tikipassword").val(generate_password);

        $('#passwordgenerate').on("click", function() {
            $("#tikipassword").val(generate_password);
        });

        $('#togglepassword').on("click", function() {
            const tiki_password = document.getElementById('tikipassword');
            const icon_change = document.getElementById('icon-change');
            const type = tiki_password.getAttribute('type') === 'password' ? 'text' : 'password';
            tiki_password.setAttribute('type', type);
            if (type === 'text') {
                icon_change.classList.add("fa-eye-slash");
                icon_change.classList.remove("fa-eye");
            } else {
                icon_change.classList.add("fa-eye");
                icon_change.classList.remove("fa-eye-slash");
            }
        });
    }

    // Calling for Tiki Manager Package
    tikipassword_options();

    // Calling for Plugin TikiManager
    $('#bootstrap-modal').on("tiki.modal.redraw", function (e) {
        tikipassword_options();
        var $modal = $(e.target);
        var $form = $modal.find("#tiki-manager-create-instance");
        createInstance($form);
        var $applyform = $("#tiki-manager-apply-profile");
        manageRepositoryField($applyform);
    });

    var $vmform = $('#virtualmin-create-form');
    var limit_available_versions = function() {
        var $source = $vmform.find('select[name=source]');
        var $version = $vmform.find('select[name=php_version]');
        var $branch = $vmform.find('select[name=branch]');
        if (! $source.val()) {
            return;
        }
        ajaxLoadingShow($version[0]);
        $.ajax({
            url: $.service('manager', 'available_versions'),
            data: {
                source: $source.val(),
                php_version: $version.val(),
            },
            dataType: 'json',
            success: function(data) {
                ajaxLoadingHide();
                var old_val = $version.val();
                $version.empty().append("<option value=''></option>");
                if (data.php_versions) {
                    for(var i = 0, l = data.php_versions.length; i < l; i++) {
                        $version.append("<option value='"+data.php_versions[i]+"'>"+data.php_versions[i]+"</option>");
                    }
                }
                $version.val(old_val);
                old_val = $branch.val();
                $branch.empty().append("<option value=''></option>");
                if (data.available_branches) {
                    for(var i = 0, l = data.available_branches.length; i < l; i++) {
                        $branch.append("<option value='"+data.available_branches[i]+"'>"+data.available_branches[i]+"</option>");
                    }
                }
                $branch.val(old_val);
            },
            error: function() {
                ajaxLoadingHide();
            }
        });
    };
    var getProfiles = function ($activeform) {
        var $repository = $activeform.find('#repository');
        $activeform.find('#profile').replaceWith('<select required class="form-control" name="profile"></select>');
        var $profile = $activeform.find('select[name=profile]');
        ajaxLoadingShow($profile[0]);
        $.ajax({
            url: $.service('manager', 'get_profiles'),
            data: {
                repository: $repository.val(),
            },
            dataType: 'json',
            success: function(data) {
                ajaxLoadingHide();
                if (data.length > 0) {
                    $profile.empty().append('<option value="">'+tr('Pick one please')+'</option>');
                    for (var i = 0; i < data.length; i++) {
                        $profile.append("<option value='"+data[i]+"'>"+data[i]+"</option>");
                    }
                } else {
                    replaceProfileField($activeform);
                }
            },
            error: function() {
                ajaxLoadingHide();
            }
        });

    };

    var manageRepositoryField = function ($activeform) {
        $activeform.find('#repository').on("change", function(){
            var $minlength = 5;
            var $value = $(this).val();
            if ($value.length >= $minlength) {
                getProfiles($activeform);
            } else {
                replaceProfileField($activeform);
            }
        });
        $activeform.find('#button-load').on("click", function () {
            getProfiles($activeform);
        });

    };

    var manageApplyFields = function ($activeform) {
        $activeform.find(".apply_fields").addClass('d-none');
        $activeform.find('select[name=apply]').on("change", function () {
            var $choice = $(this).val();
            var $profile = $activeform.find('select[name=profile]');
            var $repository = $activeform.find('#repository');
            if ($choice == 'Yes') {
                $profile.prop('required',true);
                $repository.prop('required',true);
                $activeform.find(".apply_fields").removeClass('d-none');
                getProfiles($activeform);
            } else {
                $profile.prop('required',false);
                $repository.prop('required',false);
                $activeform.find(".apply_fields").addClass('d-none');
                ajaxLoadingHide();
            }
        });
    };

    var triggerChangeForInputs = function($activeform) {
        // Maintain state to track input values
        let user = null, host = null, port = null, password = null;
        const checkAndTrigger = function(instanceType) {
            // Trigger only if all required inputs are filled based on instanceType
            if ((instanceType === 'ssh' && user && host && port) || (instanceType === 'ftp' && user && host && port && password)) {
                $activeform.find('select[name=connection_type]').trigger("change");
            }
        };
        $activeform.find('#user, #host, #port, #pass').on("change", function () {
            // Update state based on the changed input
            user = $activeform.find('#user').val() || null;
            host = $activeform.find('#host').val() || null;
            port = $activeform.find('#port').val() || null;
            password = $activeform.find('#pass').val() || null;

            // Get the selected instanceType
            const instanceType = $activeform.find('select[name=connection_type]').val();
            checkAndTrigger(instanceType);
        });
    };

    var appendHostPHPVersions = function($activeform) {
        $activeform.find('select[name=connection_type]').on("change", function () {
            const instanceType = $(this).val();
            let user, host, port, password;
            if (instanceType === 'ssh' || instanceType === 'ftp') {
                user = $activeform.find('#user').val() || null;
                host = $activeform.find('#host').val() || null;
                port = parseInt($activeform.find('#port').val(), 10);
                password = instanceType === 'ftp' ? $activeform.find('#pass').val() || null : null;
                if (!user || !host || !port || (instanceType === 'ftp' && !password)) {
                    return;
                }
            }
            if (instanceType) {
                ajaxLoadingShow();
                $.ajax({
                    url: $.service('manager', 'GetHostPHPVersions'),
                    data: {
                        type: instanceType,
                        user: user,
                        host: host,
                        port: port,
                        password: password
                    },
                    dataType: 'json',
                    success: function(data) {
                        ajaxLoadingHide();
                        const $phpExecutable = $activeform.find('#php_executable');
                        $phpExecutable.empty();
                        const phpVersions = Object.keys(data) || [];
                        if (phpVersions.length) {
                            phpVersions.forEach(function(ver) {
                                const $option = $('<option>', {
                                    value: data[ver],
                                    text: ver
                                }).attr('data-extra', ver);
                                $phpExecutable.append($option);
                            });
                        }
                        $phpExecutable.trigger('change');
                    },
                    error: function() {
                        ajaxLoadingHide();
                    }
                });
            }
        });
    };

    var appendAvailableGitBranches = function($activeform) {
        $activeform.find('select[name=php_executable]').on("change", function () {
            const phpVersion = $(this).find(':selected').data('extra');
            if (phpVersion) {
                ajaxLoadingShow();
                $.ajax({
                    url: $.service('manager', 'GetAvailableBranches'),
                    data: {
                        php_version: phpVersion,
                    },
                    dataType: 'json',
                    success: function(data) {
                        ajaxLoadingHide();
                        const $branches = $activeform.find('#branch');
                        $branches.empty();
                        if (data.length) {
                            data.forEach(function(branch) {
                                $branches.append(
                                    $('<option>', { value: branch, text: branch })
                                );
                            });
                        }
                    },
                    error: function() {
                        ajaxLoadingHide();
                    }
                });
            }
        });
    };

    var replaceProfileField = function ($activeform) {
        var $content = $activeform.find('#profile-field');
        $content.html('<input type="text" class="form-control" name="profile" id="profile" placeholder="e.g Personal_Blog_and_Profile" required>');
    };

    var createInstance = function ($activeform) {
        manageRepositoryField($activeform);
        manageApplyFields($activeform);
    };

    if ($vmform.length > 0) {
        $vmform.find('select[name=source], select[name=php_version]').on('change', limit_available_versions);
        createInstance($vmform);
    }

    if ($form.length > 0) {
        appendHostPHPVersions($form);
        appendAvailableGitBranches($form);
        triggerChangeForInputs($form);
        createInstance($form);
    }

    var $applyform = $("#tiki-manager-apply-profile");
    if ($applyform.length > 0) {
        manageRepositoryField($applyform);
    }

    var $instance_backup_form = $("#tiki-manager-backup-instance");
    if ($instance_backup_form.length > 0) {
        $instance_backup_form.on('submit', function(){
            ajaxLoadingShow('tiki-manager-backup-instance');
        });
    }
    /**
     *  disable selected branch while selecting to clone data or code only
     * @type {*|jQuery|HTMLElement}
     */
    var $manager_clone_form = $('#tiki-manager-clone-form');
    var $clone_data = $manager_clone_form.find('input[type=checkbox][name="options[--only-data]"]');
    var $clone_code = $manager_clone_form.find('input[type=checkbox][name="options[--only-code]"]');
    var $branch = $manager_clone_form.find('select[name="options[--branch]"]');

    var $controlSelectBranch = function() {
        if (!$($clone_data).is(':checked') && !$($clone_code).is(':checked')) {
            $branch.prop('disabled', false);
            $branch.attr("required",true);
        } else {
            $branch.attr("required",false);
            $branch.prop('selectedIndex', -1);
            $branch.prop('disabled', true);
        }
    };

    if ($manager_clone_form.length > 0) {
        $clone_data.on('change', $controlSelectBranch);
        $clone_code.on('change', $controlSelectBranch);
    }
})();
