import { render, screen, waitFor, within } from "@testing-library/vue";
import { afterEach, describe, expect, test, vi } from "vitest";
import FileGalUploader, { DATA_TEST_ID, DEFAULT_ACTION_URL } from "../../components/FileGalUploader/FileGalUploader.vue";
import { h } from "vue";
import { ElMessage } from "element-plus";
import ConfigWrapper from "../../components/ConfigWrapper.vue";
import * as AjaxUploadHelpers from "element-plus/es/components/upload/src/ajax.mjs";
import getUploadAjaxError from "../../helpers/fileGalUploader/getUploadAjaxError";
import handleTikiFeedback from "../../helpers/fileGalUploader/handleTikiFeedback";

// A plain vi.fn() spy for tracking what props FileGalUploader passes to <el-upload>.
// Using a separate spy (instead of the component itself) because the mock must be a
// stateful component object (not a vi.fn() function) for `ref.submit()` to work via expose().
const elUploadSpy = vi.fn();
let mockElUploadSubmit = vi.fn();
let mockElUploadHandleRemove = vi.fn();
let mockUploadSlotFile = null;
let mockEditedImage = null;

vi.mock("element-plus", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        // Stateful component so Vue resolves the template ref to the exposed object.
        // attrs (not props) holds all undeclared attributes passed by FileGalUploader,
        // including data-testid, before-upload, on-error, on-success, http-request, etc.
        ElUpload: {
            setup(_, { attrs, slots, expose }) {
                elUploadSpy(attrs, { slots, expose });
                expose({ submit: mockElUploadSubmit, handleRemove: mockElUploadHandleRemove });
                return () =>
                    h("div", attrs, [
                        slots.default ? slots.default() : null,
                        mockUploadSlotFile && slots.file ? slots.file({ file: mockUploadSlotFile }) : null,
                    ]);
            },
        },
    };
});

vi.mock("../../components/ConfigWrapper.vue", () => {
    return {
        default: vi.fn((props, { slots }) => h("div", { ...props, "data-testid": "config-wrapper" }, slots.default ? slots.default() : null)),
    };
});

vi.mock("../../components/FileGalUploader/ImageEditor.vue", () => ({
    default: {
        props: ["file"],
        emits: ["apply", "cancel"],
        setup(props, { emit }) {
            return () =>
                h("div", { "data-testid": "image-editor" }, [
                    h("span", props.file.name),
                    h("button", { onClick: () => emit("apply", mockEditedImage) }, "Apply edited image"),
                    h("button", { onClick: () => emit("cancel") }, "Cancel image editing"),
                ]);
        },
    },
}));

vi.mock("element-plus/es/components/upload/src/ajax.mjs", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        ajaxUpload: vi.fn(),
    };
});

vi.mock("../../helpers/fileGalUploader/getUploadAjaxError", () => {
    return {
        default: vi.fn(),
    };
});

vi.mock("../../helpers/fileGalUploader/handleTikiFeedback", () => {
    return {
        default: vi.fn(),
    };
});

