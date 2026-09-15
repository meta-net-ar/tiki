/**
 * Cross-component regression guard.
 *
 * A Secret (SEC) field bound to an encryption key renders its input and its
 * show/hide toggle disabled while the key is absent from the session. Nothing in
 * `secret.js` re-enables the toggle except the `document`-level `tiki:unlocked`
 * listener, and the only producer of that event is `EnterKeyModal`. The modal
 * keeps the decrypted value on a field-scoped, non-bubbling event, so it sends a
 * separate value-free notification on `document` for exactly this case.
 *
 * This test wires the real modal to the real `secret.js` over the markup
 * `templates/trackerinput/secret.tpl` emits, so the contract cannot drift on
 * either side without failing here.
 */
import { render, fireEvent, screen, waitFor } from "@testing-library/vue";
import { describe, expect, test, vi, beforeAll, beforeEach, afterEach } from "vitest";
import jQuery from "jquery";
import EnterKeyModal from "../../../vue-widgets/element-plus-ui/src/components/EnterKeyModal/EnterKeyModal.vue";

const FIELD_ID = 288;
const ITEM_ID = 42;

// secret.js reads `$` as a page global (the way Tiki exposes jQuery) and calls two
// Tiki jQuery plugins on its ajax paths, so both are in place before it is loaded.
// The import is dynamic and deferred because a static one would hoist above this.
beforeAll(async () => {
    globalThis.$ = globalThis.jQuery = jQuery;
    jQuery.fn.clearError =
        jQuery.fn.clearError ||
        function () {
            return this;
        };
    jQuery.fn.showError =
        jQuery.fn.showError ||
        function () {
            return this;
        };
    // Imported for its side effects: the module registers its document listeners once.
    await import("../secret.js");
});

// The locked edit-mode markup of templates/trackerinput/secret.tpl, nested in the
// `trackerinput_<fieldId>` wrapper that TrackerInput.php emits around it.
function renderLockedSecretField() {
    const wrapper = document.createElement("div");
    wrapper.id = `trackerinput_${FIELD_ID}`;
    wrapper.innerHTML = `
        <div class="js-secret-field" data-field-id="${FIELD_ID}" data-item-id="${ITEM_ID}">
            <div class="input-group js-secret-input-group" data-field-id="${FIELD_ID}" data-item-id="${ITEM_ID}">
                <input type="password" name="ins_${FIELD_ID}" class="form-control js-secret-input"
                       disabled data-has-stored="1" data-is-encrypted="1" placeholder="●●●●●●">
                <button type="button" class="btn js-secret-toggle" aria-pressed="false" disabled>
                    <i class="fa fa-eye"></i>
                </button>
            </div>
        </div>`;
    document.body.appendChild(wrapper);

    const fieldHost = document.createElement("tiki-encrypted-field");
    fieldHost.setAttribute("data-field-id", String(FIELD_ID));
    fieldHost.setAttribute("data-item-id", String(ITEM_ID));
    fieldHost.setAttribute("locked", "");
    document.body.appendChild(fieldHost);

    const modalHost = document.createElement("tiki-enter-key-modal");
    modalHost.setAttribute("data-field-id", String(FIELD_ID));
    modalHost.setAttribute("data-item-id", String(ITEM_ID));
    modalHost.setAttribute("hidden", "");
    document.body.appendChild(modalHost);

    return {
        modalHost,
        fieldHost,
        input: wrapper.querySelector(".js-secret-input"),
        toggle: wrapper.querySelector(".js-secret-toggle"),
    };
}

describe("Secret field unlock through EnterKeyModal", () => {
    let fetchSpy;

    beforeEach(() => {
        fetchSpy = vi.spyOn(global, "fetch");
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.innerHTML = "";
    });

    test("re-enables the show/hide toggle once the key is entered", async () => {
        const dom = renderLockedSecretField();
        render(EnterKeyModal, {
            props: { keyName: "Test Key", encryptionKeyId: 42, fieldId: FIELD_ID, itemId: ITEM_ID },
        });

        expect(dom.toggle.disabled).toBe(true);
        expect(dom.input.disabled).toBe(true);

        dom.modalHost.removeAttribute("hidden");
        await waitFor(() => screen.getByRole("dialog"));

        await fireEvent.update(screen.getByLabelText(/Enter shared secret/i), "valid-share");
        fetchSpy
            .mockResolvedValueOnce({ ok: true }) // enter_key
            .mockResolvedValueOnce({ ok: true, json: async () => ({ value: "s3cr3t" }) }); // get_decrypted_value

        await fireEvent.click(screen.getByText("Submit"));

        await waitFor(() => expect(dom.toggle.disabled).toBe(false));
        // The modal, not the document event, is what puts the value back in the input.
        expect(dom.input.disabled).toBe(false);
        expect(dom.input.value).toBe("s3cr3t");
        // Masked again after unlock, so the value is not on screen until the user reveals it.
        expect(dom.input.type).toBe("password");
        expect(dom.fieldHost.hasAttribute("locked")).toBe(false);
    });

    test("ignores an unlock aimed at a different field", async () => {
        const dom = renderLockedSecretField();

        document.dispatchEvent(new CustomEvent("tiki:unlocked", { detail: { fieldId: FIELD_ID + 1, itemId: ITEM_ID } }));

        expect(dom.toggle.disabled).toBe(true);
    });
});
