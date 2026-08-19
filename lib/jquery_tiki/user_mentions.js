/**
 * Show a dropdown of users following a @ char if...
 */

$.fn.userMentions = function () {
    const textarea = this;
    $(textarea).on("input", async function (event) {
        const cursorPosition = this.selectionStart;
        const textBeforeCursor = this.value.substring(0, cursorPosition);
        const suggestions = await fetchMentions(textBeforeCursor);
        if (suggestions.length) {
            showSuggestionsDropdown(suggestions, cursorPosition);
        } else {
            $("#user-mentions-dropdown").remove();
        }
    });
};

async function fetchMentions(selectionText) {
    let usernamePattern = jqueryTiki.usernamePattern;
    usernamePattern = usernamePattern.substring(2, usernamePattern.length - 2);    // trim /^ and $/

    const match = selectionText.match(new RegExp("(?:^|\\s)@(" + usernamePattern + ")$"));
    if (match?.[1]) {
        const res = await fetch(`tiki-ajax_services.php?listonly=usersautocomplete&q=${match[1]}`, {
            headers: {
                Accept: "application/json",
            },
        });
        const data = await res.json();
        return data;
    }
    return [];
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
