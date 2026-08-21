import { render, screen, fireEvent, waitFor } from "@testing-library/vue";
import { describe, expect, test, vi, beforeEach, afterEach } from "vitest";
import EnterKeyModal from "../../components/EnterKeyModal/EnterKeyModal.vue";

const BASE_PROPS = {
    keyName: "Test Key",
    encryptionKeyId: 42,
    fieldId: 7,
    itemId: 0,
};

function makeModal(props = {}) {
    // The component queries the DOM for its host element by data-field-id +
    // data-item-id (fields repeat once per item in list views, so both are
    // needed to reach the right instance). Provide a matching fake host.
    const fieldId = String(props.fieldId ?? BASE_PROPS.fieldId);
    const itemId = String(props.itemId ?? BASE_PROPS.itemId);
    const host = document.createElement("tiki-enter-key-modal");
    host.setAttribute("data-field-id", fieldId);
    host.setAttribute("data-item-id", itemId);
    host.setAttribute("hidden", "");
    document.body.appendChild(host);

    const result = render(EnterKeyModal, { props: { ...BASE_PROPS, ...props } });
    return { ...result, host };
}

// Build the encrypted-field host the modal dispatches its unlock event on,
// keyed by both ids so applyUnlock's [data-field-id][data-item-id] lookup matches.
function makeFieldHost(itemId = BASE_PROPS.itemId, fieldId = BASE_PROPS.fieldId) {
    const fieldHost = document.createElement("tiki-encrypted-field");
    fieldHost.setAttribute("data-field-id", String(fieldId));
    fieldHost.setAttribute("data-item-id", String(itemId));
    document.body.appendChild(fieldHost);
    return fieldHost;
}

function showModal(host) {
    // Simulate external removal of `hidden` (what EncryptedField.vue does).
    host.removeAttribute("hidden");
}

