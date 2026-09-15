import { render, screen, fireEvent } from "@testing-library/vue";
import { describe, expect, test, afterEach } from "vitest";
import EncryptedField from "../../components/EncryptedField/EncryptedField.vue";

function makeField(props = {}) {
    return render(EncryptedField, { props });
}

afterEach(() => {
    document.body.innerHTML = "";
});

describe("EncryptedField — unlocked (default)", () => {
    test("shows Unlocked badge", () => {
        makeField({ keyName: "Payroll Key" });
        expect(screen.getByText("Unlocked")).toBeTruthy();
    });

    test("shows key name in description", () => {
        makeField({ keyName: "Payroll Key" });
        expect(screen.getByText(/Payroll Key/)).toBeTruthy();
    });

    test("shows fallback name when keyName is omitted", () => {
        makeField({});
        expect(screen.getByText(/encrypted/)).toBeTruthy();
    });

    test("does not render an unlock link", () => {
        makeField({ keyName: "Payroll Key" });
        expect(screen.queryByRole("link")).toBeNull();
    });
});

describe("EncryptedField — locked", () => {
    test("shows Encrypted badge", () => {
        makeField({ locked: true, keyName: "HR Key" });
        expect(screen.getByText("Encrypted")).toBeTruthy();
    });

    test("shows unlock link", () => {
        makeField({ locked: true, keyName: "HR Key" });
        expect(screen.getByRole("link", { name: /key/i })).toBeTruthy();
    });

    test("click on unlock link removes hidden from matching modal", () => {
        // Set up a fake host element (custom element) keyed by field + item
        const host = document.createElement("tiki-encrypted-field");
        host.dataset.fieldId = "5";
        host.dataset.itemId = "3";
        document.body.appendChild(host);

        // Set up a matching modal (matched on both field and item ids)
        const modal = document.createElement("tiki-enter-key-modal");
        modal.setAttribute("data-field-id", "5");
        modal.setAttribute("data-item-id", "3");
        modal.setAttribute("hidden", "");
        document.body.appendChild(modal);

        // Render inside the host so closest('tiki-encrypted-field') works
        const div = document.createElement("div");
        host.appendChild(div);
        makeField({ locked: true, keyName: "HR Key" });

        const link = screen.getByRole("link", { name: /key/i });

        // Move rendered content inside the fake host for closest() to work
        host.appendChild(link.closest("span") ?? link);

        fireEvent.click(link);

        expect(modal.hasAttribute("hidden")).toBe(false);
    });

    test("opens only this row's modal when the same field appears for multiple items", () => {
        // Same fieldId 5, two items — each row has its own modal instance.
        const host = document.createElement("tiki-encrypted-field");
        host.dataset.fieldId = "5";
        host.dataset.itemId = "22";
        document.body.appendChild(host);

        const modalA = document.createElement("tiki-enter-key-modal");
        modalA.setAttribute("data-field-id", "5");
        modalA.setAttribute("data-item-id", "11");
        modalA.setAttribute("hidden", "");
        document.body.appendChild(modalA);

        const modalB = document.createElement("tiki-enter-key-modal");
        modalB.setAttribute("data-field-id", "5");
        modalB.setAttribute("data-item-id", "22");
        modalB.setAttribute("hidden", "");
        document.body.appendChild(modalB);

        makeField({ locked: true, keyName: "HR Key" });
        const link = screen.getByRole("link", { name: /key/i });
        host.appendChild(link.closest("span") ?? link);

        fireEvent.click(link);

        // Only row 22's modal opens; row 11's stays hidden.
        expect(modalB.hasAttribute("hidden")).toBe(false);
        expect(modalA.hasAttribute("hidden")).toBe(true);
    });

    test("click does nothing when no matching modal exists", () => {
        const host = document.createElement("tiki-encrypted-field");
        host.dataset.fieldId = "99";
        document.body.appendChild(host);

        makeField({ locked: true, keyName: "HR Key" });
        const link = screen.getByRole("link", { name: /key/i });
        host.appendChild(link.closest("span") ?? link);

        // Should not throw
        expect(() => fireEvent.click(link)).not.toThrow();
    });
});

describe("EncryptedField — forbidden", () => {
    test("shows No Access badge", () => {
        makeField({ forbidden: true });
        expect(screen.getByText("No Access")).toBeTruthy();
    });

    test("shows error description", () => {
        makeField({ forbidden: true });
        expect(screen.getByText(/no longer exists/i)).toBeTruthy();
    });

    test("does not render an unlock link", () => {
        makeField({ forbidden: true });
        expect(screen.queryByRole("link")).toBeNull();
    });
});

describe("EncryptedField — prop precedence", () => {
    test("forbidden takes precedence over locked when both are set", () => {
        makeField({ forbidden: true, locked: true });
        expect(screen.getByText("No Access")).toBeTruthy();
        expect(screen.queryByText("Encrypted")).toBeNull();
    });
});

describe("EncryptedField — string attribute coercion (light-DOM custom element)", () => {
    test('locked="false" renders as unlocked', () => {
        makeField({ locked: "false", keyName: "HR Key" });
        expect(screen.getByText("Unlocked")).toBeTruthy();
        expect(screen.queryByText("Encrypted")).toBeNull();
    });

    test('locked="0" renders as unlocked', () => {
        makeField({ locked: "0", keyName: "HR Key" });
        expect(screen.getByText("Unlocked")).toBeTruthy();
    });

    test('forbidden="false" does not render No Access', () => {
        makeField({ forbidden: "false", locked: true });
        expect(screen.queryByText("No Access")).toBeNull();
        expect(screen.getByText("Encrypted")).toBeTruthy();
    });

    test('attribute-name string value still renders locked (locked="locked")', () => {
        makeField({ locked: "locked" });
        expect(screen.getByText("Encrypted")).toBeTruthy();
    });
});
