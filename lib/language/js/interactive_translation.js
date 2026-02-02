$('.intertrans').find('*').addClass('intertrans');
$('#intertrans-modal form :reset').on("click", function() {
    $('#intertrans-modal').modal('hide');
    return false;
} );

if (localStorage.getItem("isCheckActiveTrans") === "true") {
    $("#intertrans-active").attr("checked", true);
}

var interTransDone = false;
$('#intertrans-modal form').on("submit", function( e ) {
    e.preventDefault();
    $('body, input[type="submit"]').css('cursor', 'wait');

    $.ajax({
        url: $(this).attr('action'),
        data: $(this).serialize(),
        success: function() {
            $('body').css('cursor', 'default');
            $('input[type="submit"]').css('cursor', 'pointer');
            $('#intertrans-modal').hide();
            interTransDone = true;
            document.location.href = document.location.href.replace(/#.*$/, "");
        }
    });

    return false;
 } );

var canTranslateIt = function( e ) {
    if( $('#intertrans-active:checked').length == 0 ||
        e.currentTarget.id.indexOf('intertrans-') === 0 ||
        $(e.currentTarget).parents("form.intertrans, #intertrans-form").length > 0 ) {
        return false;
    } else {
        return true;
    }
};

var interTransDeepestElement = -1;

$('#intertrans-active').on("click", function( e ) {

    var isActive = $("#intertrans-active").is(":checked");
    if (isActive) {
        localStorage.setItem("isCheckActiveTrans", "true");
    } else {
        localStorage.setItem("isCheckActiveTrans", "false");
    }

    if (interTransDone && !$(this).prop("checked")) {
        history.go(0);
    }

    if ($.lang != 'en') {
        if (!$('#intertrans-active').is(":checked")) {
            $('.to-translate').removeClass('to-translate');
            localStorage.setItem("isCheckActive", "false");
        } else {
            for (let i in data) {
                let original = data[i][0].trim();
                let translated = data[i][1].trim();
                let isTranslated = data[i][2];

                if (original != '' && !isTranslated) {
                    let needTranslate = $('.container *:contains("' + original + '")');
                    needTranslate.each(function() {
                        if ($(this).text().trim() == original) {
                            $(this).addClass('to-translate');
                        }
                    });
                }
            }
        }
    }
});

$("#disableTranslation").on("click", function () {
    localStorage.setItem("isCheckActiveTrans", "false");
});

// Attach event listeners to all elements within .container
// Use event delegation to handle dynamically added elements as well
$(document).find('.container *').on("click", function (e) {
    if (!canTranslateIt(e)) { return; }

    e.preventDefault();
    var text = $(this).text();
    var val = $(this).val();
    var alt = $(this).attr('alt');
    var title = $(this).attr('title');
    if ($(this).parent().hasClass('tikihelp')
        || $(this).parent().hasClass('titletips')
        || $(this).parent().parent().hasClass('tips')) {
    }

    findTextToTranslate(text, val, alt, title);

    return true;
}).on("mouseover", function (e) {
    if (!canTranslateIt(e)) { return; }
    var $this = $(this);

    if ($this.hasClass('dropdown-toggle')) {
        $this.trigger('click.bs.dropdown');
    }

    var myparents = $this.parents();
    if (myparents.length > interTransDeepestElement) {    // trying to only highlight one element at a time
        var shad = "black 0 0 5px";
        $this.css({ "box-shadow": shad, "-moz-box-shadow": shad, "-webkit-box-shadow": shad });
        $(myparents[interTransDeepestElement]).css({ "box-shadow": "", "-moz-box-shadow": "", "-webkit-box-shadow": "" });
        interTransDeepestElement = myparents.length;
    }
}).on("mouseout", function (e) {
    if (!canTranslateIt(e)) { return; }
    $(this).css({ "box-shadow": "", "-moz-box-shadow": "", "-webkit-box-shadow": "" });
    interTransDeepestElement = -1;
});


// Handle modal separately to avoid conflicts with modal structures
// and to avoid issues with z-index and event propagation
$(document).find(".modal-content *").on("click", function (e) {
    if (!canTranslateIt(e)) { return; }

    $(".modal-dialog").find("*").each(function () {

        if ($(this).hasClass("modal-content") ||
            $(this).hasClass("modal-body") || $(this).hasClass("modal-header") ||
            $(this).hasClass("modal-footer") || $(this).hasClass("accordion") ||
            $(this).hasClass("card-accordion")) {
            return;
        }

        //if is a form element ignore
        if ($(this).is("form") || $(this).is("input") || $(this).is("textarea") || $(this).is("select") || $(this).is("button")) {
            return;
        }

        let text = $(this).text().trim();
        let val = $(this).val();
        let alt = $(this).attr('alt');
        let title = $(this).attr('title');

        if (text.length > 0 || val || alt || title) {
            addShadowEffectToElement(this);

            // Add click event to open intertrans modal
            $(this).on("click", function (e) {
                if (!canTranslateIt(e)) { return; }
                e.preventDefault();
                e.stopPropagation();
                findTextToTranslate(text, val, alt, title);
            });
        }
    });
});

// Global function to be called from Vue component when an item is selected
window.vueselectIntertransHandler = function (selectedOption) {
    if (!$("#intertrans-active").is(":checked")) {
        return;
    }

    if (!selectedOption || !selectedOption.label) {
        return;
    }

    findTextToTranslate(selectedOption.label, null, null, null);
};

// Handle popovers separately to avoid conflicts with popover structures
$("[data-toggle='popover'], [data-bs-toggle='popover'], [data-bs-trigger='hover focus'][data-bs-content], [data-trigger='hover focus'][data-content]").on("shown.bs.popover", function (e) {
    if (!canTranslateIt(e)) { return; }

    $(".popover").find("*").each(function () {
        let text = $(this).text().trim();

        // set in a variable number of child elements
        let numChildElements = $(this).children().length;
        if ($(this).hasClass("popover-body")) {
            numChildElements = $(this).children().length;
        }

        //ignore element with class popover-body
        if ($(this).hasClass("popover-body") && numChildElements > 0) {
            return;
        }

        if (text.length > 0) {
            addShadowEffectToElement(this);

            // Add click event to open intertrans modal
            $(this).on("click", function (e) {
                e.preventDefault();
                findTextToTranslate(text, null, null, null);
                $('.popover').popover('hide');
            });
        }
    });
});

// Function to add shadow effect and hover effect to an element
addShadowEffectToElement = function (e) {
    if (!e || e.length == 0) return;

    var shad = "black 0 0 5px";
    let css = { "box-shadow": shad, "-moz-box-shadow": shad, "-webkit-box-shadow": shad };

    // Add hover effect
    $(e).hover(
        function () { $(e).css(css); },
        function () { $(e).css({ "box-shadow": "", "-moz-box-shadow": "", "-webkit-box-shadow": "" }); }
    );

    // add leave event to remove hover effect
    $(e).on("mouseleave", function () {
        $(e).css({ "box-shadow": "", "-moz-box-shadow": "", "-webkit-box-shadow": "" });
    });
};

// Function to find occurrences of text, val, alt, or title in the data array
// and populate the modal with the results
function findTextToTranslate(text, val, alt, title) {
    // data is defined on lib/smarty_tiki/function.interactivetranslation.php
    var applicable = $(data).filter(function (k) {
        var textToSearchFor = $('<span>' + this[1] + '</span>').text(); // The spans just make sure this calls jQuery( html ) instead of another jQuery constructor. text() will strip them.
        return textToSearchFor.length && ((text && text.length && text.indexOf(textToSearchFor) != -1)
            || (val && val.length && val.indexOf(textToSearchFor) != -1)
            || (alt && alt.length && alt.indexOf(textToSearchFor) != -1)
            || (title && title.length && title.indexOf(textToSearchFor) != -1));
    });

    var searchedText = text || val || alt || title;

    $('#intertrans-table table tbody').empty();

    if (applicable.length > 0) {
        $('#intertrans-empty').hide();
        $('#intertrans-close').hide();
        $('#intertrans-submit').show();
        $('#intertrans-cancel').show();
        $('#intertrans-help').show();

        $('#intertrans-table table tbody')
            .append(applicable.map(function () {
                var r = $('<tr><td class="original"></td><td><textarea name="trans[]" class="form-control"></textarea><input type="hidden" name="source[]"/></td></tr>');
                r.find('td.original').text(this[0]);
                if (this[2]) {    // new ones in italic
                    r.find('td.original').css("font-style", 'italic');
                }
                r.find(':hidden').val(this[0]);
                r.find(':text').val(this[1]);
                r.find('textarea').val(reverseArgReplace(this[1], this[3]));
                return r[0];
            }));
    } else {
        // set text in span with this id intertrans-string-to-translate
        $("#intertrans-string-to-translate").html(`"<b>${searchedText}</b>"`);
        $('#intertrans-empty').show();
        $('#intertrans-close').show();
        $('#intertrans-submit').hide();
        $('#intertrans-cancel').hide();
        $('#intertrans-help').hide();
    }

    $('#intertrans-modal').modal('show').on("keydown", function (e) {
    }).find("input").first().trigger("focus");
}

/**
 * Replace argument values in the content with their placeholders.
 *
 * @param {string} content Text with argument values
 * @param {string[]} args Array of argument values
 * @returns {string} Text with arguments replaced by their placeholders
 */
function reverseArgReplace(content, args) {
    if (!args || args.length === 0 || !content) return content;

    const matches = [];

    args.forEach((val, i) => {
        if (!val) return;
        let offset = 0;
        while (true) {
            const pos = content.indexOf(val, offset);
            if (pos === -1) break;
            matches.push({
                start: pos,
                length: val.length,
                arg: i
            });
            offset = pos + 1;
        }
    });

    if (matches.length === 0) return content;

    matches.sort((a, b) => b.length - a.length || a.start - b.start);

    const selected = [];
    matches.forEach(m => {
        const s = m.start;
        const e = s + m.length;
        let overlap = selected.some(sel => !(e <= sel.start || s >= sel.start + sel.length));
        if (!overlap) selected.push(m);
    });

    if (selected.length === 0) return content;

    selected.sort((a, b) => a.start - b.start);
    let out = '';
    let cursor = 0;
    selected.forEach(m => {
        const start = m.start;
        const len = m.length;
        const argIndex = m.arg;

        if (cursor < start) {
            out += content.slice(cursor, start);
        }

        out += `%${argIndex}`;
        cursor = start + len;
    });

    if (cursor < content.length) {
        out += content.slice(cursor);
    }

    return out;
}