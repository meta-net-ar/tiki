const addFormValidation = (formId, tracker) => {
    let rules = {};
    tracker.fields.forEach((field) => {
        if (field.type == "b") {
            rules[`${field.html_name}_currency`] = {
                required: function (element) {
                    return $(`#${field.html_name}`).val() != "";
                },
            };
        }
        if (field.validation || field.isMandatory == "y") {
            let key = "";
            if (field.isMultilingual == "y") {
                // TODO: handle multilingual
            } else {
                key = field.html_name;
            }
            rules[key] = {};
            if (field.isMandatory == "y") {
                if (field.type == "D") {
                    rules[key]["required_in_group"] = [1, `group_${field.html_name}`, "other"];
                } else if (field.type == "A") {
                    $rules[key]["required_tracker_file"] = [1, `file_${field.html_name}`];
                } else if (field.type == "f") {
                    rules[key]["required"] = false;
                    let date_ins_num = field.options_array[0] === "dt" ? 5 : 3;
                    rules[`${field.html_name}Month`] = { required_in_group: [date_ins_num, `select[name^=${field.html_name}]`] };
                    rules[`${field.html_name}Day`] = { required_in_group: [date_ins_num, `select[name^=${field.html_name}]`] };
                    rules[`${field.html_name}Year`] = { required_in_group: [date_ins_num, `select[name^=${field.html_name}]`] };
                    if (field.options_array[0] === "dt") {
                        rules[`${field.html_name}Hour`] = { required_in_group: [date_ins_num, `select[name^=${field.html_name}]`] };
                        rules[`${field.html_name}Minute`] = { required_in_group: [date_ins_num, `select[name^=${field.html_name}]`] };
                    }
                } else {
                    if (field.isMultilingual == "y") {
                        // TODO: handle multilingual
                    } else {
                        rules[key] = { required: true };
                    }
                }
            }
            if (field.validation) {
                // TODO: remote validation in offline mode?? (probably support the basic cases of format validation or uniqueness)
            }
        }
    });
    let messages = {};
    tracker.fields.forEach((field) => {
        if (field.type == "b") {
            if (field.validationMessage) {
                messages[`${field.html_name}_currency`] = tr(field.validationMessage);
            } else {
                messages[`${field.html_name}_currency`] = tr("This field is required");
            }
        }
        if (field.validationMessage && field.isMandatory == "y") {
            messages[field.html_name] = {
                required: tr(field.validationMessage),
            };
        } else if (field.isMandatory == "y") {
            if (field.isMultilingual == "y") {
                // TODO: handle multilingual
            } else {
                messages[field.html_name] = {
                    required: tr("This field is required"),
                };
            }
        }
    });
    $(formId).validate({
        rules: rules,
        messages: messages,
        focusInvalid: false,
        invalidHandler: function (event, validator) {
            let errors = validator.numberOfInvalids();
            if (errors) {
                let $container = $(formId).parents(".modal");
                let $scroller = $container;
                let offset = 0;

                if (!$container.length) {
                    $container = $("html");
                    $scroller = $("body");
                    offset = $(".fixed-top").outerHeight() || 0;
                }
                var containerScrollTop = $scroller.scrollTop(),
                    $firstError = $(validator.errorList[0].element),
                    $scrollElement = $firstError.parents(".tracker-field-group");

                if (!$scrollElement.length) {
                    $scrollElement = $firstError;
                }

                if ($firstError.parents(".tab-content").length > 0) {
                    $tab = $firstError.parents(".tab-pane");
                    $('a[href="#' + $tab.attr("id") + '"]').tab("show");
                }

                $container.animate(
                    {
                        scrollTop: containerScrollTop + $scrollElement.offset().top - offset - $(window).height() / 2,
                    },
                    1000,
                    function () {
                        $firstError.trigger("focus");
                    }
                );
            }
        },
        onkeyup: false,
        errorClass: "invalid-feedback",
        errorPlacement: function (error, element) {
            if ($(element).parents(".input-group").length > 0) {
                error.insertAfter($(element).parents(".input-group").first());
            } else {
                error.appendTo($(element).parents().first());
            }
        },
        highlight: function (element) {
            $(element).addClass("is-invalid");

            // Highlight chosen element if exists
            $(`#${element.getAttribute("id")}_chosen`).addClass("is-invalid");
        },
        unhighlight: function (element) {
            $(element).removeClass("is-invalid");

            // Unhighlight chosen element if exists
            $(`#${element.getAttribute("id")}_chosen`).removeClass("is-invalid");
        },
        ignore: ".ignore",
    });
    $(formId).on("click.validate", ":submit", function () {
        $(formId)
            .find("[name^=other_ins_]")
            .each(function (key, item) {
                $(item).data("tiki_never_visited", "");
            });
    });
};

export { addFormValidation };
