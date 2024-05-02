/*
=================================
    Constants used more than once
=================================
*/
const UPPER_CASE_CHARACTERS = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
const LOWER_CASE_CHARACTERS = "abcdefghijklmnopqrstuvwxyz";
const DIGITS = "0123456789";
const SPECIAL_CHARACTERS = "!@#$%^&*?_~";

// simple translation function for tiki 6
function tr(str) {
    // The lang object defined in lang/xx/language.js (included automatically) holds JS string translations.
    if (typeof lang !== "undefined" /* language.js is included after tiki-js.js. This prevents errors in case tr() is called before language.js is loaded. Ideally, language.js would be loaded before tr() is defined. */ && typeof lang[str] == 'string') {
        return lang[str];
    } else {
        return str;
    }
}

var lang = {};    // object to hold JS string translations
                // default strings empty, override in lang/xx/language.js
                // which will be included automatically

// end translation

function browser() {
    this.version = navigator.appVersion;
    this.v = parseInt(this.version, 10);
    this.op = (navigator.userAgent.indexOf('Opera')>-1);
    this.safari = (navigator.userAgent.indexOf('Safari')>-1);
    this.moz = (navigator.userAgent.indexOf('Mozilla')>-1);
    this.moz13 = (navigator.userAgent.indexOf('Mozilla')>-1 && navigator.userAgent.indexOf('1.3')>-1);
    this.oldmoz = (navigator.userAgent.indexOf('Mozilla')>-1 && navigator.userAgent.indexOf('1.4')>-1 || navigator.userAgent.indexOf('Mozilla')>-1 && navigator.userAgent.indexOf('1.5')>-1 || navigator.userAgent.indexOf('Mozilla')>-1 && navigator.userAgent.indexOf('1.6')>-1);
    this.docom = (this.op||this.safari||this.moz||this.oldmoz);
}

function toggle_dynamic_var(name) {
    var displayContainer = document.getElementById('dyn_'+name+'_display');
    var editContainer = document.getElementById('dyn_'+name+'_edit');

    // Create form element and append all inputs from "edit" span
    var form = document.createElement('form');
    form.setAttribute('method', 'post');
    form.setAttribute('name', 'dyn_vars');
    form.style.display = "inline";
    editContainer.parentNode.insertBefore(form, editContainer);
    form.appendChild(editContainer);

    // Show form
    if (displayContainer.style.display == "none") {
        editContainer.style.display = "none";
        displayContainer.style.display = "inline";
    } else {
        displayContainer.style.display = "none";
        editContainer.style.display = "inline";
    }
}

function chgArtType() {
    var articleType = document.getElementById('articletype').value;
    var typeProperties = articleTypes[articleType];

    var propertyList = ['show_topline','y',
                        'show_subtitle','y',
                        'show_linkto','y',
                        'show_author','y',
                        'use_ratings','y',
                        'heading_only','n',
                        'show_image_caption','y',
                        'show_pre_publ','y',
                        'show_post_expire','y',
                        'show_image','y',
                        'show_expdate','y'
                        ];
    if (typeof articleCustomAttributes != 'undefined') {
        propertyList = propertyList.concat(articleCustomAttributes);
    }
    var l = propertyList.length, property, value, display;
    for (var i=0; i<l; i++) {
        property = propertyList[i++];
        value = propertyList[i];

        if (typeProperties[property] == value || (!typeProperties[property] && value == "n")) {
            display = "";
        } else {
            display = "none";
        }

        if (document.getElementById(property)) {
            document.getElementById(property).style.display = display;
        } else {
            var j = 1;
            while (document.getElementById(property+'_'+j)) {
                document.getElementById(property+'_'+j).style.display = display;
                j++;
            }
        }
    }
}

function setMenuOptionFields(value) {
    const [url, name, section, perm] = value.split(',');
    document.getElementById('menu_url').value = url;
    document.getElementById('menu_name').value = name;
    document.getElementById('menu_section').value = section ?? '';
    document.getElementById('menu_perm').value = perm ?? '';
    flip('weburls');
}

function genPass(w1) {
    const passwordArray = [];
    //ensure at least 2 upper case letters, 2 numbers, and 2 special characters
    for (let i = 0; i < 8; i++) {
        if (i < 2) {
            passwordArray[i] = LOWER_CASE_CHARACTERS.charAt(Math.round(Math.random() * (LOWER_CASE_CHARACTERS.length - 1)));
        } else if (i < 4) {
            passwordArray[i] = UPPER_CASE_CHARACTERS.charAt(Math.round(Math.random() * (UPPER_CASE_CHARACTERS.length - 1)));
        } else if (i < 6) {
            passwordArray[i] = DIGITS.charAt(Math.round(Math.random() * (DIGITS.length - 1)));
        } else {
            passwordArray[i] = SPECIAL_CHARACTERS.charAt(Math.round(Math.random() * (SPECIAL_CHARACTERS.length - 1)));
        }
    }
    //shuffle the characters since they are blocks of 2 per above
    for (let i = passwordArray.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [passwordArray[i], passwordArray[j]] = [passwordArray[j], passwordArray[i]];
    }
    //implode into a string
    document.getElementById(w1).value = passwordArray.join('');
}

