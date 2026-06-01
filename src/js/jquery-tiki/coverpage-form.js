const textFields = [
    { key: "heading", label: "Heading", placeholder: "{PAGETITLE}" },
    { key: "subheading", label: "Subheading", placeholder: "" },
    { key: "alignment", label: "Text Alignment", type: "select", options: ["", "center", "left", "right"] },
    { key: "bgcolor", label: "Background Color", type: "color", placeholder: "#ffffff" },
    { key: "textcolor", label: "Text Color", type: "color", placeholder: "#000000" },
    { key: "border", label: "Border Width", placeholder: "1", size: 4 },
    { key: "bordercolor", label: "Border Color", type: "color", placeholder: "#cccccc" },
];

const pageFields = [
    { key: "bgcolor", label: "Background Color", type: "color", placeholder: "#ffffff" },
    { key: "borderwidth", label: "Border Width", placeholder: "1", size: 4 },
    { key: "bordercolor", label: "Border Color", type: "color", placeholder: "#000000" },
];

function buildColorInput(field, val) {
    const $wrap = $('<div class="input-group input-group-sm">');
    const $text = $('<input type="text" class="form-control form-control-sm coverpage-field">')
        .attr("data-key", field.key)
        .attr("placeholder", field.placeholder || "")
        .val(val);
    const $picker = $('<input type="color" class="form-control form-control-color form-control-sm" style="max-width:38px;padding:2px;">').val(
        val || field.placeholder || "#000000"
    );
    $picker.on("input", function () {
        $text.val($picker.val()).trigger("change");
    });
    $text.on("change", function () {
        const v = $text.val();
        if (/^#[0-9a-f]{3,8}$/i.test(v)) {
            $picker.val(v);
        }
    });
    $wrap.append($text).append($picker);
    return $wrap;
}

function buildField(field, val) {
    if (field.type === "select") {
        const $sel = $('<select class="form-select form-select-sm coverpage-field">').attr("data-key", field.key);
        $.each(field.options, function (i, opt) {
            $sel.append(
                $("<option>")
                    .val(opt)
                    .text(opt || "(default)")
            );
        });
        $sel.val(val);
        return $sel;
    }
    if (field.type === "color") {
        return buildColorInput(field, val);
    }
    return $('<input type="text" class="form-control form-control-sm coverpage-field">')
        .attr("data-key", field.key)
        .attr("placeholder", field.placeholder || "")
        .attr("size", field.size || "")
        .val(val);
}

function splitPipe(val, count) {
    const parts = (val || "").split("|");
    while (parts.length < count) {
        parts.push("");
    }
    return parts;
}

function overlay($input, fields) {
    $input.css("display", "none");

    const parts = splitPipe($input.val(), fields.length);
    const $container = $('<div class="coverpage-overlay mt-2 mb-2">');

    $.each(fields, function (i, field) {
        const $group = $('<div class="row mb-1 align-items-center">');
        const $label = $('<label class="col-sm-4 col-form-label col-form-label-sm">').text(field.label);
        const $col = $('<div class="col-sm-8">');
        $col.append(buildField(field, parts[i]));
        $group.append($label).append($col);
        $container.append($group);
    });

    $container.on("change input", ".coverpage-field", function () {
        const vals = [];
        $container.find(".coverpage-field").each(function () {
            vals.push($(this).val());
        });
        while (vals.length > 0 && vals[vals.length - 1] === "") {
            vals.pop();
        }
        $input.val(vals.join("|"));
    });

    $input.after($container);
    return $container;
}

function enhanceTextSettings($input) {
    return overlay($input, textFields);
}

function enhancePageSettings($input) {
    return overlay($input, pageFields);
}

// Auto-enhance admin preference page
$(function () {
    const $text = $('input[name="print_pdf_mpdf_coverpage_text_settings"]');
    if ($text.length) {
        enhanceTextSettings($text);
    }
    const $page = $('input[name="print_pdf_mpdf_coverpage_settings"]');
    if ($page.length) {
        enhancePageSettings($page);
    }
});

// Auto-enhance PDF plugin popup
$(document).on("plugin_pdf_ready", function (e) {
    const $modal = e.modal;
    if (!$modal) return;
    const $text = $modal.find('input[name="params[coverpage_text_settings]"]');
    if ($text.length && !$text.data("coverpage-enhanced")) {
        $text.data("coverpage-enhanced", true);
        enhanceTextSettings($text);
    }
    const $page = $modal.find('input[name="params[coverpage_settings]"]');
    if ($page.length && !$page.data("coverpage-enhanced")) {
        $page.data("coverpage-enhanced", true);
        enhancePageSettings($page);
    }
});
