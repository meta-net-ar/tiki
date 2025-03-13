function renderUserMentionModal(areaId) {
    const usernamePattern = jqueryTiki.usernamePattern.substring(2, jqueryTiki.usernamePattern.length - 2); // trim /^ and $/
    $.openModal({
        title: tr("Mention a user"),
        content: `<input type="text" id="user-mentions" placeholder="${tr("Type a username")}" />`,
        dialogVariants: ["centered"],
        open: function () {
            autocomplete($("#user-mentions", this)[0], "userrealname", {
                select: (event) => {
                    const value = event.detail[0].value;
                    const username = value.match(new RegExp("\\((" + usernamePattern + ")\\)$"))[1];
                    insertAt(areaId, "@" + username);
                    $.closeModal();
                },
            });
        },
    });
}

/**
 * Show a dropdown of users following a @ char if...
 */

$.fn.userMentions = function () {
    let usernamePattern = jqueryTiki.usernamePattern;
    usernamePattern = usernamePattern.substring(2, usernamePattern.length - 2);    // trim /^ and $/

    if (window.elementPlus?.autocomplete) {
        const textarea = this;
        $(textarea).on("keydown", function (event) {
            if (event.key === "@") {
                event.preventDefault();
                renderUserMentionModal($(textarea).attr("id"));
            }
        });
        return;
    }

    this.each(function () {
        let $textarea = $(this), selection, mention;

        let mentionPattern = new RegExp("@(" + usernamePattern + ")$");

        $textarea.tiki("autocomplete", "userrealname", {
            tiki_replace_term: function (val) {
                selection = $textarea.selection();

                let start = val.lastIndexOf("@", selection.end);

                // find the last word typed starting with a @
                mention = val.substring(start, selection.end).match(mentionPattern);

                if (mention && mention[1].length > 1) {
                    mention = mention[1];
                    return mention;
                } else {
                    return "";
                }
            },
            select: function (event, data) {
                let val = $textarea.val();

                let regex = new RegExp("\\((" + usernamePattern + ")\\)$"),
                    userName = data.item.value.match(regex);

                if (userName) {
                    userName = userName[1];
                } else {
                    userName = data.item.value;
                }

                let start = val.lastIndexOf("@" + mention, selection.end);
                val = val.substring(0, start) +  "@" + userName + val.substring(start + 1 + mention.length);

                $textarea.val(val).selection(start + 1 + userName.length);

                return false;
            },
            open: function( event, ui ) {

                let $menu = $textarea.autocomplete("widget"),
                offset = $textarea.offset(),
                coords = getCaretCoordinates($textarea[0], selection.end);

                let lineHeight = parseInt($menu.css("font-size").replace(/\D+/, ""));
                $menu.css({
                    left: (offset.left + coords.left) + "px",
                    top:  (offset.top  + coords.top + lineHeight) + "px",
                    width: "auto",
                });

            },
        });

    });
};

// from https://stackoverflow.com/a/22446703/2459703

if (typeof getCaretCoordinates === "undefined") {
    function getCaretCoordinates(element, position) {
        // The properties that we copy into a mirrored div.
        // Note that some browsers, such as Firefox,
        // do not concatenate properties, i.e. padding-top, bottom etc. -> padding,
        // so we have to do every single property specifically.
        var properties = [
            'boxSizing',
            'width',  // on Chrome and IE, exclude the scrollbar, so the mirror div wraps exactly as the textarea does
            'height',
            'overflowX',
            'overflowY',  // copy the scrollbar for IE

            'borderTopWidth',
            'borderRightWidth',
            'borderBottomWidth',
            'borderLeftWidth',

            'paddingTop',
            'paddingRight',
            'paddingBottom',
            'paddingLeft',

            // https://developer.mozilla.org/en-US/docs/Web/CSS/font
            'fontStyle',
            'fontVariant',
            'fontWeight',
            'fontStretch',
            'fontSize',
            'lineHeight',
            'fontFamily',

            'textAlign',
            'textTransform',
            'textIndent',
            'textDecoration',  // might not make a difference, but better be safe

            'letterSpacing',
            'wordSpacing'
        ];

        var isFirefox = !(window.mozInnerScreenX == null);
        //var mirrorDivDisplayCheckbox = document.getElementById('mirrorDivDisplay');
        var mirrorDiv, computed, style;

        // mirrored div
        mirrorDiv = document.getElementById(element.nodeName + '--mirror-div');
        if (!mirrorDiv) {
            mirrorDiv = document.createElement('div');
            mirrorDiv.id = element.nodeName + '--mirror-div';
            document.body.appendChild(mirrorDiv);
        }

        style = mirrorDiv.style;
        computed = getComputedStyle(element);

        // default textarea styles
        style.whiteSpace = 'pre-wrap';
        if (element.nodeName !== 'INPUT') {
            style.wordWrap = 'break-word';
        }  // only for textarea-s

        // position off-screen
        style.position = 'absolute';  // required to return coordinates properly
        style.top = element.offsetTop + parseInt(computed.borderTopWidth) + 'px';
        style.left = "400px";
    //    style.visibility = mirrorDivDisplayCheckbox.checked ? 'visible' : 'hidden';  // not 'display: none' because we want rendering
        style.visibility = 'hidden';

        // transfer the element's properties to the div
        properties.forEach(function (prop) {
            style[prop] = computed[prop];
        });

        if (isFirefox) {
            style.width = parseInt(computed.width) - 2 + 'px';  // Firefox adds 2 pixels to the padding -
                                                               // https://bugzilla.mozilla.org/show_bug.cgi?id=753662
            // Firefox lies about the overflow property for textareas: https://bugzilla.mozilla.org/show_bug.cgi?id=984275
            if (element.scrollHeight > parseInt(computed.height)) {
                style.overflowY = 'scroll';
            }
        } else {
            style.overflow = 'hidden';  // for Chrome to not render a scrollbar; IE keeps overflowY = 'scroll'
        }

        mirrorDiv.textContent = element.value.substring(0, position);
        // the second special handling for input type="text" vs textarea: spaces need to be replaced with non-breaking spaces -
        // http://stackoverflow.com/a/13402035/1269037
        if (element.nodeName === 'INPUT') {
            mirrorDiv.textContent = mirrorDiv.textContent.replace(/\s/g, "\u00a0");
        }

        var span = document.createElement('span');
        // Wrapping must be replicated *exactly*, including when a long word gets
        // onto the next line, with whitespace at the end of the line before (#7).
        // The  *only* reliable way to do that is to copy the *entire* rest of the
        // textarea's content into the <span> created at the caret position.
        // for inputs, just '.' would be enough, but why bother?
        span.textContent = element.value.substring(position) || '.';  // || because a completely empty faux span doesn't render at all
        span.style.backgroundColor = "lightgrey";
        mirrorDiv.appendChild(span);

        var coordinates = {
            top: span.offsetTop + parseInt(computed['borderTopWidth']),
            left: span.offsetLeft + parseInt(computed['borderLeftWidth'])
        };

        return coordinates;
    }
}