function setSelectionRange(textarea, selectionStart, selectionEnd) {
    var $textareaEditor = syntaxHighlighter.get($(textarea));
    if ($textareaEditor) {
        syntaxHighlighter.setSelection($textareaEditor, selectionStart, selectionEnd);
        return;
    }

    $(textarea).selection(selectionStart, selectionEnd);
}

function getTASelection(textarea) {
    const $textareaEditor = syntaxHighlighter.get($(textarea));
    if ($textareaEditor) return $textareaEditor.getSelection();

    const ta_id = $(textarea).attr("id");

    if (typeof (CKEDITOR) !== 'undefined') return CKEDITOR.instances[ta_id].getSelection().getSelectedText();

    if (typeof tuiEditors !== 'undefined' && tuiEditors[ta_id]) {
        const tuiEditor = tuiEditors[ta_id];
        const selection = tuiEditor.getSelection();
        let selectedText = "";

        //        if (tuiEditor.isWysiwygMode()) {
        //            // try to get the markdown syntax for the selected node (this doesn't work yet)
        //            tuiEditor.changeMode('markdown');
        //            const mdSelection = tuiEditor.convertPosToMatchEditorMode(selection[0], selection[1]);
        //            tuiEditor.setSelection(mdSelection[0], mdSelection[1]);
        //            // still doesn't select the whole thing, i.e. for a link it only gets the label FIXME
        //            selectedText = tuiEditor.getSelectedText();
        //            tuiEditor.changeMode('wysiwyg');
        //        } else {
        selectedText = tuiEditor.getSelectedText(selection);
        //        }
        //        tuiEditor.setSelection(selection[0], selection[0] + selectedText.length);

        return selectedText;

    }
    if (typeof $(textarea).attr("selectionStartSaved") != 'undefined' && $(textarea).attr("selectionStartSaved")) { // forgetful firefox/IE now
        return textarea.value.substring($(textarea).attr("selectionStartSaved"), $(textarea).attr("selectionEndSaved"));
    }

    if ((typeof textarea != 'undefined') && (typeof textarea.selectionStart != 'undefined')) {
        return textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);
    }

    // IE
    const range = document.selection.createRange();
    return range.text;
}

var ieFirstTimeInsertKludge = null;

function storeTASelection( area_id ) {
    if (typeof CKEDITOR === 'undefined' || typeof CKEDITOR.instances[area_id] === 'undefined') {
        var $el = $("#" + area_id);
        var sel = $el.selection();
        $el.attr("selectionStartSaved", sel.start)
                .attr("selectionEndSaved", sel.end)
                .attr("scrollTopSaved", $el.attr("scrollTop"));
    }
    if (ieFirstTimeInsertKludge === null) {
        ieFirstTimeInsertKludge = true;
    }
}

const setCaretToPos = (textarea, pos) => setSelectionRange(textarea, pos, pos);

function getCaretPos(textarea) {
    var $textareaEditor = syntaxHighlighter.get($(textarea));

    if ($textareaEditor) return $textareaEditor.cursorCoords().x ?? 0;

    if (typeof textarea.selectionEnd != 'undefined') return textarea.selectionEnd;

    return 0;
}

