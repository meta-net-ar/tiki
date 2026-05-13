import { fireEvent, render, screen, within } from "@testing-library/vue";
import { afterEach, beforeAll, describe, expect, test, vi } from "vitest";
import Transfer, { DATA_TEST_ID } from "../../components/Transfer/Transfer.vue";
import { ElAlert, ElButton, ElTransfer } from "element-plus";
import { h } from "vue";
import Sortable from "sortablejs";
import ConfigWrapper from "../../components/ConfigWrapper.vue";

vi.mock("element-plus", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        ElTransfer: vi.fn((props, { slots }) =>
            h(
                "div",
                { ...props },
                props.modelValue.map((key) => slots.default({ option: { key } }))
            )
        ),
        ElAlert: vi.fn(),
        ElButton: vi.fn((props) => h("button", props)),
    };
});

vi.mock("sortablejs", async () => {
    return {
        default: vi.fn(),
    };
});

vi.mock("../../components/ConfigWrapper.vue", () => {
    return {
        default: vi.fn((props, { slots }) => h("div", { ...props, "data-testid": "config-wrapper" }, slots.default ? slots.default() : null)),
    };
});

describe("Transfer", () => {
    const consoleErrorSpy = vi.spyOn(console, "error");
    const consoleWarnSpy = vi.spyOn(console, "warn");

    const props = {
        data: { a: "Item A", b: "Item B", c: "Item C" },
        fieldName: "testField",
        filterable: true,
        defaultValue: ["a", "b"],
        sourceListTitle: "Source List",
        targetListTitle: "Target List",
        filterPlaceholder: "Filter items",
        ordering: false,
        language: "en",
    };

    afterEach(() => {
        vi.clearAllMocks();
        vi.resetModules();
    });

    beforeAll(() => {
        window.tr = (str) => "translated: " + str;
    });

    test("renders correctly with given props", async () => {
        render(Transfer, { props });

        const configWrapper = screen.getByTestId("config-wrapper");
        expect(configWrapper).to.exist;
        const selectElement = within(configWrapper).getByTestId(DATA_TEST_ID.HIDDEN_SELECT);
        expect(selectElement).to.exist;
        // should be hidden
        expect(selectElement.style).to.have.property("display", "none");
        expect(selectElement.getAttribute("aria-hidden")).to.equal("true");
        // should have correct name
        expect(selectElement.name).to.equal(props.fieldName);
        // its options should be the selected values
        assertSelectElementToHaveOptions(selectElement, props.defaultValue);

        // options should not render the edit button as showEdit is not set
        expect(screen.queryByTestId(DATA_TEST_ID.EDIT_ITEM_BUTTON)).not.to.exist;

        // el-transfer should be rendered with correct props
        const elTransferData = Object.entries(props.data).map(([key, value]) => ({ key, label: value }));
        expect(ElTransfer).toHaveBeenCalledWith(
            expect.objectContaining({
                data: elTransferData,
                filterable: props.filterable,
                "filter-placeholder": props.filterPlaceholder,
                titles: [props.sourceListTitle, props.targetListTitle],
            }),
            expect.any(Object)
        );

        expect(ConfigWrapper).toHaveBeenCalledWith(
            {
                language: props.language,
            },
            expect.any(Object)
        );

        expect(consoleErrorSpy).not.toHaveBeenCalled();
        expect(consoleWarnSpy).not.toHaveBeenCalled();
    });

    test("renders correctly when the given prop data and defaultValue are JSON strings", async () => {
        const data = JSON.stringify(props.data);
        const defaultValue = JSON.stringify(props.defaultValue);
        render(Transfer, { props: { ...props, data, defaultValue } });

        const selectElement = screen.getByTestId(DATA_TEST_ID.HIDDEN_SELECT);
        assertSelectElementToHaveOptions(selectElement, props.defaultValue);
        assertElTransferToBeCalledWith(props);

        expect(consoleErrorSpy).not.toHaveBeenCalled();
        expect(consoleWarnSpy).not.toHaveBeenCalled();
    });

    test("renders correctly when the defaultValue prop is not set", async () => {
        render(Transfer, { props: { ...props, defaultValue: undefined } });

        const selectElement = screen.getByTestId(DATA_TEST_ID.HIDDEN_SELECT);
        assertSelectElementToHaveOptions(selectElement, []);
        assertElTransferToBeCalledWith(props);

        expect(consoleErrorSpy).not.toHaveBeenCalled();
        expect(consoleWarnSpy).not.toHaveBeenCalled();
    });

    test("renders correctly given the prop isInvalid is true", () => {
        render(Transfer, { props: { ...props, isInvalid: "true" } });

        const tranferContainer = screen.getByTestId(DATA_TEST_ID.TRANSFER_CONTAINER);
        expect(tranferContainer.getAttribute("class")).to.include("invalid");

        expect(consoleErrorSpy).not.toHaveBeenCalled();
        expect(consoleWarnSpy).not.toHaveBeenCalled();
    });

    test.each([
        ["true", "error"],
        ["false", "info"],
    ])("renders the alert element with the correct status given the helperText is set and the prop isInvalid is %s", (isInvalid, type) => {
        render(Transfer, { props: { ...props, isInvalid: isInvalid, helperText: "foo" } });
        expect(ElAlert).toHaveBeenCalledWith(
            expect.objectContaining({
                type: type,
            }),
            null
        );

        expect(consoleErrorSpy).not.toHaveBeenCalled();
        expect(consoleWarnSpy).not.toHaveBeenCalled();
    });

    test.each([
        [{ minItems: 2 }, "A minimum of 2 items is allowed"],
        [{ maxItems: 2 }, "A maximum of 2 items is allowed"],
        [{ minItems: 2, maxItems: 4 }, "A minimum of 2 items and a maximum of 4 items are allowed"],
        [{ helperText: "foo" }, "foo"],
        [{ minItems: 2, helperText: "foo" }, "foo"],
        [{ maxItems: 2, helperText: "foo" }, "foo"],
        [{ minItems: 2, maxItems: 4, helperText: "foo" }, "foo"],
    ])(
        "renders the alert element with the correct message in all variations of the props: minItems, maxItems, and helperText",
        (givenProps, expectedMessage) => {
                vi.mocked(ElAlert).mockImplementationOnce((props, ctx) => h("div", props, ctx.slots.default()));

            render(Transfer, { props: { ...props, ...givenProps } });

            const helperText = screen.getByTestId(DATA_TEST_ID.HELPER_TEXT);
            expect(helperText).to.exist;
            expect(helperText.textContent).to.equal(expectedMessage);

            expect(consoleErrorSpy).not.toHaveBeenCalled();
            expect(consoleWarnSpy).not.toHaveBeenCalled();
        }
    );

    test("renders an edit button on each item in the target list when showEdit prop is true", () => {
        render(Transfer, { props: { ...props, showEdit: "true" } });

        const editButtons = screen.getAllByTestId(DATA_TEST_ID.EDIT_ITEM_BUTTON);
        expect(editButtons).to.have.length(props.defaultValue.length);

        editButtons.forEach(() => {
            expect(ElButton).toHaveBeenCalledWith(
                expect.objectContaining({
                    type: "primary",
                    text: true,
                    icon: expect.objectContaining({ name: "Edit" }),
                    onClick: expect.any(Function),
                }),
                null
            );
        });

        expect(consoleErrorSpy).not.toHaveBeenCalled();
        expect(consoleWarnSpy).not.toHaveBeenCalled();
    });

    test("should correctly handle click event on the 'edit' button for an item in the target list", async () => {
        const emit = vi.fn();

        render(Transfer, { props: { ...props, showEdit: "true", _emit: emit } });
        const editButtons = screen.getAllByTestId(DATA_TEST_ID.EDIT_ITEM_BUTTON);

        await Promise.all(editButtons.map((editButton) => fireEvent.click(editButton)));

        props.defaultValue.forEach((key) => {
            expect(emit).toHaveBeenCalledWith("edit", { value: key });
        });
    });

    test("should correctly initialize SortableJS when ordering prop is true", async () => {
        vi.mocked(ElTransfer).mockImplementationOnce(() =>
            h("div", {}, [h("div", { class: "el-transfer-panel__list" }, "list"), h("div", { class: "el-transfer-panel__list" }, "list 2")])
        );
        render(Transfer, { props: { ...props, ordering: true } });

        expect(Sortable).toHaveBeenCalledWith(
            screen.getByText("list 2"),
            expect.objectContaining({
                sorting: true,
                group: props.fieldName,
                onAdd: expect.any(Function),
                onUpdate: expect.any(Function),
            })
        );

        expect(Sortable).toHaveBeenCalledWith(
            screen.getByText("list"),
            expect.objectContaining({
                sorting: false,
                group: props.fieldName,
                onAdd: expect.any(Function),
            })
        );

        expect(consoleErrorSpy).not.toHaveBeenCalled();
        expect(consoleWarnSpy).not.toHaveBeenCalled();
    });

    test("should correctly reorder items with SortableJS when ordering prop is true", async () => {
        vi.mocked(ElTransfer).mockImplementationOnce(() =>
            h("div", {}, [
                h("div", { class: "el-transfer-panel__list" }, "list 1"),
                h("div", { class: "el-transfer-panel__list" }, [
                    h(
                        "div",
                        { class: "el-transfer-panel__item" },
                        h("div", { class: "el-checkbox__label" }, h("span", { "data-key": "a" }, "Item A"))
                    ),
                    h(
                        "div",
                        { class: "el-transfer-panel__item" },
                        h("div", { class: "el-checkbox__label" }, h("span", { "data-key": "b" }, "Item B"))
                    ),
                ]),
            ])
        );

        const emitValueChange = vi.fn();
        props.emitValueChange = emitValueChange;

        render(Transfer, { props: { ...props, ordering: true } });

        const sortableList = screen.getByText("list 1").nextElementSibling;
        const orderedChildren = Array.from(sortableList.children).reverse();
        sortableList.replaceChildren(...orderedChildren);

        Sortable.mock.calls[0][1].onUpdate();

        expect(emitValueChange).toHaveBeenCalledWith({ value: ["b", "a"] });
    });

    test("should not reorder items with SortableJS when ordering prop is false", async () => {
        vi.mocked(ElTransfer).mockImplementationOnce(() =>
            h("div", {}, [
                h("div", { class: "el-transfer-panel__list" }, "list 1"),
                h("div", { class: "el-transfer-panel__list" }, [
                    h(
                        "div",
                        { class: "el-transfer-panel__item" },
                        h("div", { class: "el-checkbox__label" }, h("span", { "data-key": "a" }, "Item A"))
                    ),
                    h(
                        "div",
                        { class: "el-transfer-panel__item" },
                        h("div", { class: "el-checkbox__label" }, h("span", { "data-key": "b" }, "Item B"))
                    ),
                ]),
            ])
        );

        const emitValueChange = vi.fn();
        props.emitValueChange = emitValueChange;

        render(Transfer, { props: { ...props, ordering: false } });

        const sortableList = screen.getByText("list 1").nextElementSibling;
        const orderedChildren = Array.from(sortableList.children).reverse();
        sortableList.replaceChildren(...orderedChildren);

        Sortable.mock.calls[0][1].onUpdate();

        expect(emitValueChange).not.toHaveBeenCalled();
    });

    test("should correctly transfer items  via SortableJS from source to target list", async () => {
        vi.mocked(ElTransfer).mockImplementationOnce(() =>
            h("div", {}, [
                h("div", { class: "el-transfer-panel__list" }, [
                    h(
                        "div",
                        { class: "el-transfer-panel__item", "data-testid": "item-c" },
                        h("div", { class: "el-checkbox__label" }, h("span", { "data-key": "c" }, "Item C"))
                    ),
                ]),
                h("div", { class: "el-transfer-panel__list" }, "list 2"),
            ])
        );

        const emitValueChange = vi.fn();
        props.emitValueChange = emitValueChange;

        render(Transfer, { props });

        const item = screen.getByTestId("item-c");
        const itemRemoveMock = vi.fn();
        item.remove = itemRemoveMock;

        Sortable.mock.calls[0][1].onAdd({ item });

        expect(emitValueChange).toHaveBeenCalledWith({ value: ["a", "b", "c"] });
        expect(itemRemoveMock).toHaveBeenCalled();
    });

    test("should correctly transfer items via SortableJS from target to source list", async () => {
        vi.mocked(ElTransfer).mockImplementationOnce(() =>
            h("div", {}, [
                h("div", { class: "el-transfer-panel__list" }, "list 1"),
                h("div", { class: "el-transfer-panel__list" }, [
                    h(
                        "div",
                        { class: "el-transfer-panel__item", "data-testid": "item-b" },
                        h("div", { class: "el-checkbox__label" }, h("span", { "data-key": "b" }, "Item B"))
                    ),
                ]),
            ])
        );

        const emitValueChange = vi.fn();
        props.emitValueChange = emitValueChange;

        render(Transfer, { props });

        const item = screen.getByTestId("item-b");
        const itemRemoveMock = vi.fn();
        item.remove = itemRemoveMock;

        Sortable.mock.calls[1][1].onAdd({ item });

        expect(emitValueChange).toHaveBeenCalledWith({ value: ["a"] });
        expect(itemRemoveMock).toHaveBeenCalled();
    });

    test("should keep hidden select in sync with el-transfer and call the given emitValueChange prop when the value changes", async () => {
        props.emitValueChange = vi.fn();
        vi.mocked(ElTransfer).mockImplementationOnce((_, ctx) => {
            const handleClick = () => {
                ctx.emit("update:modelValue", ["c"]);
                ctx.emit("change", ["c"]);
            };
            return h("div", {}, h("button", { onClick: handleClick }, "Transfer Item"));
        });

        render(Transfer, { props });

        const selectElement = screen.getByTestId(DATA_TEST_ID.HIDDEN_SELECT);
        const transferButton = screen.getByText("Transfer Item");

        await fireEvent.click(transferButton);

        // check hidden select's value
        expect(selectElement.options).toHaveLength(1);
        expect(selectElement.options[0].value).to.equal("c");
        expect(props.emitValueChange).toHaveBeenCalledWith(expect.objectContaining({ value: ["c"] }));

        expect(consoleErrorSpy).not.toHaveBeenCalled();
        expect(consoleWarnSpy).not.toHaveBeenCalled();
    });
});

function assertElTransferToBeCalledWith(props) {
    const elTransferData = Object.entries(props.data).map(([key, value]) => ({ key, label: value }));
    expect(ElTransfer).toHaveBeenCalledWith(
        expect.objectContaining({
            data: elTransferData,
            filterable: props.filterable,
            "filter-placeholder": props.filterPlaceholder,
            titles: [props.sourceListTitle, props.targetListTitle],
        }),
        expect.any(Object)
    );
}

function assertSelectElementToHaveOptions(selectElement, values) {
    expect(selectElement.options).toHaveLength(values.length);
    values.forEach((value) => {
        const option = within(selectElement)
            .getAllByRole("option", { hidden: true })
            .find((el) => el.value === value);
        expect(option).to.exist;
    });
}
