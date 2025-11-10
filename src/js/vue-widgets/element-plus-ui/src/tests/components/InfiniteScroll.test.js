import { render, screen } from "@testing-library/vue";
import { afterEach, beforeAll, describe, expect, test, vi } from "vitest";
import InfiniteScroll, { DATA_TESTID, DEFAULT_HEIGHT } from "../../components/InfiniteScroll/InfiniteScroll.vue";
import { ElScrollbar } from "element-plus";
import { h } from "vue";
import $ from "jquery";
import mirrorInputs from "../../helpers/infiniteScroll/mirrorInputs";

vi.mock("element-plus", async (importActual) => {
    const actual = await importActual();
    return {
        ...actual,
        ElScrollbar: vi.fn((props) => {
            const vnode = h("div", {
                ref: (el) => {
                    if (!el) return;

                    const wraper = document.createElement("div");
                    wraper.innerHTML = "<div class='el-scrollbar__view' data-testid='scrollbar-view'></div>";
                    el.wrapRef = wraper;
                    el.appendChild(wraper);
                },
                ...props,
            });
            return vnode;
        }),
    };
});

vi.mock("../../helpers/infiniteScroll/mirrorInputs", () => {
    return {
        default: vi.fn(),
    };
});

describe("InfiniteScroll", () => {
    beforeAll(() => {
        window.$ = $;
        window.tr = (str) => "translated: " + str;
        window.showMessage = vi.fn();
    });

    afterEach(() => {
        vi.clearAllMocks();
        $("body").empty();
    });

    describe("render tests", () => {
        test("renders correctly with basic props", async () => {
            const givenProps = {
                containerId: "container-123",
                step: 20,
                totalCount: 100,
                _cssFiles: "css/file1.css,css/file2.css",
            };

            const container = $(`<div id="${givenProps.containerId}"></div>`);
            $("body").append(container);

            render(InfiniteScroll, { props: givenProps });

            expect(ElScrollbar).toHaveBeenCalledWith(
                expect.objectContaining({
                    onEndReached: expect.any(Function),
                    height: false,
                    "max-height": "80vh",
                }),
                null
            );

            const cssStyles = screen.getAllByTestId(DATA_TESTID.CSS_STYLE);
            givenProps._cssFiles.split(",").forEach((file, index) => {
                expect(cssStyles[index].getAttribute("href")).toBe(file);
            });

            await vi.waitFor(() => {
                expect(mirrorInputs).toHaveBeenCalledWith(
                    container[0],
                    expect.objectContaining({ value: expect.objectContaining({ wrapRef: expect.any(HTMLElement) }) })
                );
            });

            expect(screen.getAllByTestId("scrollbar-view")[0].innerHTML).toBe(container[0].outerHTML);
        });

        test("renders with computed height when the container has items", async () => {
            const givenProps = {
                containerId: "container-456",
                step: 10,
                totalCount: 50,
                _cssFiles: "css/file1.css,css/file2.css",
            };

            const container = $(`<div id="${givenProps.containerId}"></div>`);
            for (let i = 0; i < 10; i++) {
                const item = $("<div>").text(`Item ${i + 1}`);
                container.append(item);
            }

            const mockClientHeight = 1100;
            Object.defineProperty(container[0], "clientHeight", { value: mockClientHeight });

            $("body").append(container);

            render(InfiniteScroll, { props: givenProps });

            await vi.waitFor(() => {
                expect(ElScrollbar).toHaveBeenCalledWith(
                    expect.objectContaining({
                        height: `calc(${mockClientHeight}px - 35vh)`,
                    }),
                    null
                );
            });

            expect(mirrorInputs).toHaveBeenCalledWith(
                container[0],
                expect.objectContaining({ value: expect.objectContaining({ wrapRef: expect.any(HTMLElement) }) })
            );

            expect(screen.getAllByTestId("scrollbar-view")[0].innerHTML).toBe(container[0].outerHTML);
        });

        test("renders with computed height from default when the container height cannot be determined", async () => {
            const givenProps = {
                containerId: "container-789",
                step: 5,
                totalCount: 75,
                _cssFiles: "css/file1.css,css/file2.css",
            };

            const container = $(`<div id="${givenProps.containerId}"></div>`);
            for (let i = 0; i < 5; i++) {
                const item = $("<div>").text(`Item ${i + 1}`);
                container.append(item);
            }

            $("body").append(container);

            render(InfiniteScroll, { props: givenProps });

            await vi.waitFor(() => {
                expect(ElScrollbar).toHaveBeenCalledWith(
                    expect.objectContaining({
                        height: `calc(${DEFAULT_HEIGHT}px - 35vh)`,
                    }),
                    null
                );
            });

            expect(mirrorInputs).toHaveBeenCalledWith(
                container[0],
                expect.objectContaining({ value: expect.objectContaining({ wrapRef: expect.any(HTMLElement) }) })
            );

            expect(screen.getAllByTestId("scrollbar-view")[0].innerHTML).toBe(container[0].outerHTML);
        });

        test("logs error when containerId does not exist in DOM", async () => {
            const consoleErrorSpy = vi.spyOn(console, "error").mockImplementation(() => {});

            const givenProps = {
                containerId: "non-existent-container",
                step: 10,
                totalCount: 50,
                _cssFiles: "",
            };

            render(InfiniteScroll, { props: givenProps });

            await vi.waitFor(() => {
                expect(consoleErrorSpy).toHaveBeenCalledWith(`Container with id ${givenProps.containerId} not found`);
            });

            expect(mirrorInputs).not.toHaveBeenCalled();

            consoleErrorSpy.mockRestore();
        });
    });

    describe("actions tests", () => {
        test("loads more items when end is reached", async () => {
            const givenProps = {
                containerId: "container-load-more",
                step: 10,
                totalCount: 30,
                loadUrl: "http://localhost/load/more/items",
                _cssFiles: "",
            };

            const container = $(`<div id="${givenProps.containerId}"></div>`);
            for (let i = 0; i < 10; i++) {
                const item = $("<div>").text(`Item ${i + 1}`);
                container.append(item);
            }

            const expectedUpdatedContainer = container.clone();
            for (let i = 10; i < 20; i++) {
                const item = $("<div>").text(`Item ${i + 1}`);
                expectedUpdatedContainer.append(item);
            }

            $("body").append(container);

            vi.spyOn(window, "fetch").mockResolvedValue(
                // HTML response with 10 more items indexed from 11 to 20
                new Response(
                    `<div id="${givenProps.containerId}">` + Array.from({ length: 10 }, (_, i) => `<div>Item ${i + 11}</div>`).join("") + "</div>",
                    {
                        status: 200,
                        headers: { "Content-Type": "text/html" },
                    }
                )
            );

            render(InfiniteScroll, { props: givenProps });

            ElScrollbar.mock.calls[0][0].onEndReached("bottom");

            await vi.waitFor(() => {
                expect(screen.getAllByTestId("scrollbar-view")[0].innerHTML).toBe(expectedUpdatedContainer[0].outerHTML);
            });

            expect(mirrorInputs).toHaveBeenNthCalledWith(
                2,
                expect.any(DocumentFragment),
                expect.objectContaining({ value: expect.objectContaining({ wrapRef: expect.any(HTMLElement) }) })
            );
        });

        test("loads more items from the target container when specified and end is reached", async () => {
            const givenProps = {
                containerId: "container-with-target",
                targetContainerId: "target-container",
                step: 5,
                totalCount: 15,
                loadUrl: "http://localhost/load/more/target/items",
                _cssFiles: "",
            };

            const container = $(`<div id="${givenProps.containerId}"><div id="${givenProps.targetContainerId}"></div></div>`);
            const targetContainer = container.find(`#${givenProps.targetContainerId}`);
            for (let i = 0; i < 5; i++) {
                const item = $("<div>").text(`Item ${i + 1}`);
                targetContainer.append(item);
            }

            const expectedUpdatedContainer = container.clone();
            const expectedTargetContainer = expectedUpdatedContainer.find(`#${givenProps.targetContainerId}`);
            for (let i = 5; i < 10; i++) {
                const item = $("<div>").text(`Item ${i + 1}`);
                expectedTargetContainer.append(item);
            }

            $("body").append(container);

            vi.spyOn(window, "fetch").mockResolvedValue(
                // HTML response with 5 more items indexed from 6 to 10
                new Response(
                    `<div id="${givenProps.containerId}"><div id="${givenProps.targetContainerId}">` +
                        Array.from({ length: 5 }, (_, i) => `<div>Item ${i + 6}</div>`).join("") +
                        `</div></div>`,
                    {
                        status: 200,
                        headers: { "Content-Type": "text/html" },
                    }
                )
            );

            render(InfiniteScroll, { props: givenProps });

            ElScrollbar.mock.calls[0][0].onEndReached("bottom");

            await vi.waitFor(() => {
                expect(screen.getAllByTestId("scrollbar-view")[0].innerHTML).toBe(expectedUpdatedContainer[0].outerHTML);
            });

            expect(mirrorInputs).toHaveBeenNthCalledWith(
                2,
                expect.any(DocumentFragment),
                expect.objectContaining({ value: expect.objectContaining({ wrapRef: expect.any(HTMLElement) }) })
            );
        });

        test("shows loader when loading more items", async () => {
            const givenProps = {
                containerId: "container-loader-test",
                step: 10,
                totalCount: 50,
                loadUrl: "http://localhost/load/more/items",
                _cssFiles: "",
            };

            const container = $(`<div id="${givenProps.containerId}"></div>`);
            for (let i = 0; i < 10; i++) {
                const item = $("<div>").text(`Item ${i + 1}`);
                container.append(item);
            }

            $("body").append(container);

            vi.spyOn(window, "fetch").mockImplementation(() => {
                return new Promise((resolve) => {
                    setTimeout(() => {
                        resolve(new Response("<div></div>", { status: 200, headers: { "Content-Type": "text/html" } }));
                    }, 100);
                });
            });

            render(InfiniteScroll, { props: givenProps });

            expect(screen.queryByTestId("infinite-scroll-loader")).toBeNull();

            ElScrollbar.mock.calls[0][0].onEndReached("bottom");

            await vi.waitFor(() => {
                expect(screen.getByTestId(DATA_TESTID.LOADER)).toBeDefined();
            });
        });

        test("shows an error message and logs the error when loading items fails", async () => {
            const consoleErrorSpy = vi.spyOn(console, "error").mockImplementation(() => {});

            const givenProps = {
                containerId: "container-error-test",
                step: 10,
                totalCount: 50,
                loadUrl: "http://localhost/load/error",
                _cssFiles: "",
            };

            const container = $(`<div id="${givenProps.containerId}"></div>`);
            for (let i = 0; i < 10; i++) {
                const item = $("<div>").text(`Item ${i + 1}`);
                container.append(item);
            }

            $("body").append(container);

            vi.spyOn(window, "fetch").mockResolvedValue(
                new Response("Internal Server Error", {
                    status: 500,
                    headers: { "Content-Type": "text/plain" },
                })
            );

            render(InfiniteScroll, { props: givenProps });

            ElScrollbar.mock.calls[0][0].onEndReached("bottom");

            await vi.waitFor(() => {
                expect(window.showMessage).toHaveBeenCalledWith("translated: Error loading more items", "error");
                expect(consoleErrorSpy).toHaveBeenCalledWith("Error loading more items:", expect.any(Error));
            });

            consoleErrorSpy.mockRestore();
        });

        test.each([
            ["props.loadUrl defined", true, "http://localhost/custom/load/url"],
            ["props.loadUrl undefined", false, "http://localhost/mock/window/location"],
        ])("uses correct loadUrl when %s", async (description, isLoadUrlDefined, givenUrl) => {
            const givenProps = {
                containerId: "container-load-url-test",
                step: 10,
                totalCount: 30,
                _cssFiles: "",
            };

            if (isLoadUrlDefined) {
                givenProps.loadUrl = givenUrl;
            } else {
                // Mock window.location.href
                delete window.location;
                window.location = { href: givenUrl };
            }

            const container = $(`<div id="${givenProps.containerId}"></div>`);
            for (let i = 0; i < 10; i++) {
                const item = $("<div>").text(`Item ${i + 1}`);
                container.append(item);
            }

            $("body").append(container);

            const fetchSpy = vi
                .spyOn(window, "fetch")
                .mockResolvedValue(new Response("<div></div>", { status: 200, headers: { "Content-Type": "text/html" } }));

            render(InfiniteScroll, { props: givenProps });

            ElScrollbar.mock.calls[0][0].onEndReached("bottom");

            const expectedUrl = new URL(givenUrl);
            expectedUrl.searchParams.set("offset", givenProps.step);
            await vi.waitFor(() => {
                expect(fetchSpy).toHaveBeenCalledWith(expectedUrl);
            });
        });

        test("does not load more items when disabled", async () => {
            const givenProps = {
                containerId: "container-disabled-test",
                step: 10,
                totalCount: 10, // totalCount equal to step to trigger disabled state
                loadUrl: "http://localhost/load/more/items",
                _cssFiles: "",
            };

            const container = $(`<div id="${givenProps.containerId}"></div>`);
            for (let i = 0; i < 10; i++) {
                const item = $("<div>").text(`Item ${i + 1}`);
                container.append(item);
            }

            $("body").append(container);

            const fetchSpy = vi.spyOn(window, "fetch");

            render(InfiniteScroll, { props: givenProps });

            ElScrollbar.mock.calls[0][0].onEndReached("bottom");

            await vi.waitFor(() => {
                expect(fetchSpy).not.toHaveBeenCalled();
            });
        });

        test("does not load more items when the endReached direction is not 'bottom'", async () => {
            const givenProps = {
                containerId: "container-wrong-direction-test",
                step: 10,
                totalCount: 50,
                loadUrl: "http://localhost/load/more/items",
                _cssFiles: "",
            };

            const container = $(`<div id="${givenProps.containerId}"></div>`);
            for (let i = 0; i < 10; i++) {
                const item = $("<div>").text(`Item ${i + 1}`);
                container.append(item);
            }

            $("body").append(container);

            const fetchSpy = vi.spyOn(window, "fetch");

            render(InfiniteScroll, { props: givenProps });

            ElScrollbar.mock.calls[0][0].onEndReached("top");

            await vi.waitFor(() => {
                expect(fetchSpy).not.toHaveBeenCalled();
            });
        });

        test("does not load more items when already loading", async () => {
            const givenProps = {
                containerId: "container-already-loading-test",
                step: 10,
                totalCount: 50,
                loadUrl: "http://localhost/load/more/items",
                _cssFiles: "",
            };

            const container = $(`<div id="${givenProps.containerId}"></div>`);
            for (let i = 0; i < 10; i++) {
                const item = $("<div>").text(`Item ${i + 1}`);
                container.append(item);
            }

            $("body").append(container);

            const fetchSpy = vi.spyOn(window, "fetch").mockImplementation(() => {
                return new Promise((resolve) => {
                    setTimeout(() => {
                        resolve(new Response("<div></div>", { status: 200, headers: { "Content-Type": "text/html" } }));
                    }, 100); // Delay to simulate loading
                });
            });

            render(InfiniteScroll, { props: givenProps });

            // First call to load more items
            ElScrollbar.mock.calls[0][0].onEndReached("bottom");

            // Immediately try to call again while loading
            ElScrollbar.mock.calls[0][0].onEndReached("bottom");

            await vi.waitFor(() => {
                expect(fetchSpy).toHaveBeenCalledTimes(1);
            });
        });
    });
});
