import { fireEvent, render, screen, waitFor } from "@testing-library/vue";
import { beforeEach, describe, expect, test, vi } from "vitest";
import ImageEditor from "../../components/FileGalUploader/ImageEditor.vue";

const mocks = vi.hoisted(() => {
    const image = {
        style: {},
        $ready: vi.fn(() => Promise.resolve()),
        $getTransform: vi.fn(() => [0.25, 0, 0, 0.25, 0, 0]),
        $setTransform: vi.fn(),
        $rotate: vi.fn(),
        $scale: vi.fn(),
        $center: vi.fn(),
        getBoundingClientRect: vi.fn(() => ({ left: 100, top: 75, width: 300, height: 150 })),
    };
    const selection = { x: 50, y: 50, width: 300, height: 150, $change: vi.fn(), $toCanvas: vi.fn() };
    const cropHandle = { action: "select" };
    const cropShade = { hidden: true };
    const canvas = {
        style: {},
        getBoundingClientRect: vi.fn(() => ({ left: 50, top: 25, width: 600, height: 400 })),
        querySelector: vi.fn((selector) => (selector === "cropper-shade" ? cropShade : cropHandle)),
    };
    const destroy = vi.fn();
    const modal = { show: vi.fn(), hide: vi.fn(), dispose: vi.fn() };
    const Cropper = vi.fn(function () {
        return {
            destroy,
            getCropperCanvas: () => canvas,
            getCropperImage: () => image,
            getCropperSelection: () => selection,
        };
    });
    return { Cropper, canvas, cropHandle, cropShade, destroy, image, modal, selection };
});

vi.mock("cropperjs", () => ({
    default: mocks.Cropper,
}));

describe("ImageEditor", () => {
    const file = new File(["image"], "photo.png", { type: "image/png" });

    beforeEach(() => {
        vi.clearAllMocks();
        mocks.canvas.style = {};
        mocks.image.style = {};
        mocks.cropHandle.action = "select";
        mocks.cropShade.hidden = false;
        Object.assign(mocks.selection, { x: 50, y: 50, width: 300, height: 150 });
        mocks.selection.hidden = false;
        URL.createObjectURL = vi.fn(() => "blob:image");
        URL.revokeObjectURL = vi.fn();
        window.bootstrap = {
            Modal: {
                getOrCreateInstance: vi.fn((element) => {
                    mocks.modal.show.mockImplementation(() => {
                        element.style.display = "block";
                        element.removeAttribute("aria-hidden");
                        element.dispatchEvent(new Event("shown.bs.modal"));
                    });
                    mocks.modal.hide.mockImplementation(() => element.dispatchEvent(new Event("hidden.bs.modal")));
                    return mocks.modal;
                }),
            },
        };
    });

    const renderEditor = async () => {
        const result = render(ImageEditor, { props: { file } });
        await fireEvent.load(await screen.findByAltText("Edit photo.png"));
        await waitFor(() => expect(mocks.selection.$change).toHaveBeenCalled());
        return result;
    };

    test("uses Cropper.js for image transforms", async () => {
        await renderEditor();

        expect(mocks.selection.hidden).toBe(true);
        expect(mocks.cropHandle.action).toBe("none");
        expect(mocks.cropShade.hidden).toBe(true);
        await fireEvent.click(screen.getByRole("button", { name: "Crop" }));
        expect(mocks.selection.hidden).toBe(false);
        expect(mocks.cropHandle.action).toBe("select");
        expect(mocks.cropShade.hidden).toBe(false);
        Object.assign(mocks.selection, { x: 75, y: 75, width: 200, height: 100 });
        await fireEvent.click(screen.getByRole("button", { name: "Apply crop" }));
        expect(mocks.selection.hidden).toBe(true);
        expect(mocks.cropHandle.action).toBe("none");
        expect(mocks.cropShade.hidden).toBe(true);
        expect(mocks.canvas.style.clipPath).toBe("inset(75px 325px 225px 75px)");
        await fireEvent.click(screen.getByRole("button", { name: "Edit crop" }));
        expect(mocks.canvas.style.clipPath).toBe("");
        await fireEvent.click(screen.getByRole("button", { name: "Cancel crop" }));
        expect(mocks.canvas.style.clipPath).toBe("inset(75px 325px 225px 75px)");
        await fireEvent.click(screen.getByRole("button", { name: "Rotate left" }));
        await fireEvent.click(screen.getByRole("button", { name: "Flip horizontal" }));

        expect(mocks.Cropper).toHaveBeenCalled();
        expect(mocks.image.$rotate).toHaveBeenCalledWith("-90deg");
        expect(mocks.image.$scale).toHaveBeenCalledWith(-1, 1);
    });

    test("exports the Cropper.js selection", async () => {
        const blob = new Blob(["edited"], { type: "image/png" });
        const canvas = { toBlob: vi.fn((callback) => callback(blob)) };
        mocks.selection.$toCanvas.mockResolvedValue(canvas);
        const { emitted } = await renderEditor();

        await fireEvent.click(screen.getByRole("button", { name: "Grayscale" }));
        await fireEvent.click(screen.getByRole("button", { name: "Apply changes" }));

        await waitFor(() => expect(emitted().apply).toHaveLength(1));
        const exportOptions = mocks.selection.$toCanvas.mock.calls[0][0];
        expect(exportOptions.width).toBe(1200);
        const context = {};
        exportOptions.beforeDraw(context, {});
        expect(context.filter).toBe("grayscale(100%)");
        expect(canvas.toBlob).toHaveBeenCalledWith(expect.any(Function), "image/png", 0.92);
        expect(emitted().apply[0][0].name).toBe("photo.png");
    });

    test("destroys Cropper.js and revokes the object URL", async () => {
        const { unmount } = await renderEditor();

        unmount();

        expect(mocks.destroy).toHaveBeenCalled();
        expect(mocks.modal.dispose).toHaveBeenCalled();
        expect(URL.revokeObjectURL).toHaveBeenCalledWith("blob:image");
    });
});