function insertAt(elementId, replaceString, blockLevel, perLine, replaceSelection) {

    // inserts given text at selection or cursor position
    var $textarea = $('#' + elementId);
    var $textareaEditor = syntaxHighlighter.get($textarea);
    var toBeReplaced = /text|page|area_id/g; //substrings in replaceString to be replaced by the selection if a selection was done
    var hiddenParents = $textarea.parents('fieldset:hidden').last();
    if (hiddenParents.length) { hiddenParents.show(); }

    let isPlugin = replaceString.match(/^\s?\{/m);        // do match in two halves due to multiline problems
    if (isPlugin) {
        isPlugin = replaceString.match(/}\s?$/m);        // not so simple {plugin} match
    }
    isPlugin = isPlugin && isPlugin.length > 0;

    if ($textareaEditor) {
         syntaxHighlighter.insertAt($textareaEditor, replaceString, perLine, blockLevel, replaceSelection);
        return;
     // get ckeditor handling out of the way - can only be simple text insert for now
    } else if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[elementId]) {
        // get selection from ckeditor
        var cked = CKEDITOR.instances[elementId];
        if (cked) {

            var sel = cked.getSelection(), rng;
            if (sel) { // not from IE sometimes?
                rng = sel.getRanges();
                if (rng.length) {
                    rng = rng[0];
                }
            }
            var plugin_el, com;
            if (isPlugin && rng && !rng.collapsed) {
                com = cked.getSelection().getStartElement();
                if (typeof com !== 'undefined' && com && com.$) {
                    while (!$(com.$).hasClass("tiki_plugin") && com.$.nextSibling && com.$ !== rng.endContainer.$) {    // loop through selection if multiple elements
                        com = new CKEDITOR.dom.element(com.$.nextSibling);
                        if ($(com.$).hasClass("tiki_plugin") || $(com.$).find(".tiki_plugin").length === 0) {    // found it or parent (hmm)
                            break;
                        }
                    }
                    if (!$(com.$).hasClass("tiki_plugin")) { // not found it yet?
                        plugin_el = $(com.$).find(".tiki_plugin"); // using jQuery
                        if (plugin_el.length == 1) { // found descendant plugin
                            com = new CKEDITOR.dom.element(plugin_el[0]);
                        } else {
                            plugin_el = $(com.$).parents(".tiki_plugin").last(); // try parents
                            if (plugin_el.length == 1) { // found p plugin
                                com = new CKEDITOR.dom.element(plugin_el[0]);
                            } else { // still not found it? sometimes Fx seems to get the editor body as the selection...
                                var plugin_type = replaceString.match(/^\s?\{([\w]+)/);
                                if (plugin_type.length > 1) { plugin_type = plugin_type[1].toLowerCase(); }

                                plugin_el = $(com.$).find("[plugin=" + plugin_type + "].tiki_plugin"); // find all of them
                                if (plugin_el.length == 1) { // good guess!
                                    com = new CKEDITOR.dom.element(plugin_el[0]);
                                } else {
                                    // Does not seem to be a problem at least with the image plugin, commenting out for release but keeping it here in case problem reappears
                                    //if (!confirm(tr("Development notice: Could not find plugin being edited, sorry. Choose cancel to debug."))) {
                                    //    debugger;
                                    //}
                                }
                            }
                        }
                    }
                }
                if (com && com.hasClass("tiki_plugin")) {
                    var html = cked.getData().replace(com.data("syntax"), replaceString);
                    cked.setData(html);
                    return;
                }
            }
            // catch all other issues and do the insert wherever ckeditor thinks best,
            // sadly as the first element sometimes FIXME
            //cked.focus();    seems calling focus here makes the editor focus disappear in webkit - still FIXME

            try {
                cked.insertText(replaceString);
            } catch (e) {
                prompt(tr("Development notice: The editor selection has been lost, here is the text to insert."), replaceString);
                return;
            }
            if (typeof cked.reParse === "function" &&    // also ((wiki links)) or tables
                (isPlugin || replaceString.match(/^\s?\(\(.*?\)\)\s?$/) || replaceString.match(/^||.*||$/))) {

                var bookmarks = cked.getSelection().createBookmarks2(true);    // remember selection

                cked.reParse();

                cked.getSelection().selectBookmarks( bookmarks );        // restore selection
            }
        }
        return;
    } else if (typeof tuiEditors !== 'undefined' && tuiEditors[elementId]) {
        const tuiEditor = tuiEditors[elementId],
            selection = tuiEditor.getSelection();

        if (isPlugin) {
            replaceString = "$$tiki\n" + replaceString + " \n$$\n";
        }

        tuiEditor.replaceSelection(replaceString);
        // make the wysiwyg panel redraw itself
        if (tuiEditor.isWysiwygMode()) {
            tuiEditor.setMarkdown(tuiEditor.getMarkdown());
            tuiEditor.setSelection(selection[0], selection[0] + replaceString.length);
        }
        return;
    }

    if (!$textarea.length && elementId === "fgal_picker") {    // ckeditor file browser
        $(".cke_dialog_contents").find("input").first().val(replaceString.replace("&amp;", "&"));
        return;
    } else if ($textarea.is(":input") && elementId === "fgal_picker_id") {
        $textarea.val(replaceString);
        return;
    }

    $textarea.trigger("focus");

    var val = $textarea.val();
    var selection = $textarea.selection();
    var scrollTop=$textarea[0].scrollTop;

    if (selection.start === 0 && selection.end === 0 &&
                    typeof $textarea.attr("selectionStartSaved") != 'undefined') {    // get saved textarea selection
        if ($textarea.attr("selectionStartSaved")) {    // forgetful firefox/IE
            selection.start = $textarea.attr("selectionStartSaved");
            selection.end = $textarea.attr("selectionEndSaved");
            if ($textarea.attr("scrollTopSaved")) {
                scrollTop = $textarea.attr("scrollTopSaved");
                $textarea.attr("scrollTopSaved", "");
            }
            $textarea.attr("selectionStartSaved", "").attr("selectionEndSaved", "");
        } else {
            selection.start = getCaretPos($textarea[0]);
            selection.end = selection.start;
        }
    }

    // deal with IE's two char line ends
    var lines, startoff = 0, endoff = 0;
    if ($textarea[0].createTextRange && $textarea[0].value !== val) {
        val = $textarea[0].value;    // use raw value of the textarea
        if (val.substring(selection.start, selection.start + 1) === "\n") {
            selection.start++;
        }
        lines = val.substring(0, selection.start).match(/\r\n/g);
            if (lines) {
            startoff -= lines.length;    // remove one char per line for IE
            }
        }
    var selectionStart = selection.start;
    var selectionEnd = selection.end;

    if ( blockLevel ) {
        // Block level operations apply to entire lines

        // +1 and -1 to handle end of line caret position correctly
        selectionStart = val.lastIndexOf( "\n", selectionStart - 1 ) + 1;
        var blockEnd = val.indexOf( "\r", selectionEnd ); // check for IE first
        if (blockEnd < 0) {
            selectionEnd = val.indexOf( "\n", selectionEnd );
        } else {
            selectionEnd = blockEnd;
        }
        if (selectionEnd < 0) {
            selectionEnd = val.length;
        }
    }

    var newString = '';
    if ((selectionStart != selectionEnd) && !$textareaEditor) { // has there been a selection
        if ( perLine ) {
            lines = val.substring(selectionStart, selectionEnd).split("\n");
            for( var k = 0; lines.length > k; ++k ) {
                if ( lines[k].length !== 0 ) {
                    newString += replaceString.replace(toBeReplaced, lines[k]);
                }
                if ( k != lines.length - 1 ) {
                    newString += "\n";
                }
            }
        } else {
            if (replaceSelection) {
                newString = replaceString;
            } else if (replaceString.match(toBeReplaced)) {
                var myRegex = replaceString.replace(toBeReplaced, '(.+?)');
                myRegex = new RegExp('^'+myRegex+'$', 'g');
                var isReplaced = myRegex.exec(val.substring(selectionStart, selectionEnd));
                if (isReplaced) {
                    newString = isReplaced[1];
                } else {
                    newString = replaceString.replace(toBeReplaced, val.substring(selectionStart, selectionEnd));
                }
            } else {
                newString = replaceString + '\n' + val.substring(selectionStart, selectionEnd);
            }
        }

        $textarea.val(val.substring(0, selectionStart)
                        + newString
                        + val.substring(selectionEnd)
                    );
        lines = newString.match(/\r\n/g);
        if (lines) {
            endoff   -= lines.length;    // lines within the replacement for IE
        }
        setSelectionRange($textarea[0], selectionStart + startoff, selectionStart + startoff + newString.length + endoff);

    } else { // insert at caret
        $textarea.val(val.substring(0, selectionStart)
                        + replaceString
                        + val.substring(selectionEnd)
                    );
        lines = replaceString.match(/\r\n/g);
        if (lines) {
            endoff   -= lines.length;    // lines within the replacement for IE
        }
        setCaretToPos($textarea[0], selectionStart + startoff + replaceString.length + endoff);

    }
    $textarea.attr("scrollTop", scrollTop);
    if (this.iewin && ieFirstTimeInsertKludge) {
        setTimeout(function(){        // not only does IE reset the scrollTop and selection the first time a dialog is used
            if (newString.length) {    // but somehow all the ints have been converted into strings...
                setSelectionRange($textarea[0], parseInt(selectionStart,10) + parseInt(startoff,10),
                        parseInt(selectionStart,10) + parseInt(startoff,10) + newString.length + parseInt(endoff,10));
            }
            $textarea.attr("scrollTop", scrollTop);
        }, 1000);
        ieFirstTimeInsertKludge = false;
    }

    if (hiddenParents.length) { hiddenParents.hide(); }
    if (typeof auto_save === "function") {
        auto_save(elementId);
    }
}

