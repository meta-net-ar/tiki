/**
 * Behaviour for the Secret (SEC) tracker field — edit input and read-only output.
 *
 * Wired through event delegation on `document` so the field also works when its
 * markup is injected by ajax: inline edit (`ajax_inline_edit`), Bootstrap modals
 * and item preview. The previous implementation lived in template `{jq}` blocks,
 * which are registered to headerlib and emitted at page bottom — they never run
 * inside an ajax fragment, so the field was inert in the inline editor.
 *
 * The plaintext value is never inlined into the page for saved items: both the
 * edit toggle and the output reveal fetch it on demand from the SEC-only
 * endpoint. The only exception is an unsaved item rendered outside item preview,
 * which has no stored row to fetch and therefore carries the value in
 * `data-value`. In item preview the reveal button is disabled entirely and no
 * value is embedded.
 */
(function () {
    "use strict";

    // Per-field state keyed by the container node, so several Secret fields on
    // one page stay independent without relying on generated element IDs.
    var inputStates = new WeakMap();
    var outputStates = new WeakMap();

    function setEye(toggle, icon) {
        var i = toggle.querySelector("i");
        if (i) {
            i.className = "fa " + icon;
        }
    }

    // ---- edit input ---------------------------------------------------------

    function getInputState(group) {
        var state = inputStates.get(group);
        if (state) {
            return state;
        }
        var input = group.querySelector(".js-secret-input");
        var hasStored = input.dataset.hasStored === "1";
        var isEncrypted = input.dataset.isEncrypted === "1";
        var itemId = parseInt(group.dataset.itemId, 10);
        state = {
            input: input,
            // Encrypted fields skip AJAX — EnterKeyModal populates the input via
            // the tiki:unlocked event. States: unloaded | loading |
            // loaded-visible | loaded-hidden.
            phase: hasStored && itemId && !isEncrypted ? "unloaded" : "loaded-hidden",
            placeholder: input.getAttribute("placeholder") || "",
        };
        inputStates.set(group, state);
        return state;
    }

    function reveal(state, toggle) {
        state.input.type = "text";
        state.input.removeAttribute("placeholder");
        toggle.setAttribute("aria-pressed", "true");
        setEye(toggle, "fa-eye-slash");
    }

    function mask(state, toggle) {
        state.input.type = "password";
        toggle.setAttribute("aria-pressed", "false");
        setEye(toggle, "fa-eye");
    }

    $(document).on("click", ".js-secret-toggle", function (e) {
        // Reveal/mask must not bubble up to the ajax_inline_edit cell handler
        // (delegated on document for '.editable-inline'), which would open the
        // inline-edit popover on top of the toggle.
        e.stopPropagation();
        var toggle = this;
        var group = toggle.closest(".js-secret-input-group");
        if (!group) {
            return;
        }
        var state = getInputState(group);
        var input = state.input;

        if (state.phase === "unloaded") {
            state.phase = "loading";
            toggle.disabled = true;
            setEye(toggle, "fa-hourglass-half");

            $.ajax({
                type: "POST",
                url: $.service("tracker", "get_field_value"),
                dataType: "json",
                data: {
                    itemId: parseInt(group.dataset.itemId, 10),
                    fieldId: parseInt(group.dataset.fieldId, 10),
                },
                success: function (data) {
                    toggle.disabled = false;
                    var loaded = data.value !== null && data.value !== undefined ? data.value : "";
                    input.value = loaded;
                    $(toggle).closest("form").clearError();
                    if (loaded !== "") {
                        reveal(state, toggle);
                        state.phase = "loaded-visible";
                    } else {
                        mask(state, toggle);
                        state.phase = "loaded-hidden";
                    }
                },
                error: function (jqxhr) {
                    toggle.disabled = false;
                    state.phase = "unloaded";
                    setEye(toggle, "fa-eye");
                    $(toggle).closest("form").showError(jqxhr);
                },
            });
        } else if (state.phase === "loaded-visible") {
            mask(state, toggle);
            state.phase = "loaded-hidden";
        } else if (state.phase === "loaded-hidden") {
            reveal(state, toggle);
            state.phase = "loaded-visible";
        }
        // 'loading': the button is disabled, the click cannot fire.
    });

    // Clear-stored-value checkbox: empty and disable the input so the submitted
    // value cannot compete with the clear intent, and drop the masked
    // placeholder so the field visibly reads as empty. The '_clear' field carries
    // the intent server-side; handleSave() wipes this item only. Restore on uncheck.
    $(document).on("change", ".js-secret-clear", function () {
        var field = this.closest(".js-secret-field");
        if (!field) {
            return;
        }
        var group = field.querySelector(".js-secret-input-group");
        var toggle = field.querySelector(".js-secret-toggle");
        var state = getInputState(group);

        if (this.checked) {
            state.input.value = "";
            state.input.disabled = true;
            state.input.removeAttribute("placeholder");
            if (toggle) {
                toggle.disabled = true;
            }
            mask(state, toggle);
        } else {
            state.input.disabled = false;
            if (state.placeholder) {
                state.input.setAttribute("placeholder", state.placeholder);
            }
            if (toggle && state.phase !== "loading") {
                toggle.disabled = false;
            }
        }
    });

    // Re-enable the toggle after EnterKeyModal (SSS) or legacy Bootstrap-modal
    // unlock. Both paths dispatch 'tiki:unlocked' on document; filter by fieldId.
    // EnterKeyModal already populates input.value; the legacy path passes it in detail.
    document.addEventListener("tiki:unlocked", function (e) {
        if (!e.detail) {
            return;
        }
        var fields = document.querySelectorAll(".js-secret-field");
        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            if (String(field.dataset.fieldId) !== String(e.detail.fieldId)) {
                continue;
            }
            var group = field.querySelector(".js-secret-input-group");
            var toggle = field.querySelector(".js-secret-toggle");
            var state = getInputState(group);
            if (e.detail.value) {
                state.input.value = e.detail.value;
            }
            if (toggle) {
                toggle.removeAttribute("disabled");
            }
            mask(state, toggle);
            state.phase = "loaded-hidden";
        }
    });

    // ---- read-only output ---------------------------------------------------

    function getOutputState(wrap) {
        var state = outputStates.get(wrap);
        if (state) {
            return state;
        }
        // A missing itemId (preview of an unsaved item) cannot be fetched on
        // demand, so the value is embedded in data-value for client-side reveal.
        // Saved items keep the secure on-demand fetch (loaded stays null).
        var hasValue = wrap.hasAttribute("data-value");
        state = {
            text: wrap.querySelector(".js-secret-output-text"),
            err: wrap.querySelector(".js-secret-output-err"),
            loaded: hasValue ? wrap.getAttribute("data-value") : null,
            visible: false,
        };
        outputStates.set(wrap, state);
        return state;
    }

    $(document).on("click", ".js-secret-output-toggle", function (e) {
        // Same guard as the edit toggle: in tracker lists with ajax_inline_edit
        // enabled the output sits inside an '.editable-inline' cell whose
        // document-delegated click handler opens the edit popover. The eye click
        // matches deeper in the propagation path, so stopping it here keeps the
        // reveal action from also popping the editor.
        e.stopPropagation();
        var toggle = this;
        var wrap = toggle.closest(".js-secret-output");
        if (!wrap) {
            return;
        }
        var state = getOutputState(wrap);

        if (state.loaded !== null) {
            state.visible = !state.visible;
            var showing = state.visible && state.loaded !== "";
            state.text.textContent = showing ? state.loaded : "●●●●●●";
            toggle.setAttribute("aria-pressed", showing ? "true" : "false");
            setEye(toggle, showing ? "fa-eye-slash" : "fa-eye");
            return;
        }

        toggle.disabled = true;
        setEye(toggle, "fa-hourglass-half");

        $.ajax({
            type: "POST",
            url: $.service("tracker", "get_field_value"),
            dataType: "json",
            data: {
                itemId: parseInt(wrap.dataset.itemId, 10),
                fieldId: parseInt(wrap.dataset.fieldId, 10),
            },
            success: function (data) {
                toggle.disabled = false;
                state.loaded = data.value !== null && data.value !== undefined ? String(data.value) : "";
                state.visible = state.loaded !== "";
                state.text.textContent = state.loaded || "●●●●●●";
                toggle.setAttribute("aria-pressed", state.visible ? "true" : "false");
                setEye(toggle, state.visible ? "fa-eye-slash" : "fa-eye");
                if (state.err) {
                    state.err.style.display = "none";
                }
            },
            error: function () {
                toggle.disabled = false;
                state.loaded = null;
                setEye(toggle, "fa-eye");
                if (state.err) {
                    state.err.style.display = "";
                }
            },
        });
    });
})();
