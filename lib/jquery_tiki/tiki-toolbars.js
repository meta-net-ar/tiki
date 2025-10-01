/*

 ******************************
 * Functions for dialog tools *
 ******************************/

/**
 * Selects the markup for a wiki plugin
 * Also used by pluginedit.js
 *
 * @param area_id        string    id of textarea
 * @param elementStart    int        start of selectoion
 * @param elementEnd    int        end of selection
 */
function dialogSelectElement(area_id, elementStart, elementEnd) {
    if ($(`#${area_id}`).data("summernote")) {
        return;
    }    // TODO for the wysiwyg editor

    var $textarea = $('#' + area_id);
    var textareaEditor = syntaxHighlighter.get($textarea);
    var val = ( textareaEditor ? textareaEditor.getValue() : $textarea.val() ) || $textarea.html();
    var pairs = [], pos = 0, s = 0, e = 0;

    while (s > -1 && e > -1) {    // positions of start/end markers
        s = val.indexOf(elementStart, e);
        if (s > -1) {
            e = val.indexOf(elementEnd, s + elementStart.length);
            if (e > -1) {
                e += elementEnd.length;
                pairs[pairs.length] = [s, e];
            }
        }
    }

    if(textareaEditor) textareaEditor.focus();
    else $textarea[0].focus(); // [0] will return the DOM element instead of the jQuery object

    var selection = ( textareaEditor ? syntaxHighlighter.selection(textareaEditor, true) : $textarea.selection() );

    s = selection.start;
    e = selection.end;
    var st = $textarea.attr('scrollTop');

    for (var i = 0; i < pairs.length; i++) {
        if (s >= pairs[i][0] && e <= pairs[i][1]) {
            setSelectionRange($textarea[0], pairs[i][0], pairs[i][1]);
            break;
        }
    }

}

window.pickerData = [];
var pickerDiv = {};

function displayPicker( closeTo, list, area_id, isSheet, styleType ) {
    $('div.toolbars-picker').remove();    // simple toggle
    var $closeTo = $(closeTo);

    if ($closeTo.hasClass('toolbars-picker-open')) {
        $('.toolbars-picker-open').removeClass('toolbars-picker-open');
        return false;
    }

    $closeTo.addClass('toolbars-picker-open');
    var textarea = $('#' +  area_id);

    var coord = $closeTo.offset();
    coord.bottom = coord.top + $closeTo.height();

    pickerDiv = $('<div class="toolbars-picker ' + list + '" />')
        .css('left', coord.left + 'px')
        .css('top', (coord.bottom + 8) + 'px')
        .appendTo('body');

    var prepareLink = function(ins, disp ) {
        disp = $(disp);

        var link = $( '<a href="#" />' ).append(disp);

        if (disp.attr('reset') && isSheet) {
            var bgColor = $('div.tiki_sheet').first().css(styleType);
            var color = $('div.tiki_sheet').first().css(styleType == 'color' ? 'background-color' : 'color');
            disp
                .css('background-color', bgColor)
                .css('color', color);

            link
                .addClass('toolbars-picker-reset');
        }

        link.on("click", function() {
            insertAt(area_id, ins);

            var textarea = $('#' + area_id);
            // quick fix for Firefox 3.5 losing selection on changes to popup
            if (typeof textarea.selectionStart != 'undefined') {
                var tempSelectionStart = textarea.selectionStart;
                var tempSelectionEnd = textarea.selectionEnd;
            }

            $closeTo.trigger("click");

            // quick fix for Firefox 3.5 losing selection on changes to popup
            if (typeof textarea.selectionStart != 'undefined' && textarea.selectionStart != tempSelectionStart) {
                textarea.selectionStart = tempSelectionStart;
            }
            if (typeof textarea.selectionEnd != 'undefined' && textarea.selectionEnd != tempSelectionEnd) {
                textarea.selectionEnd = tempSelectionEnd;
            }

            return false;
        });

        return link;
    };
    var chr, $a;
    for( var i in window.pickerData[list] ) {
        chr = window.pickerData[list][i];
        if (list === "specialchar") {
            chr = $("<span>" + chr + "</span>");
        }
        $a = prepareLink( i, chr );
        if ($a.length) {
            pickerDiv.append($a);
        }
    }

    return false;
}

// Internal Link

function dialogInternalLinkOpen( area_id, clickedElement ) {
    var initial = $("#" + area_id).data('initial'), options;
    if (initial) {
        options = { initial: initial };
    } else {
        options = {};
    }
    if (window.elementPlus?.autocomplete) {
        autocomplete($("#tbWLinkPage")[0], 'pagename');
    }
    dialogSelectElement( area_id, '((', '))', clickedElement ) ;
    var s = getTASelection($('#' + area_id)[0]);

    let tuiEditor;
    if (typeof window.tuiEditors !== 'undefined' && typeof window.tuiEditors[area_id] !== 'undefined') {
        tuiEditor = window.tuiEditors[area_id];

        if (clickedElement) {
            s = "(" + clickedElement.dataset.semantic + "(" + clickedElement.dataset.page;
            if (clickedElement.dataset.anchor) {
                s += "|" + clickedElement.dataset.anchor;
            }
            if (clickedElement.innerText && clickedElement.innerText !== clickedElement.dataset.page) {
                s += "|" + clickedElement.innerText;
            }
            s += "))";
        }

        const md = tuiEditor.getMarkdown(),
            search = "$$widget0 " + s + "$$";
        let start = md.indexOf(search);

        tuiEditor.setSelection(start, start + search.length);

        while(tuiEditor.getSelectedText() !== search) {
            start++;
            tuiEditor.setSelection(start, start + search.length);
        }

    }

    var m = /\((.*)\(([^\|]*)\|?([^\|]*)\|?([^\|]*)\|?\)\)/g.exec(s);
    if (m && m.length > 4) {
        if ($("#tbWLinkRel")) {
            $("#tbWLinkRel").val(m[1]);
        }
        $("#tbWLinkPage").val(m[2]);
        if (m[4]) {
            if ($("#tbWLinkAnchor")) {
                $("#tbWLinkAnchor").val(m[3]);
            }
            $("#tbWLinkDesc").val(m[4]);
        } else {
            $("#tbWLinkDesc").val(m[3]);
        }
    } else {
        $("#tbWLinkDesc").val(s);
        if ($("#tbWLinkAnchor")) {
            $("#tbWLinkAnchor").val("");
        }
    }
}