function setUserModuleFromCombo(id, textarea) {
    document.getElementById(textarea).value = document.getElementById(textarea).value
                + document.getElementById(id).options[document.getElementById(id).selectedIndex].value;
}


function toggle(elementId) {
    if ($("#" + elementId).css('display') !== "none") {
        hide(elementId, true, "menu");
    } else {
        show(elementId, true, "menu");
    }
}

function flip_thumbnail_status(id) {
    var elem = document.getElementById(id);
    if ( elem.className == 'thumbnailcontener' ) {
        elem.className += ' thumbnailcontenerchecked';
    } else {
        elem.className = 'thumbnailcontener';
    }
}

function tikitabs( focus, tabElement) {
    var container, ofocus = focus;
    if (typeof tabElement === "undefined") {
        container = $(".tabset").first();
    } else {
        container = $(tabElement).parents(".tabset").first();
    }

    if (focus > $("> .tabs .tabmark", container).length) {
        focus = 1;    // limit to number of tabs - somehow getting set to 222 sometimes
    }

    while ($("> .tabs .tabmark.tab" + focus, container).first().is(":hidden")) {
        focus++;
    }
    if ($("> .tabs .tabmark.tab" + focus, container).first().length === 0) {
        focus = ofocus;
    }

    const getFilterFirstFn = (className) => function(index) {
        return !($(this).hasClass(className + focus) && index === 0);
    };
    $("> .tabs .tabmark", container).filter(getFilterFirstFn("tab")).removeClass("tabactive");        // may need .addClass("tabinactive");
    $("> .tabs .tabmark.tab" + focus, container).first().addClass("tabactive");                // and .removeClass("tabinactive");
    $("> .tabcontent", container).filter(getFilterFirstFn("content")).hide();
    $("> .tabcontent.content" + focus, container).first().show();
    setCookie( $(".tabs", container).first().data("name"), focus, "tabs", "session");

}

function setheadingstate(foo) {
    var status = getCookie(foo, "showhide_headings");
    var $foo = $("#" + foo);
    if (status == "o") {
        $foo.show();
        collapseSign("flipper" + foo);
    } else if (status == "c") {
        $foo.hide();
        expandSign("flipper" + foo);
    }
}

