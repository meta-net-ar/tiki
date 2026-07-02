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
        // Set up a fake host element (custom element) with data-field-id
        const host = document.createElement("tiki-encrypted-field");
        host.dataset.fieldId = "5";
        document.body.appendChild(host);

        // Set up a matching modal
        const modal = document.createElement("tiki-enter-key-modal");
        modal.setAttribute("data-field-id", "5");
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
