import { render, screen, waitFor, within } from "@testing-library/vue";
import fs from "fs";
import path from "path";
import { describe, test, expect, vi, beforeAll, afterAll } from "vitest";
import { ElConfigProvider } from "element-plus";
import { h } from "vue";
import ConfigWrapper from "../../components/ConfigWrapper.vue";

vi.mock("element-plus", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        ElConfigProvider: vi.fn((props, { slots }) => {
            return h("div", { "data-testid": "config-provider" }, slots.default ? slots.default() : null);
        }),
    };
});

describe("App", () => {
    const consoleErrorSpy = vi.spyOn(console, "error");
    const consoleWarnSpy = vi.spyOn(console, "warn");

    const localePath = path.resolve(__dirname, "../../../../../../../public/generated/js/vendor_dist/element-plus/dist/locale/en.min.mjs");

    beforeAll(() => {
        fs.mkdirSync(path.dirname(localePath), { recursive: true });
        fs.writeFileSync(localePath, "export default { name: 'locale' }");
    });

    afterAll(() => {
        fs.unlinkSync(localePath);
    });

    test("renders correctly, loads and applies the correct locale", async () => {
        // given the locale file has been generated

        render(ConfigWrapper, {
            props: {
                language: "en",
            },
            slots: {
                default: "<div>Slot component</div>",
            },
        });

        const configProvider = screen.getByTestId("config-provider");
        expect(configProvider).to.exist;
        expect(within(configProvider).getByText("Slot component")).to.exist;

        await waitFor(() =>
            expect(ElConfigProvider).toHaveBeenCalledWith(
                expect.objectContaining({
                    locale: { name: "locale" },
                }),
                expect.anything()
            )
        );

        expect(consoleErrorSpy).not.toHaveBeenCalled();
        expect(consoleWarnSpy).not.toHaveBeenCalled();
    });

    test("logs an error if locale fails to load", async () => {
        render(ConfigWrapper, {
            props: {
                language: "unknown",
            },
        });

        await waitFor(() => expect(consoleErrorSpy).toHaveBeenCalled());
    });
});