function icntoggle(foo, img) {
    var $icn = $("#icn" + foo);
    var src = $icn.attr("src");
    if (!src) {
        src = "";
    }
    if (!img) {
        if (src.search(/[\\\/]/)) {
            img = src.replace(/.*[\\\/]([^\\\/]*)$/, "$1");
        } else {
            img = 'folder.png';
        }
    }
    if ($("#" + foo + ":hidden").length) {
        show(foo, true, "menu");
        $icn.attr("src", src.replace(/[^\\\/]*$/, 'o' + img));

    } else {
        hide(foo, true, "menu");
        img = img.replace(/(^|\/|\\)o(.*)$/, '$1$2');
        $icn.attr("src", src.replace(/[^\\\/]*$/, img));
    }
}

/**
 * New version of icntoggle function above to better deal with iconsets
 * Different than the above function, both versions of the icon are included in the template with one hidden
 * @param foo
 * @param clicked
 */

function icontoggle(foo, clicked) {
    //expand or collapse
    if ($("#" + foo + ":hidden").length) {
        show(foo, true, "menu");
    } else {
        hide(foo, true, "menu");
    }
    //toggle icon display
    var id = clicked.id;
    $('#' + id + ' .toggle-open').toggle();
    $('#' + id + ' .toggle-closed').toggle();
    return false;
}

//name - name of the cookie
//value - value of the cookie
// [expires] - expiration date of the cookie (defaults to end of current session)
// [path] - path for which the cookie is valid (defaults to path of calling document)
// [domain] - domain for which the cookie is valid (defaults to domain of calling document)
// [secure] - Boolean value indicating if the cookie transmission requires a secure transmission
//* an argument defaults when it is assigned null as a placeholder
//* a null placeholder is not required for trailing omitted arguments
function setSessionVar(name, value) {
    fetch("tiki-cookie-jar.php?" + name + "=" + encodeURIComponent(value));

    if (tiki_cookie_jar) {
        tiki_cookie_jar[name] = value;
    }
}

function setCookie(name, value, section, expires, path, domain, secure) {
    if (getCookie(name, section) == value) {
        return true;
    }
    if (!expires) {
        expires = new Date();
        expires.setFullYear(expires.getFullYear() + 1);
    }
    if (expires === "session") {
        expires = "";
    }
    if (typeof jqueryTiki != "undefined" && jqueryTiki.no_cookie) {
        try {
            fetch("tiki-cookie-jar.php?" + name + "=" + encodeURIComponent(value));
            tiki_cookie_jar[name] = value;
            return true;
        } catch (ex) {
            setCookieBrowser(name, value, section, expires, path, domain, secure);
            return false;
        }
    }

    setCookieBrowser(name, value, section, expires, path, domain, secure);
    return true;
}

function setCookieBrowser(name, value, section, expires, path, domain, secure) {
    if (section) {
        var valSection = getCookie(section);
        var name2 = "@" + name + ":";
        if (valSection) {
            if (new RegExp(name2).test(valSection)) {
                valSection  = valSection.replace(new RegExp(name2 + "[^@;]*"), name2 + value);
            } else {
                valSection = valSection + name2 + value;
            }
            setCookieBrowser(section, valSection, null, expires, path, domain, secure);
        }
        else {
            valSection = name2+value;
            setCookieBrowser(section, valSection, null, expires, path, domain, secure);
        }

    }
    else {
        document.cookie = name + "=" + encodeURIComponent(value) + ((expires) ? "; expires=" + expires.toGMTString() : "")
        + ((path) ? "; path=" + path : "") + ((domain) ? "; domain=" + domain : "") + ((secure) ? "; secure" : "");
    }
}

//name - name of the desired cookie
//section - name of group of cookies or null
// * return string containing value of specified cookie or null if cookie does not exist
function getCookie(name, section, defval) {
    if ( typeof jqueryTiki != "undefined" && jqueryTiki.no_cookie && typeof tiki_cookie_jar != "undefined" && tiki_cookie_jar.length > 0) {
        if (typeof tiki_cookie_jar[name] == "undefined") {
            return defval;
        }
        return tiki_cookie_jar[name];
    }
    else {
        return getCookieBrowser(name, section, defval);
    }
}
function getCookieBrowser(name, section, defval = null) {
    if (section) {
        var valSection = getCookieBrowser(section);
        if (valSection) {
            var name2 = "@"+name+":";
            var val = valSection.match(new RegExp(name2 + "([^@;]*)"));
            if (val) {
                return decodeURIComponent(val[1]);
            }
        }
        return defval;
    }

    var dc = document.cookie;

    var prefix = name + "=";
    var begin = dc.indexOf("; " + prefix);

    if (begin == -1) {
        begin = dc.indexOf(prefix);

        if (begin !== 0) {
            return defval;
        }
    } else { begin += 2; }

    var end = document.cookie.indexOf(";", begin);

    if (end == -1) {
        end = dc.length;
    }
    return decodeURIComponent(dc.substring(begin + prefix.length, end));
}

