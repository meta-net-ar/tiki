import { fireEvent, render, screen, within } from "@testing-library/vue";
import { afterEach, describe, expect, test, vi } from "vitest";
import { h } from "vue";
import Autocomplete, { DATA_TEST_ID, TEXT } from "../../components/Autocomplete/Autocomplete.vue";
import { ElAutocomplete } from "element-plus";
import { fetchSuggestions } from "../../helpers/autocomplete/remote";
import ConfigWrapper from "../../components/ConfigWrapper.vue";

vi.mock("element-plus", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        ElAutocomplete: vi.fn((props, { slots }) => h("div", props, slots.default ? slots.default({ item: { value: "Suggestion" } }) : null)),
    };
});

vi.mock("../../components/ConfigWrapper.vue", () => {
    return {
        default: vi.fn((props, { slots }) => h("div", { ...props, "data-testid": "config-wrapper" }, slots.default ? slots.default() : null)),
    };
});

vi.mock("../../helpers/autocomplete/remote", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        fetchSuggestions: vi.fn(),
    };
});

describe("Autocomplete", () => {
    const consoleErrorSpy = vi.spyOn(console, "error");
    const consoleWarnSpy = vi.spyOn(console, "warn");

    afterEach(() => {
        vi.clearAllMocks();
    });

    describe("render tests", () => {
        test("renders correctly with default props value", () => {
            const givenProps = {
                remoteSourceUrl: "https://foo/bar",
                _expose: vi.fn(),
            };

            render(Autocomplete, { props: givenProps });

            const configWrapper = screen.getByTestId("config-wrapper");
            expect(configWrapper).to.exist;
            const autocompleteElement = within(configWrapper).getByTestId(DATA_TEST_ID.AUTOCOMPLETE_ELEMENT);

            expect(autocompleteElement).to.exist;

            expect(ElAutocomplete).toHaveBeenCalledWith(
                expect.objectContaining({
                    placeholder: TEXT.INPUT_PLACEHOLDER,
                    "value-key": "value",
                }),
                expect.any(Object)
            );

            expect(consoleErrorSpy).not.toHaveBeenCalled();
            expect(consoleWarnSpy).not.toHaveBeenCalled();
            expect(givenProps._expose).toHaveBeenCalled({ value: expect.objectContaining({ _value: givenProps.value, __v_isRef: true }) });
        });

        test("logs an error to the console when required props are not provided", () => {
            render(Autocomplete, { props: { _expose: vi.fn() } });

            expect(consoleErrorSpy).toHaveBeenCalledWith(TEXT.ERROR_NO_REQUIRED_PROPS);
            expect(consoleWarnSpy).not.toHaveBeenCalled();
        });

        test("renders correctly with all props provided", () => {
            const givenProps = {
                remoteSourceUrl: "https://foo/bar",
                sourceList: JSON.stringify(["foo", "bar"]),
                value: "foo",
                placeholder: "Search",
                valueKey: "name",
                language: "en",
                _expose: vi.fn(),
            };

            render(Autocomplete, { props: givenProps });

            expect(ConfigWrapper).toHaveBeenCalledWith(
                {
                    language: givenProps.language,
                },
                expect.any(Object)
            );
            expect(ElAutocomplete).toHaveBeenCalledWith(
                expect.objectContaining({
                    modelValue: givenProps.value,
                    debounce: 0,
                    placeholder: givenProps.placeholder,
                    teleported: false,
                    "trigger-on-focus": false,
                    "value-key": givenProps.valueKey,
                }),
                expect.any(Object)
            );

            expect(consoleErrorSpy).not.toHaveBeenCalled();
            expect(consoleWarnSpy).not.toHaveBeenCalled();
            expect(givenProps._expose).toHaveBeenCalled({ value: expect.objectContaining({ _value: givenProps.value, __v_isRef: true }) });
        });
    });

    describe("action tests", () => {
        test("calls props.emitCustomEvent when ElAutocomplete emits a keyup.enter event", async () => {
            const givenProps = {
                remoteSourceUrl: "https://foo/bar",
                emitCustomEvent: vi.fn(),
                _expose: vi.fn(),
            };

            vi.mocked(ElAutocomplete).mockImplementationOnce((_, ctx) =>
                h(
                    "div",
                    {},
                    h("input", {
                        "data-testid": "autocomplete-input",
                        onKeyup: (e) => {
                            if (e.key === "Enter") {
                                ctx.emit("keyup.enter");
                            }
                        },
                    })
                )
            );

            render(Autocomplete, { props: givenProps });

            const input = screen.getByTestId("autocomplete-input");
            await fireEvent.keyUp(input, { key: "Enter" });

            expect(givenProps.emitCustomEvent).toHaveBeenCalledWith("pressEnter", undefined);
        });

        test.each([
            ["the remote source URL is provided", "https://foo/bar", null],
            ["the source list is provided", null, JSON.stringify([{ value: "foo" }, { value: "bar" }])],
            ["the remote source URL and a 'transformResultFn' are provided", "https://foo/bar", null, "fooTransformFn"],
        ])("calls the fetchSuggestions function when %s", async (_, remoteSourceUrl, sourceList, transformResultFn) => {
            const givenProps = {
                remoteSourceUrl,
                sourceList,
                transformResultFn,
                _expose: vi.fn(),
            };

            if (transformResultFn) {
                window[transformResultFn] = vi.fn();
            }

            vi.mocked(ElAutocomplete).mockImplementationOnce((props) => {
                const fetchSuggestions = props["fetch-suggestions"] ?? props.fetchSuggestions;
                return h("div", {}, h("input", { "data-testid": "autocomplete-input", onChange: (e) => fetchSuggestions(e.target.value, () => {}) }));
            });

            vi.useFakeTimers();
            render(Autocomplete, { props: givenProps });
            const input = screen.getByTestId("autocomplete-input");
            await fireEvent.change(input, { target: { value: "foo" } });
            vi.runAllTimers();
            vi.useRealTimers();

            const expectedArgs = ["foo", expect.any(Function), remoteSourceUrl, sourceList ? JSON.parse(sourceList) : [], givenProps.remoteQueryKey];
            if (!sourceList) {
                expectedArgs.push(expect.any(AbortSignal));
            }
            expect(fetchSuggestions).toHaveBeenCalledWith(...expectedArgs);
            expect(givenProps._expose).toHaveBeenCalled({ value: expect.objectContaining({ _value: givenProps.value, __v_isRef: true }) });

            const fecthSuggestionsCb = fetchSuggestions.mock.calls[0][1];
            const mockResults = [{ value: "foo" }, { value: "bar" }];
            fecthSuggestionsCb(mockResults);

            if (transformResultFn) {
                expect(window[transformResultFn]).toHaveBeenCalledWith(mockResults);
            }
        });

        describe("live search throttling", () => {
            const renderWithFetchInput = (props) => {
                let fetchFn;
                vi.mocked(ElAutocomplete).mockImplementationOnce((p, ctx) => {
                    fetchFn = p["fetch-suggestions"] ?? p.fetchSuggestions;
                    return h("div", {}, [
                        h("input", { "data-testid": "autocomplete-input", onChange: (e) => fetchFn(e.target.value, props.cb ?? (() => {})) }),
                        h("button", { "data-testid": "autocomplete-clear", onClick: () => ctx.emit("clear") }),
                    ]);
                });
                render(Autocomplete, { props: { _expose: vi.fn(), ...props } });
                return screen.getByTestId("autocomplete-input");
            };

            afterEach(() => {
                vi.useRealTimers();
            });

            test.each([
                ["a", 1500],
                ["abc", 1000],
                ["abcde", 750],
                ["abcdefgh", 500],
            ])("waits before searching for %s (%i ms)", async (query, expectedDelay) => {
                vi.useFakeTimers();
                const input = renderWithFetchInput({ remoteSourceUrl: "https://foo/bar" });

                await fireEvent.change(input, { target: { value: query } });

                vi.advanceTimersByTime(expectedDelay - 1);
                expect(fetchSuggestions).not.toHaveBeenCalled();
                vi.advanceTimersByTime(1);
                expect(fetchSuggestions).toHaveBeenCalledTimes(1);
            });

            test("only searches once for the last query when the user keeps typing", async () => {
                vi.useFakeTimers();
                const input = renderWithFetchInput({ remoteSourceUrl: "https://foo/bar" });

                await fireEvent.change(input, { target: { value: "fo" } });
                vi.advanceTimersByTime(800);
                await fireEvent.change(input, { target: { value: "foobar" } });
                vi.runAllTimers();

                expect(fetchSuggestions).toHaveBeenCalledTimes(1);
                expect(fetchSuggestions.mock.calls[0][0]).toBe("foobar");
            });

            test("does not search below minChars and returns an empty list right away", async () => {
                vi.useFakeTimers();
                const cb = vi.fn();
                const input = renderWithFetchInput({ remoteSourceUrl: "https://foo/bar", minChars: "3", cb });

                await fireEvent.change(input, { target: { value: "fo" } });
                vi.runAllTimers();

                expect(fetchSuggestions).not.toHaveBeenCalled();
                expect(cb).toHaveBeenCalledWith([]);
            });

            test("filters a local sourceList immediately, without delay", async () => {
                vi.useFakeTimers();
                const input = renderWithFetchInput({ sourceList: JSON.stringify([{ value: "foo" }]) });

                await fireEvent.change(input, { target: { value: "f" } });

                expect(fetchSuggestions).toHaveBeenCalledTimes(1);
            });

            test("aborts the in-flight request and ignores its response when a newer query is typed", async () => {
                vi.useFakeTimers();
                const cb = vi.fn();
                const input = renderWithFetchInput({ remoteSourceUrl: "https://foo/bar", cb });

                await fireEvent.change(input, { target: { value: "foobar" } });
                vi.runAllTimers();
                const [, firstCallback, , , , firstSignal] = fetchSuggestions.mock.calls[0];

                await fireEvent.change(input, { target: { value: "foobarbaz" } });
                expect(firstSignal.aborted).toBe(true);

                firstCallback([{ value: "stale" }]);
                expect(cb).not.toHaveBeenCalled();

                vi.runAllTimers();
                const secondCallback = fetchSuggestions.mock.calls[1][1];
                secondCallback([{ value: "fresh" }]);
                expect(cb).toHaveBeenCalledTimes(1);
                expect(cb).toHaveBeenCalledWith([{ value: "fresh" }]);
            });

            test("serves a repeated query from the cache without a new request", async () => {
                vi.useFakeTimers();
                const cb = vi.fn();
                const input = renderWithFetchInput({ remoteSourceUrl: "https://foo/bar", cb });

                await fireEvent.change(input, { target: { value: "foobar" } });
                vi.runAllTimers();
                fetchSuggestions.mock.calls[0][1]([{ value: "foobar" }]);

                await fireEvent.change(input, { target: { value: "foobarb" } });
                await fireEvent.change(input, { target: { value: "foobar" } });
                vi.runAllTimers();

                expect(fetchSuggestions).toHaveBeenCalledTimes(1);
                expect(cb).toHaveBeenLastCalledWith([{ value: "foobar" }]);
            });

            test("cancels a pending search when the input is cleared", async () => {
                vi.useFakeTimers();
                const input = renderWithFetchInput({ remoteSourceUrl: "https://foo/bar" });

                await fireEvent.change(input, { target: { value: "foobar" } });
                await fireEvent.click(screen.getByTestId("autocomplete-clear"));
                vi.runAllTimers();

                expect(fetchSuggestions).not.toHaveBeenCalled();
            });
        });

        test("calls props.emitCustomEvent when ElAutocomplete emits a select event", async () => {
            const givenProps = {
                remoteSourceUrl: "https://foo/bar",
                emitCustomEvent: vi.fn(),
                _expose: vi.fn(),
            };

            vi.mocked(ElAutocomplete).mockImplementationOnce((_, ctx) =>
                h("div", {}, h("div", { "data-testid": "autocomplete-suggestion", onClick: () => ctx.emit("select", "suggestion") }, "Suggestion"))
            );

            render(Autocomplete, { props: givenProps });

            const suggestion = screen.getByTestId("autocomplete-suggestion");
            await fireEvent.click(suggestion);

            expect(givenProps.emitCustomEvent).toHaveBeenCalledWith("select", "suggestion");
            expect(givenProps._expose).toHaveBeenCalled({ value: expect.objectContaining({ _value: givenProps.value, __v_isRef: true }) });
        });

        test("calls props.emitCustomEvent when ElAutocomplete emits a input event", async () => {
            const givenProps = {
                remoteSourceUrl: "https://foo/bar",
                emitCustomEvent: vi.fn(),
                _expose: vi.fn(),
            };

            vi.mocked(ElAutocomplete).mockImplementationOnce((_, ctx) =>
                h(
                    "div",
                    {},
                    h("input", {
                        "data-testid": "autocomplete-input",
                        onChange: (e) => ctx.emit("input", e.target.value),
                    })
                )
            );

            render(Autocomplete, { props: givenProps });

            const input = screen.getByTestId("autocomplete-input");
            await fireEvent.change(input, { target: { value: "foo" } });

            expect(givenProps.emitCustomEvent).toHaveBeenCalledWith("input", "foo");
            expect(givenProps._expose).toHaveBeenCalled({ value: expect.objectContaining({ _value: givenProps.value, __v_isRef: true }) });
        });
    });
});
