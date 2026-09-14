(function () {
    var $wizard = $("#report-wizard");
    if (!$wizard.length) {
        return;
    }

    $wizard.closest(".modal-body").css({ overflow: "visible", "min-height": "350px" });
    $wizard.closest(".modal-content").css("overflow", "visible");
    $wizard.closest(".modal-dialog").removeClass("modal-dialog-scrollable").addClass("modal-lg");
    $wizard.closest(".modal-content").find("> .modal-footer").hide();

    $wizard
        .find(".report-type-card")
        .css("cursor", "pointer")
        .hover(
            function () {
                $(this).addClass("shadow-sm");
            },
            function () {
                $(this).removeClass("shadow-sm");
            }
        );

    var currentStep = 1;
    var totalSteps = 4;
    var selectedTracker = null;
    var trackerFields = [];
    var selectedType = null;

    var $next = $("#report-wizard-next");
    var $back = $("#report-wizard-back");
    var $insert = $("#report-wizard-insert");
    var $steps = $wizard.find(".wizard-step");
    var $navLinks = $wizard.find(".nav-link");

    function showStep(step) {
        currentStep = step;
        $steps.addClass("d-none");
        $wizard.find('.wizard-step[data-step="' + step + '"]').removeClass("d-none");

        $navLinks.removeClass("active").addClass("disabled");
        $navLinks
            .filter('[data-step="' + step + '"]')
            .addClass("active")
            .removeClass("disabled");
        for (var i = 1; i < step; i++) {
            $navLinks.filter('[data-step="' + i + '"]').removeClass("disabled");
        }

        $back.prop("disabled", step === 1);

        if (step === totalSteps) {
            $next.addClass("d-none");
            $insert.removeClass("d-none");
            updatePreview();
        } else {
            $next.removeClass("d-none");
            $insert.addClass("d-none");
            validateStep();
        }
    }

    function validateStep() {
        var valid = false;
        switch (currentStep) {
            case 1:
                valid = !!$("#report-tracker-select").val();
                break;
            case 2:
                valid = !!selectedType;
                break;
            case 3:
                valid = validateFieldStep();
                break;
            case 4:
                valid = true;
                break;
        }
        $next.prop("disabled", !valid);
    }

    function validateFieldStep() {
        switch (selectedType) {
            case "simple_table":
                return $wizard.find("#report-field-list input:checked").length > 0;
            case "aggregation_table":
                return !!$("#report-group-field").val();
            case "chart":
                return !!$("#report-chart-group-field").val();
            case "full_report":
                return $wizard.find("#report-full-field-list input:checked").length > 0 && !!$("#report-full-group-field").val();
        }
        return false;
    }

    function loadFields(trackerId) {
        $.getJSON($.service("tracker", "list_fields"), { trackerId: trackerId }, function (data) {
            trackerFields = data.fields || [];
            populateFieldSelectors();
        });
    }

    function populateFieldSelectors() {
        var checkboxHtml = "";
        var optionsHtml = '<option value="">' + tr("-- Select field --") + "</option>";
        $.each(trackerFields, function (i, field) {
            checkboxHtml +=
                '<div class="col-md-6"><div class="form-check">' +
                '<input class="form-check-input report-field-check" type="checkbox" value="' +
                field.permName +
                '" id="field-' +
                field.fieldId +
                '">' +
                '<label class="form-check-label" for="field-' +
                field.fieldId +
                '">' +
                $("<span>").text(field.name).html() +
                ' <small class="text-muted">(' +
                field.permName +
                ")</small></label></div></div>";
            optionsHtml += '<option value="' + field.permName + '">' + $("<span>").text(field.name).html() + " (" + field.permName + ")</option>";
        });

        $("#report-field-list, #report-full-field-list").html(checkboxHtml);
        $(
            "#report-group-field, #report-value-field, #report-chart-group-field, #report-chart-value-field, #report-full-group-field, #report-full-value-field"
        ).html(optionsHtml);
    }

    function showFieldsForType() {
        $("#report-fields-simple, #report-fields-aggregation, #report-fields-chart, #report-fields-full").addClass("d-none");
        switch (selectedType) {
            case "simple_table":
                $("#report-fields-simple").removeClass("d-none");
                break;
            case "aggregation_table":
                $("#report-fields-aggregation").removeClass("d-none");
                break;
            case "chart":
                $("#report-fields-chart").removeClass("d-none");
                break;
            case "full_report":
                $("#report-fields-full").removeClass("d-none");
                break;
        }
    }

    function getWizardData() {
        var data = {
            trackerId: selectedTracker,
            reportType: selectedType,
            title: $("#report-title").val() || tr("Report"),
            orientation: $("#report-orientation").val(),
            coverPage: $("#report-cover-page").is(":checked") ? 1 : 0,
        };

        switch (selectedType) {
            case "simple_table":
                data.fields = [];
                $("#report-field-list input:checked").each(function () {
                    data.fields.push($(this).val());
                });
                break;
            case "aggregation_table":
                data.groupField = $("#report-group-field").val();
                data.valueField = $("#report-value-field").val();
                data.metricOp = $("#report-metric-op").val();
                break;
            case "chart":
                data.groupField = $("#report-chart-group-field").val();
                data.valueField = $("#report-chart-value-field").val();
                data.metricOp = $("#report-chart-metric-op").val();
                data.chartType = $("#report-chart-type").val();
                break;
            case "full_report":
                data.fields = [];
                $("#report-full-field-list input:checked").each(function () {
                    data.fields.push($(this).val());
                });
                data.groupField = $("#report-full-group-field").val();
                data.valueField = $("#report-full-value-field").val();
                data.metricOp = $("#report-full-metric-op").val();
                data.chartType = $("#report-full-chart-type").val();
                break;
        }
        return data;
    }

    function updatePreview() {
        var data = getWizardData();
        $.post(
            $.service("report", "generate"),
            data,
            function (response) {
                if (response.syntax) {
                    $("#report-syntax-preview").text(response.syntax);
                }
            },
            "json"
        );
    }

    function insertSyntax(syntax) {
        var $textarea = null;

        if (typeof window.editorId !== "undefined") {
            $textarea = $("#" + window.editorId);
        }
        if (!$textarea || !$textarea.length) {
            $textarea = $('textarea[name="edit"], #editwiki, textarea.wikiedit');
        }
        if ($textarea && $textarea.length) {
            var current = $textarea.val();
            var cursorPos = $textarea[0].selectionStart || current.length;
            var before = current.substring(0, cursorPos);
            var after = current.substring(cursorPos);
            $textarea.val(before + syntax + after);
            $textarea.trigger("change");
        }
    }

    // Event handlers
    $("#report-tracker-select").on("change", function () {
        selectedTracker = $(this).val();
        if (selectedTracker) {
            loadFields(selectedTracker);
        }
        validateStep();
    });

    $wizard.on("click", ".nav-link:not(.disabled)", function (e) {
        e.preventDefault();
        var step = parseInt($(this).data("step"), 10);
        if (step && step < currentStep) {
            showStep(step);
        }
    });

    $wizard.on("click", ".report-type-card", function () {
        $(".report-type-card").removeClass("border-primary");
        $(this).addClass("border-primary");
        selectedType = $(this).data("type");
        validateStep();
    });

    $wizard.on(
        "change",
        ".report-field-check, #report-group-field, #report-value-field, #report-chart-group-field, #report-chart-value-field, #report-full-group-field, #report-full-value-field",
        function () {
            validateStep();
        }
    );

    $("#report-metric-op").on("change", function () {
        if ($(this).val() === "count") {
            $("#report-value-field-group").addClass("d-none");
        } else {
            $("#report-value-field-group").removeClass("d-none");
        }
    });
    $("#report-chart-metric-op").on("change", function () {
        if ($(this).val() === "count") {
            $("#report-chart-value-field-group").addClass("d-none");
        } else {
            $("#report-chart-value-field-group").removeClass("d-none");
        }
    });
    $("#report-full-metric-op").on("change", function () {
        if ($(this).val() === "count") {
            $("#report-full-value-field-group").addClass("d-none");
        } else {
            $("#report-full-value-field-group").removeClass("d-none");
        }
    });

    $("#report-title, #report-orientation, #report-cover-page").on("change keyup", function () {
        updatePreview();
    });

    $next.on("click", function () {
        if (currentStep < totalSteps) {
            if (currentStep === 2) {
                showFieldsForType();
            }
            showStep(currentStep + 1);
        }
    });

    $back.on("click", function () {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    $insert.on("click", function () {
        var data = getWizardData();
        $.post(
            $.service("report", "generate"),
            data,
            function (response) {
                if (response.syntax) {
                    insertSyntax(response.syntax);
                    $wizard.closest(".modal").find('[data-bs-dismiss="modal"]').trigger("click");
                }
            },
            "json"
        );
    });

    showStep(1);
})();