//name - name of the cookie
//[path] - path of the cookie (must be same as path used to create cookie)
// [domain] - domain of the cookie (must be same as domain used to create cookie)
// * path and domain default if assigned null or omitted if no explicit argument proceeds
function deleteCookie(name, section, expires, path, domain, secure) {
    if (section) {
        var valSection = getCookieBrowser(section);
        var name2 = "@" + name + ":";
        if (valSection) {
            if (new RegExp(name2).test(valSection)) {
                valSection  = valSection.replace(new RegExp(name2 + "[^@;]*"), "");
                setCookieBrowser(section, valSection, null, expires, path, domain, secure);
            }
        }
    }
    else {
        document.cookie = name + "="
        + ((path) ? "; path=" + path : "") + ((domain) ? "; domain=" + domain : "") + "; expires=Thu, 01-Jan-70 00:00:01 GMT";
    }
}

function flipWithSign(foo) {
    if (document.getElementById(foo).style.display == "none") {
        show(foo, true, "showhide_headings");
        collapseSign("flipper" + foo);
    } else {
        hide(foo, true, "showhide_headings");
        expandSign("flipper" + foo);
    }
}

function expandSign(foo) {
    if (document.getElementById(foo)) {
        document.getElementById(foo).firstChild.nodeValue = "[+]";
    }
}

function collapseSign(foo) {
    if (document.getElementById(foo)) {
        document.getElementById(foo).firstChild.nodeValue = "[-]";
    }
}

// Set client timezone
// moved to lib/setup/javascript.php

/** \brief: insert img tag in textarea
 *
 */
function insertImgFile(elementId, fileId, oldfileId, type, page, attach_comment) {
    const textarea = $('#' + elementId)[0];
    const fileup = $('input[name=' + fileId + ']')[0];
    const oldfile = $('input[name=' + oldfileId + ']')[0];
    let prefixEl = $('input[name=prefix]')[0];
    let prefix = "img/wiki_up/";

    if (!textarea || !fileup) {
        return;
    }
    if (prefixEl) { prefix = prefixEl.value; }

    let filename = fileup.files[0].name.replaceAll(' ', ''), str;
    let oldfilename = oldfile.value;

    if (filename == oldfilename || filename === "") { // insert only if name really changed
        return;
    }
    oldfile.value = filename;

    if (type == "file") {
        str = "{file name=\"" + filename + "\"";
        var desc = $('#' + attach_comment).val();
        if (desc) {
            str = str + " desc=\"" + desc + "\"";
        }
        str = str + "}";
    } else {
        str = "{img src=\"" + prefix + filename + "\" }\n";
    }
    insertAt(elementId, str);
}

/* add new upload image form in page edition */
let img_form_count = 2;
function addImgForm(e) {
    e.preventDefault();
    const input = document.createElement('input');
    input.name = 'picfile' + img_form_count;
    input.className = 'form-control';
    input.type = 'file';
    input.onchange = insertImgFile.bind(null, 'editwiki', 'picfile' + img_form_count, 'hasAlreadyInserted', 'img');
    e.target.insertAdjacentElement('beforebegin', input);
    needToConfirm = true;
    img_form_count ++;
}

browser();

//This was added to allow wiki3d to change url on tiki's window
window.name = 'tiki';

var fgals_window = null;

function openFgalsWindow(filegal_manager_url, reload) {
    if (fgals_window && fgals_window.document && !fgals_window.closed) {
        if (reload) {
            fgals_window.location.replace(filegal_manager_url);
        }
        fgals_window.focus();
    } else {
        fgals_window=window.open(filegal_manager_url,'_blank','menubar=1,scrollbars=1,resizable=1,height=500,width=800,left=50,top=50');
    }
    $(window).on("pagehide", function(){    // tidy
        fgals_window.close();
    });
}

/* Count the number of words (spearated with space) */
function wordCount(maxSize, source, cpt, message) {
    const formcontent = source.value.trim().split(/\s+/);
    if (maxSize > 0 && formcontent.length > maxSize) {
        alert(message);
        source.value = source.value.substring(0, source.value.length-1);
    } else {
        document.getElementById(cpt).value = formcontent.length;
    }
}
function charCount(maxSize, source, cpt, message) {
    var formcontent = source.value.replace(/(\r\n|\n|\r)/g, '  ');
    if (maxSize > 0 && formcontent.length > maxSize) {
        alert(message);
        source.value = source.value.substring(0, maxSize);
    } else {
        document.getElementById(cpt).value = formcontent.length;
    }
}

//Password strength
//Based from code by:
//Matthew R. Miller - 2007
//www.codeandcoffee.com
//originally released as "free software license"

/*
 * Password Strength Algorithm:
 *
 * Password Length: 5 Points: Less than 4 characters 10 Points: 5 to 7
 * characters 25 Points: 8 or more
 *
 * Letters: 0 Points: No letters 10 Points: Letters are all either lower case or upper case, 20
 * Points: Letters are upper case and lower case
 *
 * Numbers: 0 Points: No numbers 10 Points: 1 or 2 numbers 20 Points: 3 or more
 * numbers
 *
 * Characters: 0 Points: No characters 10 Points: 1 character 25 Points: More
 * than 1 character
 *
 * Bonus: 2 Points: Letters and numbers 3 Points: Letters, numbers, and
 * characters 5 Points: Mixed case letters, numbers, and characters
 *
 * Password Text Range: >= 90: Very Secure >= 80: Secure >= 70: Very Strong >=
 * 60: Strong >= 50: Average >= 25: Weak >= 0: Very Weak
 *
 */

