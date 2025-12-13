import { render, screen } from "@testing-library/vue";
import { describe, expect, test, vi, afterEach } from "vitest";
import { h } from "vue";
import Select from "../../components/Select/Select.vue";
import { ElOption, ElSelect } from "element-plus";
import ConfigWrapper from "../../components/ConfigWrapper.vue";

vi.mock("element-plus", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        ElOption: vi.fn((props) => h("div", props)),
        ElSelect: vi.fn((props, { slots }) => h("div", props, slots.default ? slots.default() : null)),
    };
});

vi.mock("../../components/ConfigWrapper.vue", () => {
    return {
        default: vi.fn((props, { slots }) => h("div", { ...props, "data-testid": "config-wrapper" }, slots.default ? slots.default() : null)),
    };
});

describe("Select - Refactorization Tests", () => {
    const consoleErrorSpy = vi.spyOn(console, "error");
    const consoleWarnSpy = vi.spyOn(console, "warn");

    afterEach(() => {
        vi.clearAllMocks();
        vi.resetModules();
    });

    test("handles invalid JSON in options prop gracefully", () => {
        const givenProps = {
            options: "invalid-json",
            placeholder: "Select",
            value: JSON.stringify("foo"),
        };

        const { container } = render(Select, { props: givenProps });
        expect(container).toBeTruthy();
    });

    test("handles invalid JSON in ordering prop gracefully", () => {
        const givenProps = {
            options: JSON.stringify([
                { value: "foo", label: "Foo" },
                { value: "bar", label: "Bar" },
            ]),
            placeholder: "Select",
            value: JSON.stringify("foo"),
            ordering: "invalid-json",
            multiple: "true",
        };

        expect(() => {
            render(Select, { props: givenProps });
        }).not.toThrow();

        expect(consoleWarnSpy).toHaveBeenCalledWith("Invalid ordering configuration:", expect.any(Error));
    });

    test("handles fetch error in remote method gracefully", async () => {
        const givenProps = {
            options: JSON.stringify([]),
            placeholder: "Select",
            value: JSON.stringify(""),
            remoteSourceUrl: "http://foo.bar",
        };

        const fetchSpy = vi.spyOn(window, "fetch").mockRejectedValue(new Error("Network error"));

        render(Select, { props: givenProps });

        const lastCall = ElSelect.mock.calls[ElSelect.mock.calls.length - 1];
        const props = lastCall[0];
        const remoteMethod = props["remote-method"];

        if (remoteMethod) {
            await remoteMethod("test-query");
        }

        expect(fetchSpy).toHaveBeenCalled();

        expect(consoleErrorSpy).toHaveBeenCalledWith("Error loading remote options:", expect.any(Error));
    });

    test("handles HTTP error response in remote method", async () => {
        const givenProps = {
            options: JSON.stringify([]),
            placeholder: "Select",
            value: JSON.stringify(""),
            remoteSourceUrl: "http://foo.bar",
        };

        const fetchSpy = vi.spyOn(window, "fetch").mockResolvedValue({
            ok: false,
            status: 404,
            json: () => Promise.resolve([]),
        });

        render(Select, { props: givenProps });

        const lastCall = ElSelect.mock.calls[ElSelect.mock.calls.length - 1];
        const props = lastCall[0];
        const remoteMethod = props["remote-method"];

        if (remoteMethod) {
            await remoteMethod("test-query");
        }

        expect(fetchSpy).toHaveBeenCalled();
        expect(consoleErrorSpy).toHaveBeenCalledWith("Error loading remote options:", expect.any(Error));
    });

    test("parseInt with radix works correctly", () => {
        const givenProps = {
            options: JSON.stringify([
                { value: "foo", label: "Foo" },
                { value: "bar", label: "Bar" },
            ]),
            placeholder: "Select",
            value: JSON.stringify("foo"),
            max: "10",
            maxCollapseTags: "5",
            multiple: "true",
        };

        render(Select, { props: givenProps });

        const lastCall = ElSelect.mock.calls[ElSelect.mock.calls.length - 1];
        const props = lastCall[0];

        expect(props["multiple-limit"]).toBe(10);
        expect(props["max-collapse-tags"]).toBe(5);
    });
});
