/**
 * Show a dropdown of users following a @ char if...
 */

// Use var so this script can be loaded more than once
var MENTION_MIN_CHARS = 2;
var MENTION_DELAY = 300;
var mentionRequest = mentionRequest || null;

$.fn.userMentions = function () {
    const textarea = this;
    let mentionTimer = null;
    let mentionSequence = 0;

    $(textarea).on("input", function () {
        const element = this;
        const cursorPosition = element.selectionStart;
        const textBeforeCursor = element.value.substring(0, cursorPosition);
        const sequence = ++mentionSequence;

        clearTimeout(mentionTimer);

        if (!getMentionTerm(textBeforeCursor)) {
            abortMentionRequest();
            $("#user-mentions-dropdown").remove();
            return;
        }

        mentionTimer = setTimeout(async () => {
            const suggestions = await fetchMentions(textBeforeCursor);
            if (sequence !== mentionSequence) {
                return; // a newer lookup is pending
            }
            if (suggestions.length) {
                showSuggestionsDropdown(suggestions, cursorPosition);
            } else {
                $("#user-mentions-dropdown").remove();
            }
        }, MENTION_DELAY);
    });
};

/**
 * Return the username typed after @, or null if missing or too short
 */
function getMentionTerm(selectionText) {
    let usernamePattern = jqueryTiki.usernamePattern;
    usernamePattern = usernamePattern.substring(2, usernamePattern.length - 2);    // trim /^ and $/

    const match = selectionText.match(new RegExp("(?:^|\\s)@(" + usernamePattern + ")$"));
    if (!match?.[1] || match[1].length < MENTION_MIN_CHARS) {
        return null;
    }
    return match[1];
}

function abortMentionRequest() {
    if (mentionRequest) {
        mentionRequest.abort();
        mentionRequest = null;
    }
}

/**
 * Fetch users matching the @name at the end of the text and cancel any previous lookup
 */
async function fetchMentions(selectionText) {
    const term = getMentionTerm(selectionText);
    abortMentionRequest();
    if (!term) {
        return [];
    }

    const controller = new AbortController();
    mentionRequest = controller;
    try {
        const res = await fetch(`tiki-ajax_services.php?listonly=usersautocomplete&q=${encodeURIComponent(term)}`, {
            headers: {
                Accept: "application/json",
            },
            signal: controller.signal,
        });
        if (!res.ok) {
            return [];
        }
        const data = await res.json();
        return Array.isArray(data) ? data : [];
    } catch (error) {
        if (error?.name !== "AbortError") {
            console.error(error); // eslint-disable-line no-console
        }
        return [];
    } finally {
        if (mentionRequest === controller) {
            mentionRequest = null;
        }
    }
}

function showSuggestionsDropdown(suggestions, cursorPosition) {
    const textarea = document.activeElement;
    const style = window.getComputedStyle(textarea);
    const rect = textarea.getBoundingClientRect();
    const mirror = document.createElement("div");
    const marker = document.createElement("span");
    const textBeforeCursor = textarea.value.substring(0, cursorPosition);

    // Simulate real position of the dropdown by mirroring the textarea selectionStart position
    ["boxSizing", "width", "fontFamily", "fontSize", "fontWeight", "fontStyle", "letterSpacing", "lineHeight", "textTransform", "textIndent", "wordSpacing", "tabSize", "paddingTop", "paddingRight", "paddingBottom", "paddingLeft", "borderTopWidth", "borderRightWidth", "borderBottomWidth", "borderLeftWidth", "whiteSpace", "wordWrap", "overflowWrap"].forEach((property) => {
        mirror.style[property] = style[property];
    });
    mirror.style.position = "absolute";
    mirror.style.visibility = "hidden";
    mirror.style.overflow = "hidden";
    mirror.style.top = (rect.top + window.scrollY) + "px";
    mirror.style.left = (rect.left + window.scrollX) + "px";
    mirror.style.height = rect.height + "px";
    mirror.style.whiteSpace = "pre-wrap";
    mirror.style.wordWrap = "break-word";
    mirror.textContent = textBeforeCursor;
    marker.textContent = "\u200b";
    mirror.appendChild(marker);
    document.body.appendChild(mirror);
    mirror.scrollTop = textarea.scrollTop;
    mirror.scrollLeft = textarea.scrollLeft;
    const markerRect = marker.getBoundingClientRect();
    mirror.remove();

    const dropdown = getSuggestionsDropdown(markerRect);

    const closeDropdown = () => {
        dropdown.remove();
        $(textarea).off("keydown.userMentionsDropdown");
    };
    $(textarea).off("keydown.userMentionsDropdown").on("keydown.userMentionsDropdown", (event) => {
        const items = Array.from(dropdown.querySelectorAll(".dropdown-item"));
        const activeItem = dropdown.querySelector(".dropdown-item.active");
        const activeIndex = activeItem ? items.indexOf(activeItem) : -1;

        if (event.key === "ArrowDown") {
            event.preventDefault();
            activeItem?.classList.remove("active");
            items[(activeIndex + 1) % items.length]?.classList.add("active");
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            activeItem?.classList.remove("active");
            items[(activeIndex - 1 + items.length) % items.length]?.classList.add("active");
        } else if (["Enter", "Tab"].includes(event.key) && activeItem) {
            event.preventDefault();
            activeItem.click();
            closeDropdown();
        } else if (event.key === "Escape") {
            closeDropdown();
        }
    });

    suggestions.forEach(({username, realname, avatar}, index) => {
        const item = getSuggestionDropdownItem(username, realname, avatar, index === 0);
        item.addEventListener("click", (event) => {
            event.preventDefault();

            const mentionStart = textarea.value.lastIndexOf("@", cursorPosition - 1);
            setSelectionRange(textarea, mentionStart, cursorPosition);
            insertAt(textarea.id, "@" + username, false, false, true);

            const newCursorPosition = mentionStart + username.length + 1;

            textarea.focus();
            textarea.setSelectionRange(newCursorPosition, newCursorPosition);
            closeDropdown();

            textarea.dispatchEvent(new Event("change", { bubbles: true }));
        });
        dropdown.appendChild(item);
    });

    document.body.appendChild(dropdown);
}

function getSuggestionsDropdown(markerRect) {
    const dropdownId = "user-mentions-dropdown";
    $("#" + dropdownId).remove();

    const dropdown = document.createElement("div");
    dropdown.id = dropdownId;
    dropdown.className = "dropdown-menu show";
    dropdown.style.cssText = "display:block;position:absolute;z-index:1080;left:" + (markerRect.left + window.scrollX) + "px;top:" + (markerRect.bottom + window.scrollY) + "px;";

    return dropdown;
}

function getSuggestionDropdownItem(username, realname, avatar, active = false) {
    const item = document.createElement("button");
    item.className = "dropdown-item d-flex gap-2 align-items-center" + (active ? " active" : "");
    item.innerHTML = `
        <div class="rounded-circle overflow-hidden" style="max-width: 30px; max-height: 30px;">${avatar}</div> ${username} <span class="text-muted fs-sm fw-lighter">${realname}</span>
    `;

    import('avatar-generator').then(function (mod) {
        if (mod.renderAvatars) {
            mod.renderAvatars();
        }
    });

    return item;
}