//Check password
function getPasswordScore(strPassword)
{
    // Reset combination count
    let nScore = 0;

    // Password length
    // -- Less than 4 characters
    if (strPassword.length < 4)
    {
        nScore += 5;
    }
    // -- 5 to 7 characters
    else if (strPassword.length > 4 && strPassword.length < 8)
    {
        nScore += 10;
    }
    // -- 8 or more
    else if (strPassword.length > 7)
    {
        nScore += 25;
    }

    // Letters
    const nUpperCount = countContain(strPassword, UPPER_CASE_CHARACTERS);
    const nLowerCount = countContain(strPassword, LOWER_CASE_CHARACTERS);
    const nLowerUpperCount = nUpperCount + nLowerCount;
    // -- Letters are upper case and lower case
    if (nUpperCount && nLowerCount) {
        nScore += 20;
    } else if (nLowerUpperCount) {
        nScore += 10; // Either upper or lower case
    }

    // Numbers
    const nNumberCount = countContain(strPassword, DIGITS);
    if (nNumberCount >= 3) {
        nScore += 20;
    } else {
        nScore += 10;
    }

    // Special Characters
    const nCharacterCount = countContain(strPassword, SPECIAL_CHARACTERS);
    // -- 1 character
    if (nCharacterCount == 1) {
        nScore += 10;
    }
    // -- More than 1 character
    if (nCharacterCount > 1) {
        nScore += 25;
    }

    // Bonus
    // -- Letters and numbers
    if (nNumberCount && nLowerUpperCount) {
        nScore += 2;
    }
    // -- Letters, numbers, and characters
    if (nNumberCount && nLowerUpperCount && nCharacterCount) {
        nScore += 3;
    }
    // -- Mixed case letters, numbers, and characters
    if (nNumberCount && nUpperCount && nLowerCount && nCharacterCount) {
        nScore += 5;
    }

    return nScore;
}

//Runs password through check and then updates GUI
function runPassword(strPassword, strFieldID)
{
    // Get controls
    const ctlBar = document.getElementById(strFieldID + "_bar");
    const ctlText = document.getElementById(strFieldID + "_text");
    const ctlTextInner = document.getElementById(strFieldID + "_text_inner");
    if (!ctlBar || !ctlText || !ctlTextInner) {
        return;
    }
    if (strPassword.length > 0) {
        // Check password
        const nScore = getPasswordScore(strPassword);

        // Set new width
        ctlBar.style.width = nScore + "%";

        let icon = 'error', strText = tr("Very Weak"), strColor = '#ff0000';
        if (nScore >= 60) {
            icon = 'ok';
            strColor = "#0ca908";
            if (nScore >= 90) {
                strText = tr("Very Secure");
            } else if (nScore >= 80) {
                strText = tr("Secure");
            } else if (nScore >= 70) {
                strText = tr("Very Strong");
            } else {
                strText = tr("Strong");
            }
        } else if (nScore >= 40) {
            icon = 'none';
            strText = tr("Average");
            strColor = "#e3cb00";
        } else if (nScore >= 25) {
            strText = tr("Weak");
        }
        ctlBar.style.backgroundColor = strColor;
        $(ctlBar).show();
        if (icon === 'none') {
            $(ctlText).children('span.icon').hide();
        } else {
            $(ctlText).children(`span.icon-${icon}`).css('color', strColor).show();
            $(ctlText).children(`span.icon-${icon === 'ok' ? 'error': 'ok'}`).hide();
        }
        $(ctlTextInner).text(tr('Strength') + ': ' + strText).show();
    } else {
        $(ctlText).children().hide();
        $(ctlTextInner).hide();
        $(ctlBar).hide();
    }
}

// Get number of string character occurences in a set of characters
function countContain(strPassword, strCheck) {
    // Declare variables
    let nCount = 0;

    for (let i = 0; i < strPassword.length; i++) {
        if (strCheck.indexOf(strPassword.charAt(i)) > -1) {
            nCount++;
        }
    }

    return nCount;
}

function checkPasswordsMatch(in1, in2, el) {
    if ($(in1).val().length) {
        if ($(in1).val() == $(in2).val()) {
            $(el).children('#match').show();
            $(el).children('#nomatch').hide();
            return true;
        } else {
            $(el).children('#match').hide();
            $(el).children('#nomatch').show();
            return false;
        }
    } else {
        $(el).children().hide();
    }
}

/**
 * Adds an Option to the quickpoll section.
 */
function pollsAddOption()
{
    const newOption = $( '<input />')
        .attr('type', 'text')
        .attr('name', 'options[]')
        .attr('placeholder', tr('New option'))
        .addClass('form-control');
    $('#tikiPollsOptions').append($('<div class="mb-2"></div>').append(newOption));
}

/**
 * toggles the quickpoll section
 */
function pollsToggleQuickOptions()
{
    $( '#tikiPollsQuickOptions' ).toggle(function () {
        if (this.style.display === 'none') {
            $('#tikiPollsOptionsButton').text(tr('Show Options'));
        } else {
            $('#tikiPollsOptionsButton').text(tr('Hide Options'));
        }
    });
}

