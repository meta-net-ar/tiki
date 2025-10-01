import { render, screen, within } from "@testing-library/vue";
import { describe, expect, test, vi } from "vitest";
import { h } from "vue";
import FileInput, { DATA_TEST_ID } from "../../components/FileInput/FileInput.vue";
import { ElIcon, ElUpload } from "element-plus";
import { UploadFilled } from "@element-plus/icons-vue";

vi.mock("element-plus", () => {
    return {
        ElUpload: vi.fn((props, ctx) => h("div", props, ctx.slots)),
        ElIcon: vi.fn((props, ctx) => h("div", props, ctx.slots)),
    };
});

vi.mock("../../components/ConfigWrapper.vue", () => {
    return {
        default: vi.fn((props, { slots }) => h("div", props, slots)),
    };
});

vi.mock("@element-plus/icons-vue", () => {
    return {
        UploadFilled: vi.fn((props) => h("div", props)),
    };
});

describe("FileInput", () => {
    afterEach(() => {
        vi.clearAllMocks();
    });

    test("renders correctly with no prop", () => {
        render(FileInput);

        const uploadElement = screen.getByTestId("file-input");
        expect(uploadElement).to.exist;

        expect(ElUpload).toHaveBeenCalledWith(
            expect.objectContaining({
                modelValue: [],
                accept: undefined,
                multiple: undefined,
                drag: true,
            }),
            expect.anything()
        );

        const uploadIconWrapper = within(uploadElement).getByTestId(DATA_TEST_ID.UPLOAD_ICON_WRAPPER);
        expect(uploadIconWrapper).to.exist;
        expect(ElIcon).toHaveBeenCalled();

        expect(within(uploadIconWrapper).getByTestId(DATA_TEST_ID.UPLOAD_ICON)).to.exist;
        expect(UploadFilled).toHaveBeenCalled();
    });

    test("renders correctly with all props", () => {
        render(FileInput, {
            props: {
                accept: "image/*",
                multiple: "multiple",
                _emit: vi.fn(),
            },
        });

        expect(ElUpload).toHaveBeenCalledWith(
            expect.objectContaining({
                accept: "image/*",
                multiple: "multiple",
            }),
            expect.anything()
        );
    });

    test("correctly handle the change event", async () => {
        // Ensure that the exposed handleRemove function is available
        const mockHandleRemove = vi.fn();
        ElUpload.mockImplementationOnce((props, { slots }) => {
            return h(
                "div",
                {
                    ...props,
                    ref: (el) => {
                        if (el) el.handleRemove = mockHandleRemove;
                    },
                },
                slots
            );
        });

        const mockEmit = vi.fn();
        render(FileInput, {
            props: {
                _emit: mockEmit,
            },
        });

        ElUpload.mock.calls[0][0]["on-change"]("file", "fileList");
        expect(mockEmit).toHaveBeenCalledWith("change", "file");

        // on subsquent uploads, remove the previous file
        ElUpload.mock.calls[0][0]["on-change"]("file2", "fileList");
        expect(mockEmit).toHaveBeenCalledWith("change", "file2");
        expect(mockHandleRemove).toHaveBeenCalledWith("file");
    });

    test("correctly handle the change event with multiple files", async () => {
        const mockEmit = vi.fn();
        render(FileInput, {
            props: {
                _emit: mockEmit,
                multiple: "multiple",
            },
        });

        ElUpload.mock.calls[0][0]["on-change"]("file", "fileList");
        expect(mockEmit).toHaveBeenCalledWith("change", "fileList");
    });
});
