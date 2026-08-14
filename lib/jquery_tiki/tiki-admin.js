(function ($) {
    $(function () {
        // highlight the admin icon (anchors)
        var $anchors = $(".adminanchors li a, .admbox"),
            bgcol = $anchors.is(".admbox") ? $anchors.css("background-color") : $anchors.parent().css("background-color");

        $("input[name=lm_criteria]").on("keyup", function () {
            var criterias = this.value.toLowerCase().split(/\s+/),
                word,
                text;
            $anchors.each(function () {
                var $parent = $(this).is(".admbox") ? $(this) : $(this).parent();
                if (criterias && criterias[0]) {
                    text = $(this).attr("data-alt").toLowerCase();
                    for (var i = 0; criterias.length > i; ++i) {
                        word = criterias[i];
                        if (word.length > 0 && text.indexOf(word) == -1) {
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
    var pluginSearchTimer = null;
    $("#pluginfilter")
        .on("change", function (event) {
            var filter = $(this).val();
            if (filter.length > 2 || !filter) {
                if (pluginSearchTimer) {
                    clearTimeout(pluginSearchTimer);
                    pluginSearchTimer = null;
                }
                $("#pluginlist")
                    .load(
                        $.service("plugin", "list"),
                        {
                            filter: filter,
                        },
                        function (response, status, xhr) {
                            if (status === "error") {
                                $("#pluginfilter").showError(xhr);
                            }
                            $(this).tikiModal();
                        }
                    )
                    .tikiModal(tr("Loading..."));
            }
        })
        .on("keydown", function (event) {
            if (event.which === 13) {
                event.preventDefault();
                $(this).trigger("change");
            } else if (!pluginSearchTimer) {
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
        $(".add-param", $pluginAliasAdmin).on("click", function () {
            var $fieldset = $(this).closest("fieldset");
            // for composed args/params (fieldset) the template comes after the one for a new param,
            // so we need closestDescendent, not :first
            var $template = $fieldset.closestDescendent(".param.d-none");
            // If no proper template found, use the first visible param as fallback
            if ($template.length === 0) {
                var $fallbackParam = $fieldset.find(".param").first();
                $template = $fallbackParam;
            }
            var $clone = $template.clone(true),
                index = $fieldset.find(".param:visible").length;
            $clone.find("input").each(function () {
                const $input = $(this);
                const name = $input.attr("name");
                const id = $input.attr("id");
                // Update id if present
                if (id && id.includes("__NEW__")) {
                    const newId = id.replace("__NEW__", index);
                    $input.attr("id", newId);
                } else if (id) {
                    // For fallback cloning, update the index in existing ids
                    const newId = id.replace(/\[(\d+)\]/g, `[${index}]`);
                    $input.attr("id", newId);
                }
                // Update name if present
                if (name && name.includes("__NEW__")) {
                    const newName = name.replace("__NEW__", index);
                    $input.attr("name", newName);
                } else if (name) {
                    // For fallback cloning, update the index in existing names
                    const newName = name.replace(/\[(\d+)\]/g, `[${index}]`);
                    $input.attr("name", newName);
                }
                $input.val("");
                if ($input.data("required")) {
                    $input.attr("required", true);
                }
            });
            $clone.find(".d-none").addBack().removeClass("d-none");
            $template.parent().append($clone);
            const plugin = $("#implementation").data("plugin");
            if (plugin) {
                const params = Object.keys
                    (plugin.params || {});
                if (window.elementPlus?.autocomplete) {
                    const inputElement = $clone.find("input.sparam-default");
                    autocomplete(inputElement[0], null, {
                        source: params,
                    });
                }
            }
            // Attach validation to the new field added
            $clone.find(".sparam-name").each(function () {
                attachValidation($(this));
            });

            return false;
        });
        // Custom validation: each sparam must have documentation
        $.validator.addMethod("hasAssociatedDocumentation", function (value, element) {
            const paramName = value.trim(); // use the field value directly
            if (!paramName) return true;
            // Look for any doc input whose value matches the param name
            const documentationField = $(`#pluginalias_doc input`).filter(function () {
                return $(this).val().trim() === paramName;
            });
            return documentationField.length > 0;
        }, "* This argument must have an associated documentation.");

        var $pluginAliasForm = $pluginAliasAdmin.closest('form.admin');
        // Adding validation with jquery validate
        $pluginAliasForm.validate({
            errorClass: "invalid-feedback",
        });

        // Helper to attach validation to a field
        function attachValidation($field) {
            $field.rules("add", {
                hasAssociatedDocumentation: true
            });
        }

        // On page load, attach to existing sparam-name fields
        $pluginAliasForm.find(".sparam-name").each(function () {
            attachValidation($(this));
        });

        // Handle submit properly
        $pluginAliasForm.on("submit", function (e) {
            if (!$pluginAliasForm.valid()) {
                e.preventDefault(); // block submit if invalid
                // add a scroll to the first error
                const $firstError = $pluginAliasForm.find(".invalid-feedback").first();
                if ($firstError.length) {
                    $("html, body").animate(
                        {
                            scrollTop: $firstError.offset().top - 100,
                        },
                        500
                    );
                }
            }
        });

        $($pluginAliasAdmin).on("click", ".delete-param", function (e) {
            e.preventDefault();
            $(this).popover("hide").closest(".param").remove();
        });

        setTimeout(function () {
            $("#plugin_alias").on("change", function () {
                const $this = $(this),
                    $pluginName = $("#plugin_name");

                let val = $this.val().toLowerCase();

                // sanitize val
                const sysname = val.replace(/[^a-z\d]/g, "");
                $this.val(sysname);

                if (!$pluginName.val()) {
                    $pluginName.val(
                        val.replace(/[\s_]+/g, " ").replace(/\w\S*/g, function (txt) {
                            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
                        })
                    );
                }
            });

            $("#implementation")
                .on("change", function () {
                    var val = $("#implementation").val();
                    if (val) {
                        $.getJSON(
                            $.service("plugin", "list", {
                                filter: val,
                                title: val, // to get it back later
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
                })
                .trigger("change");
        }, 500);

        $pluginAliasAdmin.on("click", ".delete-alias", function () {
            const aliasName = $(this).data("alias-name");
            $(this).confirmationDialog({
                title: tr("Delete alias?"),
                message: tr(`Are you sure you want to delete the plugin alias "${aliasName}"?`),
                success: () => {
                    const form = $(this).closest("form");
                    const input = $("<input>").attr("type", "hidden").attr("name", "alias_delete").val(aliasName);
                    form.append(input).trigger("submit");
                },
            });
            return false;
        });
    }

    // Encryption management JS
    var $encryptionAdmin = $("#content_admin1-encryption");
    if ($encryptionAdmin.length) {
        $("#regenerate")
            .on("change", function () {
                if ($(this).is(":checked")) {
                    $('#algo, #shares, input[name=users], select[name="users[]"]').removeAttr("disabled");
                    $("#old_share_container").show();
                } else {
                    $('#algo, #shares, input[name=users], select[name="users[]"]').attr("disabled", "disabled");
                    $("#old_share_container").hide();
                }
            })
            .trigger("change");
    }

    // Admin Backend sidebar toggle
    $(function () {
        var $body = $("body"),
            $targets = $(".admin-nav .offcanvas, .admin-nav .navbar-wrapper"),
            storageKey = "tiki_admin_sidebar_collapsed";

        function setCollapsed(state) {
            $body.toggleClass("sidebar_collapsed", state);
            $targets.toggleClass("narrow", state);

            // Storage
            try {
                state ? localStorage.setItem(storageKey, "y") : localStorage.removeItem(storageKey);
            } catch (e) { }

            if (state && typeof setCookie === "function") {
                setCookie("sidebar_collapsed", "y", "", "session", window.tikiCookieConstants ? window.tikiCookieConstants.BUILTIN_COOKIE_CATEGORY_FUNCTIONAL : undefined);
            } else if (!state && typeof deleteCookie === "function") {
                deleteCookie("sidebar_collapsed");
            }
        }

        // Toggle collapse on button click
        $(document).on("click", ".admin-menu-collapse-btn", function (e) {
            e.preventDefault();
            setCollapsed(!$body.hasClass("sidebar_collapsed"));
        });

        // Handle search icon click in narrow mode (expand instead of submit)
        $(document).on("click", ".search-preferences button[type='submit']", function (e) {
            if ($body.hasClass("sidebar_collapsed")) {
                e.preventDefault();
                setCollapsed(false);
                $(".search-preferences input[name='lm_criteria']").focus();
            }
        });

        // Handle preference filters icon click in narrow mode (expand sidebar)
        $(document).on("click", "#flush-heading-preference-filters > button", function (e) {
            if ($body.hasClass("sidebar_collapsed")) {
                e.preventDefault();
                setCollapsed(false);
            }
        });

        // Ensure icon is correct on load immediately
        $(function () {
            var collapsed = $body.hasClass("sidebar_collapsed");
            if (!collapsed && (localStorage.getItem(storageKey) === "y" || getCookie("sidebar_collapsed") === "y")) {
                collapsed = true;
            }
            setCollapsed(collapsed);
        });

        // Initialize (check storage)
        try {
            if (localStorage.getItem(storageKey) === "y" || getCookie("sidebar_collapsed") === "y") {
                setCollapsed(true);
            }
        } catch (e) { }

    });

    // API Tokens
    if ($("#content-admin1-api").length) {
        $(".js-allow-copy").each(function (i, el) {
            const $container = $(el);
            $container.find("span.icon").tiki("copy")(
                () => $container.data("content"),
                function () {
                    $container.prepend($("<div/>").addClass("alert alert-success").html(tr("Token copied to clipboard.")));
                }
            );
        });
        $(document).on("click", ".js-remove-token", function (event) {
            event.preventDefault();
            let $removebtn = $(this);
            $removebtn.confirmationDialog({
                title: tr("Remove token?"),
                message: tr("Clients using it will no longer have access!"),
                success: function () {
                    $.ajax({
                        url: $removebtn.attr("href"),
                        success: function () {
                            $.ajax({
                                url: $.service("api_token", "list"),
                                success: function (data) {
                                    $("#auth_api_tokens_childcontainer").html(data);
                                },
                            });
                        },
                    });
                },
            });
            return false;
        });
    }

    //Toogle between Advanced and basic preferences form anywhere in admin panel
    const dontShowAdvancedPrefFilterAlertBox = localStorage.getItem("dontShowAdvancedPrefFilterAlertBox");
    if (!dontShowAdvancedPrefFilterAlertBox || dontShowAdvancedPrefFilterAlertBox == "n") {
        $(".toggle-advanced-preffilter-alertbox").removeClass("d-none");
    }
    $("#dont-show-toggle-advanced-preffilter-alertbox").on("click", function () {
        localStorage.setItem("dontShowAdvancedPrefFilterAlertBox", "y");
        $(".toggle-advanced-preffilter-alertbox").addClass("d-none");
    });

    // Toggle between Unified Admin Panel and legacy admin panel script
    const dontShowUnifiedAdminPanelAlertBox = localStorage.getItem("dontShowUnifiedAdminPanelAlertBox");
    if (!dontShowUnifiedAdminPanelAlertBox || dontShowUnifiedAdminPanelAlertBox == "n") {
        $(".toggle-unified-admin-panel-alertbox").removeClass("d-none");
    }

    $("#toggle-unified-admin-panel-btn").on("change", function () {
        var checked = $(this).is(":checked");
        var ticket = $(".ticket").val();
        var data = null;
        if (checked) {
            data = {
                theme_unified_admin_backend: "on",
                "lm_preference[]": "theme_unified_admin_backend",
                lm_criteria: "Unified Admin Backend",
                ticket: ticket,
            };
        } else {
            data = {
                "lm_preference[]": "theme_unified_admin_backend",
                lm_criteria: "Unified Admin Backend",
                ticket: ticket,
            };
        }
        $("#loader-toggle-uap").removeClass("d-none");
        $.ajax("tiki-admin.php", {
            type: "POST",
            data: data,
            success: function (data) {
                $("#tikifeedback").html(
                    '<div class="alert alert-success alert-dismissible">' +
                    tr("You have changed the default preference of your admin panel, The page will reload in a few seconds...") +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="' +
                    tr("Close") +
                    '"></button></div>'
                );
                $("#loader-toggle-uap").addClass("d-none");
                location.href = "tiki-admin.php";
            },
            error: function () {
                $("#tikifeedback").html(
                    '<div class="alert alert-success alert-dismissible">' +
                    tr("An error occurred while switching between unified admin panel and legacy admin panel.") +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="' +
                    tr("Close") +
                    '"></button></div>'
                );
                $("#loader-toggle-uap").addClass("d-none");
            },
        });
    });

    $("#dont-show-toggle-unified-admin-panel-alertbox").on("click", function () {
        localStorage.setItem("dontShowUnifiedAdminPanelAlertBox", "y");
    });

    // manage default pref filters switches
    const updateVisible = function () {
        const show = function (selector) {
            selector.show();
            selector.parents("fieldset:not(.tabcontent)").show();
            selector.closest("fieldset.tabcontent").addClass("filled");
        };
        const hide = function (selector) {
            selector.hide();
        };

        let filters = [];
        const prefs = $(".tiki-admin #col1 .adminoptionbox.preference, .admbox").hide();
        prefs.parents("fieldset:not(.tabcontent)").hide();
        prefs.closest("fieldset.tabcontent").removeClass("filled");
        $(".preffilter").each(function () {
            if ($(this).is(":checked")) {
                filters.push($(this).val());
                show($(".adminoptionbox.preference." + $(this).val() + ",.admbox." + $(this).val()));
            }
        });

        show($(".adminoptionbox.preference.modified"));

        $(".preffilter").each(function () {
            if ($(this).is(".negative:not(:checked)")) {
                hide($(".adminoptionbox.preference." + $(this).val() + ":not(.always),.admbox." + $(this).val() + ":not(.always)"));
            }
        });

        // Hide preference fieldsets that became empty after negative filters were applied
        $(".tiki-admin fieldset:not(.tabcontent):visible")
            .has(".adminoptionbox.preference, .admbox")
            .each(function () {
                if ($(this).find(".adminoptionbox.preference:visible, .admbox:visible").length === 0) {
                    $(this).hide();
                }
            });

        // Hide tag badges (advanced/experimental) when their filter is actively selected
        $(".preffilter").each(function () {
            const tag = $(this).val();
            if (tag !== "advanced" && tag !== "experimental") {
                return;
            }
            const badges = $(".pref-tag-" + tag);
            if ($(this).is(":checked")) {
                badges.hide();
            } else {
                badges.show();
            }
        });

        $('input[name="filters"]').val(filters.join(" "));
        $(".tabset .tabmark a").each(function () {
            const selector = "fieldset.tabcontent." + $(this).attr("href").substring(1);
            const content = $(this).closest(".tabset").find(selector);

            $(this)
                .parent()
                .toggle(content.is(".filled") || content.find(".preference").length === 0);
        });
    };

    updateVisible();

    $(".preffilter").on("change", updateVisible);
    $(".preffilter-toggle").on("change", function () {
        const checked = $(this).is(":checked");
        $("input.preffilter[value=advanced]").prop("checked", checked);
        updateVisible();
    });

    $('.input-pref_filters').on("change", function () {
        const pref_filters_values = $("input[name='pref_filters[]']:checked").map(function () {
            return $(this).val();
        }).get();

        // Synchronize page-level toggle with navbar checkboxes
        const advancedChecked = pref_filters_values.includes('advanced');
        $('.preffilter-toggle').prop("checked", advancedChecked);

        $("#preffilter-loader").removeClass('d-none');
        $.ajax("tiki-admin.php", {
            type: "POST",
            data: { pref_filters: pref_filters_values },
            success: function (data) {
                $("#tikifeedback").html(
                    `<div class = "alert alert-success alert-dismissible">
                        ${tr("Default preference filters set.")}
                        <button type="button" class= "btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>`
                );
                $("#preffilter-loader").addClass("d-none");
            },
            error: function () {
                $("#tikifeedback").show(tr("An error occurred while modifying the default preferences."));
                $("#preffilter-loader").addClass("d-none");
            },
        });
    });
})(jQuery);