/**
 * toggles div for droplist with Disabled option
 */

function hidedisabled(divid,value) {
    if (value=='disabled') {
        document.getElementById(divid).style.display = 'none';
    } else {
        document.getElementById(divid).style.display = 'block';
    }
}

const open_webdav = (url) => prompt(tr('URL to open this file with WebDAV'), url);

const ccsValueToInteger = (str) => parseInt(str.replace(/[^\d]*$/, ""), 10) || 0;

// function to allow multiselection in checkboxes
// must be called like this :
//
// <input type="checkbox" class="form-check-input" onclick="checkbox_list_check_all(form_name,[checkbox_name_1,checkbox_name2 ...],true|false);">
/*
    Used in messu-* templates. This could be replaced with the "select_all" smarty function, and adjusting the php code a little bit. - mercijacob - 2024-04-12
 */
function checkbox_list_check_all(form,list,checking) {
  for (var checkbox in list) {
    document.forms[form].elements[list[checkbox]].checked=checking;
  }
}

if (!window.syntaxHighlighter) {
    window.syntaxHighlighter = {
        get: function() {return null;}
    };
}

/**
* Wrapper for javascript decodeURI
*/
const tiki_decodeURI = (encstr) => decodeURI(encstr.replace(/\+/g, " "));

/**
* Wrapper for javascript decodeURIComponent
*/
const tiki_decodeURIComponent = (encstr) => decodeURIComponent(encstr.replace(/\+/g, " "));

//Date helpers for to and from unix times
Date.prototype.toUnix = function() {
    return Math.round(this.getTime() / 1000.0);
};

Date.parseUnix = function(date) {
    date = new Date(Date.parse(date));
    return date.toUnix();
};

/**
 * Tracker rating field adjust after voing using ajax
 * (when rendered in search results)
 *
 * @param data    array containing result
 *            'my_rate',
 *            'numvotes',
 *            'voteavg',
 *            'request_rate',
 *            'value',
 *            'mode',
 *            'labels',
 *            'rating_options'
 */

function adjustRating(data) {

    const $sibs = $("span > a#star"), $help = $("#num-votes"), $unvote = $("#unvote");

    for (let i = 0; i < $sibs.length; i++) {
        const v = $($sibs[i]).data("vote");
        let icon = "";

        if (v <= data[0].voteavg && data[0].numvotes > 0) {
            if (data[0].result && data[0].my_rate == v) {
                icon = 'star-selected';
            } else {
                icon = 'star';
            }
        } else if (v - data[0].voteavg <= 0.5 && data[0].numvotes > 0) {
            if (data[0].result && data[0].my_rate == v) {
                icon = 'star-half-selected';
            } else {
                icon = 'star-half-rating';
            }
        } else {
            if (data[0].result && data[0].my_rate == v) {
                icon = 'star-empty-selected';
            } else {
                icon = 'star-empty';
            }
        }
        $($sibs[i]).find('.icon-' + icon).css('display', 'inline');
        $($sibs[i]).find('.icon').not('.icon-' + icon).css('display', 'none');
    }

    var t = tr("Number of votes:") + " " + data[0].numvotes + ", " + tr("Average:") + " " + data[0].voteavg;
    if (data[0].result) {
        if (data[0].my_rate != "NULL") {
            t = t + ", " + tr("Your rating:") + " " + data[0].my_rate;
            $unvote.show();
        } else {
            $unvote.hide();
        }
    } else {
        t = t + ", " + tr("Vote not accepted");
    }
    $help.text("(" + data[0].numvotes + ")")
        .next().attr("title", t);
}

function sendVote(element, itemId, fieldId, vote) {
    $(element).parent().tikiModal(" ");
    $.getJSON(
        $.service(
            'tracker',
            'vote',
            { i: itemId, f: fieldId, v: vote }
        ), function (data) {
            $(element).parent().tikiModal();
            adjustRating(data);
        }
    );
}

/**
 *
 * @param str string    Query or hash string to parse
 * @returns object
 */
function parseQuery(str) {
    let arr, pair, key, val, out = {}, b1, b2, key2;

    if (str.substring(0, 1) === "?" || str.substring(0, 1) === "#") {
        str = str.substring(1);
    }
    arr = str.split("&");
    for (let i = 0; i < arr.length; i++) {
        pair = arr[i].split("=");
        key = tiki_decodeURIComponent(pair[0]);
        val = pair.length > 1 ? tiki_decodeURIComponent(pair[1]) : "";

        if ((b1 = key.indexOf("[")) > -1 && (b2 = key.substring(b1 + 1).indexOf("]")) > -1) {
            key2 = key.substring(b1 + 1, b2);
            key = key.substring(0, b1);
            if (key2) {
                if (typeof out[key] != "object") {
                    out[key] = {};
                }
                out[key][key2] = val;
            } else {
                if (typeof out[key] != "object") {
                    out[key] = [];
                }
                out[key].push(val);
            }
        } else {
            out[key] = val;
        }
    }
    return out;
}

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        const progressBar = document.getElementById("progressBar");

        if (progressBar) {
            progressBar.remove();
        }
    }, 500);
});
