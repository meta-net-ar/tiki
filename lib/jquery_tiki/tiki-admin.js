(function ($) {


    $(function () {
        // highlight the admin icon (anchors)
        var $anchors = $(".adminanchors li a, .admbox"),
            bgcol = $anchors.is(".admbox") ? $anchors.css("background-color") : $anchors.parent().css("background-color");

        $("input[name=lm_criteria]").on("keyup", function () {
            var criterias = this.value.toLowerCase().split( /\s+/ ), word, text;
            $anchors.each( function() {
                var $parent = $(this).is(".admbox") ? $(this) : $(this).parent();
                if (criterias && criterias[0]) {
                    text = $(this).attr("data-alt").toLowerCase();
                    for( var i = 0; criterias.length > i; ++i ) {
                        word = criterias[i];
                        if ( word.length > 0 && text.indexOf( word ) == -1 ) {
                            $parent.css("background", "");
                            return;
                        }
                    }
                    $parent.css("background", "radial-gradient(white, " + bgcol + ")");
                } else {
                    $parent.css("background", "");
                }
            });
        });
    });

    // AJAX plugin list load for admin/textarea/plugins
    var pluginSearchTimer  = null;
    $("#pluginfilter").on("change", function (event) {
        var filter = $(this).val();
        if (filter.length > 2 || !filter) {
            if (pluginSearchTimer) {
                clearTimeout(pluginSearchTimer);
                pluginSearchTimer = null;
            }
            $("#pluginlist").load($.service("plugin", "list"), {
                filter: filter
            }, function (response, status, xhr) {
                if (status === "error") {
                    $("#pluginfilter").showError(xhr);
                }
                $(this).tikiModal();
            }).tikiModal(tr("Loading..."));
        }
    }).on("keydown",function (event) {
        if (event.which === 13) {
            event.preventDefault();
            $(this).trigger("change");
        } else if (! pluginSearchTimer) {
            pluginSearchTimer = setTimeout(function () {
                $("#pluginfilter").trigger("change");
            }, 1000);
        }
    });

    // Plugin Alias management JS

    var $pluginAliasAdmin = $("#contentadmin_textarea-plugin_alias");

    if ($pluginAliasAdmin.length) {
        /**
         * General purpose param adding icons
         */
        $('.add-param', $pluginAliasAdmin).on("click", function () {
            var $fieldset = $(this).closest("fieldset"),
                // for composed args/params (fieldset) the template comes after the one for a new param,
                // so we need closestDescendent, not :first
                $template = $fieldset.closestDescendent(".param.d-none"),
                $clone = $template.clone(),
                index = $fieldset.find(".param:visible").length;

            $clone.find('input:not(.select2-search__field)').each(function () {
                $(this).attr('name', $(this).attr('name').replace('__NEW__', index));
            }).val('').find('label').each(function () {
                $(this).attr('for', $(this).attr('for').replace('__NEW__', index));
            });

            $clone.find(".d-none").addBack().removeClass("d-none");

            if (jqueryTiki.autocomplete && jqueryTiki.ui) {
                var plugin = $("#implementation").data("plugin");

                if (plugin) {
                    // get the param names
                    var params = $.map(plugin.params, function(element,index) {return index;});

                    $clone.find("input.sparam-name").autocomplete({
                        minLength: 1,
                        source: params,
                        select: function(e, ui) {
                            var $defInput = $(this).closest(".param").find("input.sparam-default"),
                                options = [],
                                param = plugin.params[ui.item.value];

                            // collect the options if any
                            $.each(param.options, function (k, v) {
                                options.push(v.value);
                            });

                            // set the default as the default
                            $defInput.val(param.default);

                            // autocomplete the defaults on the options
                            if (options) {
                                $defInput.autocomplete({
                                    minLength: 1,
                                    source: options
                                });
                            }
                        }

                    });
                }
            }

            $template.parent().append($clone);    //  .tiki_popover() doesn't work as the title has been removeed on page
                // load... FIXME?;

            return false;
        });

        $($pluginAliasAdmin).on("click", ".delete-param", function () {
            $(this).popover("hide").parents(".param").remove();
        });

        setTimeout(function () {
/*
            if (jqueryTiki.validate) {
                $pluginAliasAdmin.closest("form").validate({
                    rules: {
                        plugin_alias: "required",
                        implementation: "required"
                    }
                });
            }
*/

            $("#plugin_alias").on("change", function () {
                var $this = $(this),
                    val = $this.val().toLowerCase(),
                    $pluginName = $("#plugin_name");

                if (!$pluginName.val()) {
                    $pluginName.val(val.replace(/\s+/g, " ").replace(
                        /\w\S*/g, function (txt) {
                                return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
                            }
                        )
                    );
                }
                $this.val(val.replace(/\W+/g, ""));
            });

            $("#implementation").on("change", function () {
                var val = $("#implementation").val();
                if (val) {
                    $.getJSON(
                        $.service("plugin", "list", {
                            filter: val,
                            title: val    // to get it back later
                        }),
                        function (data) {
                            if (data && data.plugins[data.title]) {
                                var plugin = data.plugins[data.title];

                                if (plugin.prefs) {
                                    $("#plugin_deps").val(plugin.prefs.join(","));
                                }

                                $("#implementation").data("plugin", plugin);
                            }
                        }
                    );
                }
            }).trigger("change");

        }, 500);

    }

    // Encryption management JS
    var $encryptionAdmin = $("#content_admin1-encryption");
    if ($encryptionAdmin.length) {
        $('#regenerate').on('change', function() {
            if ($(this).is(':checked')) {
                $('#algo, #shares, input[name=users], select[name="users[]"]').removeAttr('disabled');
                $('#old_share_container').show();
            } else {
                $('#algo, #shares, input[name=users], select[name="users[]"]').attr('disabled', 'disabled');
                $('#old_share_container').hide();
            }
        }).trigger('change');
    }

    // Admin Backend sidebar toggle
    $(".admin-menu-collapser > a", ".admin-nav").on("click", function () {
        let $this = $(this),
            $parentNav = $this.parents("nav");
            $icon = $this.find(".icon");

        if ($parentNav.is(".narrow")) {
            $parentNav.removeClass("narrow");
            $icon.setIcon("angle-double-left");
            $("body").removeClass("sidebar_collapsed");
            deleteCookie("sidebar_collapsed");
        } else {
            $parentNav.addClass("narrow");
            $icon.setIcon("angle-double-right");
            $("body").addClass("sidebar_collapsed");
            setCookie("sidebar_collapsed", "y");
        }
        return false;
    });

    $(function () {
        if (getCookie("sidebar_collapsed")) {
            $("body").addClass("sidebar_collapsed");
        }
    });

    // API Tokens
    if ($('#content-admin1-api').length) {
        const container = $(document).closest('.js-allow-copy');
        $('.js-allow-copy span.icon').tiki('copy')(() => container.data('content'), function() {
            container.prepend($('<div/>').addClass('alert-success').html(tr('Token copied to clipboard.')));
        });
        $(document).on('click', '.js-remove-token', function(event) {
            event.preventDefault();
            let $removebtn = $(this);
            $removebtn.confirmationDialog({
                title: tr('Remove token?'),
                message: tr('Clients using it will no longer have access!'),
                success: function () {
                    $.ajax({
                        url: $removebtn.attr('href'),
                        success: function() {
                            $.ajax({
                                url: $.service('api_token', 'list'),
                                success: function(data) {
                                    $('#auth_api_tokens_childcontainer').html(data);
                                }
                            });
                        }
                    });
                }
            });
            return false;
        });
    }


    //Toogle between Advanced and basic preferences form anywhere in admin panel
    const dontShowAdvancedPrefFilterAlertBox = localStorage.getItem('dontShowAdvancedPrefFilterAlertBox');
    if (! dontShowAdvancedPrefFilterAlertBox || dontShowAdvancedPrefFilterAlertBox == 'n') {
        $(".toggle-advanced-preffilter-alertbox").removeClass("d-none");
    }
    $('#dont-show-toggle-advanced-preffilter-alertbox').on("click", function () {
        localStorage.setItem("dontShowAdvancedPrefFilterAlertBox", "y");
        $(".toggle-advanced-preffilter-alertbox").addClass('d-none');
    });

    // Toggle between Unified Admin Panel and legacy admin panel script
    const dontShowUnifiedAdminPanelAlertBox = localStorage.getItem('dontShowUnifiedAdminPanelAlertBox');
    if (! dontShowUnifiedAdminPanelAlertBox || dontShowUnifiedAdminPanelAlertBox == 'n') {
        $(".toggle-unified-admin-panel-alertbox").removeClass("d-none");
    }

    $('#toggle-unified-admin-panel-btn').on("change", function () {
        var checked = $(this).is(":checked");
        var ticket = $(".ticket").val();
        var data = null;
        if (checked) {
            data = {
                'theme_unified_admin_backend' : 'on',
                'lm_preference[]' : "theme_unified_admin_backend",
                'lm_criteria' : 'Unified Admin Backend',
                'ticket' : ticket
            };
        } else {
            data = {
                'lm_preference[]' : "theme_unified_admin_backend",
                'lm_criteria' : 'Unified Admin Backend',
                'ticket' : ticket
            };
        }
        $("#loader-toggle-uap").removeClass('d-none');
        $.ajax("tiki-admin.php", {
            type: 'POST',
            data: data,
            success: function (data) {
                $("#tikifeedback").html('<div class="alert alert-success alert-dismissible">'+tr("You have changed the default preference of your admin panel, The page will reload in a few seconds...")+'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="'+tr("Close")+'"></button></div>');
                $("#loader-toggle-uap").addClass('d-none');
                location.href="tiki-admin.php";
            },
            error: function () {
                $("#tikifeedback").html('<div class="alert alert-success alert-dismissible">'+tr("An error occurred while switching between unified admin panel and legacy admin panel.")+'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="'+tr("Close")+'"></button></div>');
                $("#loader-toggle-uap").addClass('d-none');
            }
        });
    });

    $('#dont-show-toggle-unified-admin-panel-alertbox').on("click", function () {
        localStorage.setItem("dontShowUnifiedAdminPanelAlertBox", "y");
    });

    // manage default pref filters switches
    const updateVisible = function () {
        const show = function (selector) {
            selector.show();
            selector.parents('fieldset:not(.tabcontent)').show();
            selector.closest('fieldset.tabcontent').addClass('filled');
        };
        const hide = function (selector) {
            selector.hide();
        };

        let filters = [];
        const prefs = $('#col1 .adminoptionbox.preference, .admbox').hide();
        prefs.parents('fieldset:not(.tabcontent)').hide();
        prefs.closest('fieldset.tabcontent').removeClass('filled');
        $('.preffilter').each(function () {
            var targets = $('.adminoptionbox.preference.' + $(this).val() + ',.admbox.' + $(this).val());
            if ($(this).is(':checked')) {
                filters.push($(this).val());
                show(targets);
            } else if ($(this).is('.negative:not(:checked)')) {
                hide(targets);
            }
        });

        show($('.adminoptionbox.preference.modified'));

        $('input[name="filters"]').val(filters.join(' '));
        $('.tabset .tabmark a').each(function () {
            const selector = 'fieldset.tabcontent.' + $(this).attr('href').substring(1);
            const content = $(this).closest('.tabset').find(selector);

            $(this).parent().toggle(content.is('.filled') || content.find('.preference').length === 0);
        });
    };

    updateVisible();

    $('.preffilter').on("change", updateVisible);
    $('.preffilter-toggle').on("change", function () {
        const checked = $(this).is(":checked");
        $("input.preffilter[value=advanced]").prop("checked", checked);
        updateVisible();
    });

    $('.input-pref_filters').on("change", function () {
        const pref_filters_values = $("input[name='pref_filters[]']:checked").map(function () {
            return $(this).val();
        }).get();
        $("#preffilter-loader").removeClass('d-none');
        $.ajax("tiki-admin.php", {
            type: 'POST',
            data: {"pref_filters": pref_filters_values},
            success: function (data) {
                $("#tikifeedback").html(
                    `<div class = "alert alert-success alert-dismissible">
                        ${tr("Default preference filters set.")}
                        <button type="button" class= "btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>`
                );
                $("#preffilter-loader").addClass('d-none');
            },
            error: function () {
                $("#tikifeedback").show(tr("An error occurred while modifying the default preferences."));
                $("#preffilter-loader").addClass('d-none');
            }
        });
    });

})(jQuery);