describe("FileGalUploader", () => {
    const consoleErrorSpy = vi.spyOn(console, "error");
    const consoleWarnSpy = vi.spyOn(console, "warn");

    afterEach(() => {
        mockUploadSlotFile = null;
        mockEditedImage = null;
        window.location.search = "";
        vi.clearAllMocks();
        vi.resetModules();
    });

    describe("render tests", () => {
        test("renders correctly without some basic props", () => {
            render(FileGalUploader, {
                props: {
                    maxSize: "100",
                    maxFiles: "10",
                },
            });

            expect(elUploadSpy).toHaveBeenCalledWith(
                expect.objectContaining({
                    action: DEFAULT_ACTION_URL,
                    drag: true,
                    multiple: true,
                    limit: 10,
                    "auto-upload": false,
                    method: "POST",
                    headers: { accept: "application/json" },
                    "http-request": expect.any(Function),
                }),
                expect.any(Object)
            );

            const configWrapper = screen.getByTestId("config-wrapper");
            expect(configWrapper).to.exist;
            const uploadElement = within(configWrapper).getByTestId(DATA_TEST_ID.UPLOAD_ELEMENT);
            expect(uploadElement).to.exist;
            expect(within(uploadElement).getByTestId(DATA_TEST_ID.UPLOAD_ICON)).to.exist;
            expect(within(uploadElement).getByTestId(DATA_TEST_ID.UPLOAD_TEXT)).to.exist;
            expect(screen.getByTestId(DATA_TEST_ID.SUBMIT_BUTTON).textContent).toBe("Upload");

            expect(consoleErrorSpy).not.toHaveBeenCalled();
            expect(consoleWarnSpy).not.toHaveBeenCalled();
        });

        test("renders correctly with some advanced props", () => {
            const givenProps = {
                accept: "image/*",
                maxFiles: "1",
                maxSize: "50",
                vimeoUrl: "http://foo/bar",
                language: "en",
            };

            render(FileGalUploader, { props: givenProps });

            expect(ConfigWrapper).toHaveBeenCalledWith(
                {
                    language: givenProps.language,
                },
                expect.any(Object)
            );

            expect(elUploadSpy).toHaveBeenCalledWith(
                expect.objectContaining({
                    accept: givenProps.accept,
                    action: givenProps.vimeoUrl,
                    method: "PUT",
                    limit: 1,
                }),
                expect.any(Object)
            );

            expect(consoleErrorSpy).not.toHaveBeenCalled();
            expect(consoleWarnSpy).not.toHaveBeenCalled();
        });

        test("renders correctly when inserting a file into the text editor", async () => {
            window.location.search = "?filegals_manager=editwiki";
            render(FileGalUploader, {
                props: {
                    maxSize: "100",
                    maxFiles: "10",
                },
            });

            await waitFor(() => {
                expect(screen.getByTestId(DATA_TEST_ID.SUBMIT_BUTTON).textContent).toBe("Insert");
            });
        });
    });

    describe("action tests", () => {
        test("show an error message when the file size exceeds the maximum size", async () => {
            const elMessage = vi.spyOn(ElMessage, "error");
            const givenFile = new File(["foo"], "foo.txt", { type: "text/plain" });

            const givenProps = {
                maxSize: "1",
                maxFiles: "10",
            };

            render(FileGalUploader, { props: givenProps });

            const uploadValidation = elUploadSpy.mock.calls[0][0]["before-upload"](givenFile);

            await waitFor(() => {
                expect(elMessage).toHaveBeenCalledWith(`File size cannot exceed ${Number(givenProps.maxSize) / 1000}KB`);
            });

            expect(uploadValidation).toBe(false);
        });

        test("edits and removes an image before upload", async () => {
            const rawFile = new File(["original"], "photo.png", { type: "image/png" });
            mockUploadSlotFile = { name: rawFile.name, raw: rawFile, status: "ready", uid: 1 };
            mockEditedImage = new File(["edited"], "photo.png", { type: "image/png" });

            render(FileGalUploader, {
                props: {
                    maxSize: "100",
                    maxFiles: "1",
                },
            });

            await screen.getByRole("button", { name: "Edit photo.png" }).click();
            await (await screen.findByRole("button", { name: "Apply edited image" })).click();

            expect(mockUploadSlotFile.raw).toBe(mockEditedImage);
            expect(mockUploadSlotFile.raw.uid).toBe(1);
            expect(mockUploadSlotFile.size).toBe(mockEditedImage.size);
            expect(screen.queryByTestId("image-editor")).toBeNull();

            await screen.getByRole("button", { name: "Remove photo.png" }).click();
            expect(mockElUploadHandleRemove).toHaveBeenCalledWith(mockUploadSlotFile);
        });

        test("correctly executes the http request function", async () => {
            const givenFile = new File(["foo"], "foo.txt", { type: "text/plain" });

            const givenProps = {
                maxSize: "100",
                maxFiles: "10",
            };

            render(FileGalUploader, { props: givenProps });

            const httpRequestFunction = elUploadSpy.mock.calls[0][0]["http-request"];

            const mockOption = {
                action: DEFAULT_ACTION_URL,
                method: "POST",
                data: {},
                filename: "file",
                file: givenFile,
                onProgress: vi.fn(),
                onError: vi.fn(),
                onSuccess: vi.fn(),
            };

            const mockXMLHttpRequestInstance = new XMLHttpRequest();
            vi.spyOn(AjaxUploadHelpers, "ajaxUpload").mockReturnValueOnce(mockXMLHttpRequestInstance);

            const alteredOnErrorSpy = vi.spyOn(mockOption, "onError");

            const mockUploadAjaxErrorInstance = { error: "mock error" };
            getUploadAjaxError.mockReturnValueOnce(mockUploadAjaxErrorInstance);

            httpRequestFunction(mockOption);

            expect(AjaxUploadHelpers.ajaxUpload).toHaveBeenCalledWith(mockOption);

            mockOption.onError();
            await waitFor(() => {
                expect(alteredOnErrorSpy).toHaveBeenCalledWith(mockUploadAjaxErrorInstance);
            });
            expect(getUploadAjaxError).toHaveBeenCalledWith(mockOption, mockXMLHttpRequestInstance);
            expect(handleTikiFeedback).toHaveBeenCalledWith(mockXMLHttpRequestInstance);
        });

        test("show an error message when the upload fails", async () => {
            const givenProps = {
                maxSize: "1",
                maxFiles: "10",
            };

            render(FileGalUploader, { props: givenProps });

            elUploadSpy.mock.calls[0][0]["on-error"]({ message: "foo" });

            await waitFor(() => {
                expect(ElMessage.error).toHaveBeenCalledWith("foo");
            });
        });

        test("show a success message when the upload is successful", async () => {
            window.location.search = "";
            const elMessage = vi.spyOn(ElMessage, "success");
            const givenProps = {
                maxSize: "100",
                maxFiles: "10",
            };

            render(FileGalUploader, { props: givenProps });

            elUploadSpy.mock.calls[0][0]["on-success"]({}, { name: "foo" });

            await waitFor(() => {
                expect(elMessage).toHaveBeenCalledWith("foo uploaded successfully");
            });
        });

        test("successfully inserts the file into the text editor when the file is uploaded", async () => {
            window.location.search = "?filegals_manager=editwiki";
            window.opener = {
                insertAt: vi.fn(),
            };
            window.tikiCloseFileGalleryManagerWindow = vi.fn();
            const givenProps = {
                maxSize: "100",
                maxFiles: "10",
            };

            render(FileGalUploader, { props: givenProps });

            elUploadSpy.mock.calls[0][0]["on-success"]({ syntax: "syntax mock" }, { name: "foo" });

            await waitFor(() => {
                expect(window.opener.insertAt).toHaveBeenCalledWith("editwiki", "syntax mock", false, false, true);
            });
            expect(window.tikiCloseFileGalleryManagerWindow).toHaveBeenCalled();
        });

        test("calls the vimeo upload callback when the file is uploaded for vimeo uploads", async () => {
            window.location.search = "";
            window.completeVimeoUpload = vi.fn();
            const givenProps = {
                maxSize: "100",
                maxFiles: "10",
                vimeoUrl: "http://foo/bar",
            };

            render(FileGalUploader, { props: givenProps });

            elUploadSpy.mock.calls[0][0]["on-success"]({}, { name: "foo" });

            await waitFor(() => {
                expect(window.completeVimeoUpload).toHaveBeenCalledWith("foo");
            });
        });

        test("clicking the submit button triggers the upload", async () => {
            render(FileGalUploader, {
                props: {
                    maxSize: "100",
                    maxFiles: "10",
                },
            });

            const submitButton = screen.getByTestId(DATA_TEST_ID.SUBMIT_BUTTON);
            await submitButton.click();

            expect(mockElUploadSubmit).toHaveBeenCalled();
        });
    });
});
