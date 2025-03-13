import { render, screen, waitFor, within } from "@testing-library/vue";
import { afterEach, describe, expect, test, vi } from "vitest";
import FileGalUploader, { DATA_TEST_ID, DEFAULT_ACTION_URL } from "../../components/FileGalUploader/FileGalUploader.vue";
import { h } from "vue";
import { ElMessage, ElUpload } from "element-plus";
import ConfigWrapper from "../../components/ConfigWrapper.vue";

vi.mock("element-plus", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        ElUpload: vi.fn((props, { slots }) => h("div", props, slots.default ? slots.default() : null)),
    };
});

vi.mock("../../components/ConfigWrapper.vue", () => {
    return {
        default: vi.fn((props, { slots }) => h("div", { ...props, "data-testid": "config-wrapper" }, slots.default ? slots.default() : null)),
    };
});

describe("FileGalUploader", () => {
    const consoleErrorSpy = vi.spyOn(console, "error");
    const consoleWarnSpy = vi.spyOn(console, "warn");

    afterEach(() => {
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

            expect(ElUpload).toHaveBeenCalledWith(
                expect.objectContaining({
                    action: DEFAULT_ACTION_URL,
                    drag: true,
                    multiple: true,
                    limit: 10,
                    "auto-upload": false,
                    method: "POST",
                    headers: { accept: "application/json" },
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

            expect(ElUpload).toHaveBeenCalledWith(
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

            const uploadValidation = ElUpload.mock.calls[0][0]["before-upload"](givenFile);

            await waitFor(() => {
                expect(elMessage).toHaveBeenCalledWith(`File size cannot exceed ${Number(givenProps.maxSize) / 1000}KB`);
            });

            expect(uploadValidation).toBe(false);
        });

        test("show an error message when the upload fails", async () => {
            const givenProps = {
                maxSize: "1",
                maxFiles: "10",
            };

            render(FileGalUploader, { props: givenProps });

            ElUpload.mock.calls[0][0]["on-error"]({ message: "foo" });

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

            ElUpload.mock.calls[0][0]["on-success"]({}, { name: "foo" });

            await waitFor(() => {
                expect(elMessage).toHaveBeenCalledWith("foo uploaded successfully");
            });
        });

        test("successfully inserts the file into the text editor when the file is uploaded", async () => {
            window.location.search = "?filegals_manager=editwiki";
            window.opener = {
                insertAt: vi.fn(),
            };
            window.checkClose = vi.fn();
            const givenProps = {
                maxSize: "100",
                maxFiles: "10",
            };

            render(FileGalUploader, { props: givenProps });

            ElUpload.mock.calls[0][0]["on-success"]({ syntax: "syntax mock" }, { name: "foo" });

            await waitFor(() => {
                expect(window.opener.insertAt).toHaveBeenCalledWith("editwiki", "syntax mock", false, false, true);
            });
            expect(window.checkClose).toHaveBeenCalled();
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

            ElUpload.mock.calls[0][0]["on-success"]({}, { name: "foo" });

            await waitFor(() => {
                expect(window.completeVimeoUpload).toHaveBeenCalledWith("foo");
            });
        });

        test("clicking the submit button triggers the upload", async () => {
            ElUpload = {
                setup(props, { slots }) {
                    return () => h("div", props, slots.default());
                },
                methods: {
                    submit: vi.fn(),
                },
                exposes: ["submit"],
            };

            render(FileGalUploader, {
                props: {
                    maxSize: "100",
                    maxFiles: "10",
                },
            });

            const submitButton = screen.getByTestId(DATA_TEST_ID.SUBMIT_BUTTON);
            await submitButton.click();

            expect(ElUpload.methods.submit).toHaveBeenCalled();
        });
    });
});
