import { render, screen } from "@testing-library/vue";
import { afterEach, beforeAll, describe, expect, test, vi } from "vitest";
import InfiniteScroll, { DEFAULT_HEIGHT } from "../../components/InfiniteScroll/InfiniteScroll.vue";
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
        });
    });
});