describe("EnterKeyModal", () => {
    let fetchSpy;

    beforeEach(() => {
        fetchSpy = vi.spyOn(global, "fetch");
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.innerHTML = "";
    });

    test("is not visible before hidden attribute is removed", () => {
        makeModal();
        expect(screen.queryByRole("dialog")).toBeNull();
    });

    test("becomes visible when hidden attribute is removed from host", async () => {
        const { host } = makeModal();

        showModal(host);
        await waitFor(() => expect(screen.getByRole("dialog")).toBeTruthy());
    });

    test("does not fetch the stored share when the modal opens", async () => {
        const { host } = makeModal();

        showModal(host);
        await waitFor(() => expect(screen.getByRole("dialog")).toBeTruthy());

        // The secret share must never be fetched without an explicit user action.
        expect(fetchSpy).not.toHaveBeenCalled();
    });

    test("fills share input when 'Use my stored share' is clicked and server returns a share", async () => {
        const { host } = makeModal();

        showModal(host);
        await waitFor(() => expect(screen.getByRole("dialog")).toBeTruthy());

        fetchSpy.mockResolvedValueOnce({
            ok: true,
            json: async () => "abc-share-value",
        });

        await fireEvent.click(screen.getByText(/Use my stored share/i));

        await waitFor(() => expect(screen.getByLabelText(/Enter shared secret/i).value).toBe("abc-share-value"));
        expect(screen.getByText(/Share retrieved from your account/i)).toBeTruthy();
    });

    test("shows an error when no stored share is available for the account", async () => {
        const { host } = makeModal();

        showModal(host);
        await waitFor(() => expect(screen.getByRole("dialog")).toBeTruthy());

        fetchSpy.mockResolvedValueOnce({ ok: false });

        await fireEvent.click(screen.getByText(/Use my stored share/i));

        await waitFor(() => expect(screen.getByText(/No stored share found/i)).toBeTruthy());
    });

    test("shows error state when enter_key returns non-OK response", async () => {
        const { host } = makeModal({ itemId: 99 });

        showModal(host);
        await waitFor(() => screen.getByRole("dialog"));

        // Type a key and submit
        const input = screen.getByLabelText(/Enter shared secret/i);
        await fireEvent.update(input, "wrong-key");

        // enter_key responds 409
        fetchSpy.mockResolvedValueOnce({
            ok: false,
            json: async () => ({ errors: [{ message: "Incorrect share." }] }),
        });

        await fireEvent.click(screen.getByText("Submit"));
        await waitFor(() => expect(screen.getByText("Incorrect share.")).toBeTruthy());
    });

    test("dispatches tiki:unlocked on the field host and closes modal on successful unlock (new item, no decryption)", async () => {
        const { host } = makeModal({ itemId: 0 });

        // The unlock event is dispatched on the encrypted-field host element, not document
        const fieldHost = makeFieldHost(0);

        showModal(host);
        await waitFor(() => screen.getByRole("dialog"));

        const input = screen.getByLabelText(/Enter shared secret/i);
        await fireEvent.update(input, "valid-share");

        fetchSpy.mockResolvedValueOnce({ ok: true }); // enter_key success

        const events = [];
        const documentEvents = [];
        fieldHost.addEventListener("tiki:unlocked", (e) => events.push(e.detail));
        document.addEventListener("tiki:unlocked", (e) => documentEvents.push(e.detail));

        await fireEvent.click(screen.getByText("Submit"));

        await waitFor(() => expect(events.length).toBe(1));
        expect(events[0]).toMatchObject({ fieldId: BASE_PROPS.fieldId });
        // A document-level notification is also sent so field-type JS can react
        // (see the value-free assertion below), but it must never carry cleartext.
        expect(documentEvents.length).toBe(1);
        expect(documentEvents[0]).not.toHaveProperty("value");
    });

    test("notifies document listeners without the cleartext value", async () => {
        // The Secret (SEC) field's show/hide toggle is rendered disabled while the
        // field is locked and is only re-enabled by a document-level tiki:unlocked
        // listener, so the notification has to reach document. It must stay
        // value-free: the host-scoped event is the only carrier of cleartext.
        const { host } = makeModal({ itemId: 99 });
        const fieldHost = makeFieldHost(99);

        showModal(host);
        await waitFor(() => screen.getByRole("dialog"));

        const input = screen.getByLabelText(/Enter shared secret/i);
        await fireEvent.update(input, "valid-share");

        fetchSpy
            .mockResolvedValueOnce({ ok: true }) // enter_key
            .mockResolvedValueOnce({ ok: true, json: async () => ({ value: "decrypted!" }) }); // get_decrypted_value

        const hostEvents = [];
        const documentEvents = [];
        fieldHost.addEventListener("tiki:unlocked", (e) => hostEvents.push(e.detail));
        document.addEventListener("tiki:unlocked", (e) => documentEvents.push(e.detail));

        await fireEvent.click(screen.getByText("Submit"));

        await waitFor(() => expect(documentEvents.length).toBe(1));
        expect(documentEvents[0]).toEqual({ fieldId: BASE_PROPS.fieldId, itemId: 99 });
        expect(documentEvents[0]).not.toHaveProperty("value");
        // The host event still carries the decrypted value for field-scoped listeners
        expect(hostEvents[0]).toMatchObject({ value: "decrypted!" });
    });

    test("dispatches tiki:unlocked with decrypted value on successful unlock (existing item)", async () => {
        const { host } = makeModal({ itemId: 99 });

        const fieldHost = makeFieldHost(99);

        showModal(host);
        await waitFor(() => screen.getByRole("dialog"));

        const input = screen.getByLabelText(/Enter shared secret/i);
        await fireEvent.update(input, "valid-share");

        fetchSpy
            .mockResolvedValueOnce({ ok: true }) // enter_key
            .mockResolvedValueOnce({ ok: true, json: async () => ({ value: "decrypted!" }) }); // get_decrypted_value

        const events = [];
        fieldHost.addEventListener("tiki:unlocked", (e) => events.push(e.detail));

        await fireEvent.click(screen.getByText("Submit"));

        await waitFor(() => expect(events.length).toBe(1));
        expect(events[0]).toMatchObject({ fieldId: BASE_PROPS.fieldId, value: "decrypted!" });
    });

    test("unlocks on an empty decrypted value (empty string is valid, not a failure)", async () => {
        const { host } = makeModal({ itemId: 99 });
        const fieldHost = makeFieldHost(99);

        showModal(host);
        await waitFor(() => screen.getByRole("dialog"));

        const input = screen.getByLabelText(/Enter shared secret/i);
        await fireEvent.update(input, "valid-share");

        fetchSpy
            .mockResolvedValueOnce({ ok: true }) // enter_key
            .mockResolvedValueOnce({ ok: true, json: async () => ({ value: "" }) }); // get_decrypted_value: legitimately empty

        const events = [];
        fieldHost.addEventListener("tiki:unlocked", (e) => events.push(e.detail));

        await fireEvent.click(screen.getByText("Submit"));

        // Must unlock (dispatch the event and close), not report "Decryption failed."
        await waitFor(() => expect(events.length).toBe(1));
        expect(events[0]).toMatchObject({ fieldId: BASE_PROPS.fieldId, value: "" });
        expect(screen.queryByText(/Decryption failed/i)).toBeNull();
        await waitFor(() => expect(screen.queryByRole("dialog")).toBeNull());
    });

    test("a null decrypted value is still treated as a failure", async () => {
        const { host } = makeModal({ itemId: 99 });
        makeFieldHost(99);

        showModal(host);
        await waitFor(() => screen.getByRole("dialog"));

        const input = screen.getByLabelText(/Enter shared secret/i);
        await fireEvent.update(input, "valid-share");

        fetchSpy
            .mockResolvedValueOnce({ ok: true }) // enter_key
            .mockResolvedValueOnce({ ok: true, json: async () => ({ value: null, error: "Decryption failed: key may be incorrect." }) });

        await fireEvent.click(screen.getByText("Submit"));

        await waitFor(() => expect(screen.getByText(/Decryption failed/i)).toBeTruthy());
    });

    test("opens only this row's modal when the same field renders for multiple items", async () => {
        // Two rows: same fieldId, different itemId. Each has its own modal instance.
        const { host: hostA } = makeModal({ itemId: 11 });
        const { host: hostB } = makeModal({ itemId: 22 });

        // Simulate EncryptedField click delegation targeting row 22 only.
        hostB.removeAttribute("hidden");

        // Row 22's dialog is shown; row 11's stays hidden.
        await waitFor(() => expect(screen.getAllByRole("dialog").length).toBe(1));
        expect(hostA.hasAttribute("hidden")).toBe(true);
        expect(hostB.hasAttribute("hidden")).toBe(false);
    });

    test("Escape key closes the modal", async () => {
        const { host } = makeModal();

        showModal(host);
        await waitFor(() => screen.getByRole("dialog"));

        const dialog = screen.getByRole("dialog");
        await fireEvent.keyDown(dialog, { key: "Escape" });

        await waitFor(() => expect(screen.queryByRole("dialog")).toBeNull());
    });

    test("dialog has required ARIA attributes", async () => {
        const { host } = makeModal();

        showModal(host);
        await waitFor(() => screen.getByRole("dialog"));

        const dialog = screen.getByRole("dialog");
        expect(dialog.getAttribute("aria-modal")).toBe("true");
        expect(dialog.getAttribute("aria-labelledby")).toBeTruthy();
        const titleId = dialog.getAttribute("aria-labelledby");
        expect(document.getElementById(titleId)).toBeTruthy();
    });
});