// Object Link

function dialogObjectLinkOpen( area_id ) {
    dialogSelectElement( area_id, '((', '))' ) ;
    var m, s = getTASelection($("#" + area_id)[0]), title = "", url = "";
    m = /\((.*)\(([^\|]*)\|?([^\|]*)\|?([^\|]*)\|?\)\)/.exec(s);
    if (m) {
        if (m.length > 4 && (m[1] || m[4])) {
            alert(tr("Development notice: Semantic link types and anchors not fully supported by this tool, use the Wiki Link"));
        } else if (m.length > 3) {
            url = m[2];
            title = m[3];
        }
    } else {
        dialogSelectElement( area_id, "[", "]" ) ;
        s = getTASelection($('#' + area_id)[0]);
        m = /\[([^\|]*)\|?([^\]]*)]/.exec(s);
        if (m) {
            url = m[1];
            title = m[2];
        }
    }
    if (!title) {
        title = s;
    }
    $("#tbOLinkDesc").val(title);
    $("#tbOLinkObject").val(url);

    $("#tbOLinkObjectSelector").object_selector();

    $("#tbOLinkObjectType").on("change", function () {
        $("#tbOLinkObjectSelector").object_selector('setfilter', 'type', $(this).val());
    });

}

// External Link

function dialogExternalLinkOpen( area_id ) {
    if (window.elementPlus?.autocomplete) {
        autocomplete($("#tbWLinkPage")[0], 'pagename');
    }
    dialogSelectElement( area_id, '[', ']' ) ;
    var s = getTASelection($('#' + area_id)[0]);
    var m = /\[([^\|]*)\|?([^\|]*)\|?([^\|]*)\]/g.exec(s);
    if (m && m.length > 3) {
        $("#tbLinkURL").val(m[1]);
        $("#tbLinkDesc").val(m[2]);
        if (m[3]) {
            if ($("#tbLinkNoCache") && m[3] == "nocache") {
                $("#tbLinkNoCache").prop("checked", "checked");
            } else {
                $("#tbLinkRel").val(m[3]);
            }
        } else {
            $("#tbWLinkDesc").val(m[3]);
        }
    } else {
        if (s.match(/(http|https|ftp)([^ ]+)/ig) == s) { // v simple URL match
            $("#tbLinkURL").val(s);
        } else {
            $("#tbLinkDesc").val(s);
        }
    }
    if (!$("#tbLinkURL").val()) {
        $("#tbLinkURL").val("http://");
    }
}

function dialogFindFind( area_id ) {
    var ta = $('#' + area_id);
    var findInput = $("#tbFindSearch").removeClass("ui-state-error");

    var $textareaEditor = syntaxHighlighter.get(ta); //codemirror functionality
    if ($textareaEditor) {
        syntaxHighlighter.find($textareaEditor, findInput.val());
    }
    else { //standard functionality
        var s, opt, str, re, p = 0, m;
        s = findInput.val();
        opt = "";
        if ($("#tbFindCase").prop("checked")) {
            opt += "i";
        }
        str = ta.val();
        re = new RegExp(s, opt);
        p = getCaretPos(ta[0]);
        if (p && p < str.length) {
            m = re.exec(str.substring(p));
        }
        else {
            p = 0;
        }
        if (!m) {
            m = re.exec(str);
            p = 0;
        }
        if (m) {
            setSelectionRange(ta[0], m.index + p, m.index + s.length + p);
        }
        else {
            findInput.addClass("ui-state-error");
        }
    }
}

// Replace

function dialogReplaceOpen(area_id) {

    var s = getTASelection($('#' + area_id)[0]);
    $("#tbReplaceSearch").val(s).trigger("focus");

}

function dialogReplaceReplace( area_id ) {
    var findInput = $("#tbReplaceSearch").removeClass("ui-state-error");
    var s = findInput.val();
    var r = $("#tbReplaceReplace").val();
    var opt = "";
    if ($("#tbReplaceAll").prop("checked")) {
        opt += "g";
    }
    if ($("#tbReplaceCase").prop("checked")) {
        opt += "i";
    }
    var ta = $('#' + area_id);
    var str = ta.val();
    var re = new RegExp(s,opt);

    var textareaEditor = syntaxHighlighter.get(ta); //codemirror functionality
    if (textareaEditor) {
        syntaxHighlighter.replace(textareaEditor, s, r);
    }
    else { //standard functionality
        ta.val(str.replace(re, r));
    }

}

function displayEmojiPicker(pickerId, areaId)
{
    const $picker = $("#" + pickerId);
    if ($picker.is(":visible")) {
        $picker.hide();
    } else {
        $picker.show();
    }
}

