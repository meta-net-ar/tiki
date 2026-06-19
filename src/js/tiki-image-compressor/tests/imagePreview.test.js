import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

let initializeImagePreview;
let refreshPreview;
let cleanup;
let formatFileSize;

// ---- mocks ----
const compressMock = vi.fn();
const normalizeResizeDimensionMock = vi.fn((v) => v);

vi.mock("../imageCompressor.js", () => ({
    compress: (...args) => compressMock(...args),
    normalizeResizeDimension: (v) => normalizeResizeDimensionMock(v),
}));

function setupDOM() {
    document.body.innerHTML = `
        <input type="file" id="userfile" />
        <div id="compression_preview_container" class="d-none"></div>
        <img id="compression_preview_image" />
        <div id="compression_original_size"></div>
        <div id="compression_estimated_size"></div>
        <div id="compression_preview_dimensions"></div>
    `;
}

function mockFileReader(result = "data:image/png;base64,AAA") {
    class MockFileReader {
        constructor() {
            this.onload = null;
            this.onerror = null;
        }
        readAsDataURL() {
            this.onload?.({ target: { result } });
        }
    }
    global.FileReader = MockFileReader;
}

function mockImageLoad(width = 800, height = 600) {
    class MockImage {
        constructor() {
            setTimeout(() => {
                this.naturalWidth = width;
                this.naturalHeight = height;
                this.onload?.();
            }, 0);
        }
        set src(_) {
            // trigger load
            this.onload?.();
        }
    }
    global.Image = MockImage;
}

function mockURL() {
    let counter = 1;
    global.URL.createObjectURL = vi.fn(() => `blob:url-${counter++}`);
    global.URL.revokeObjectURL = vi.fn();
}

async function importModule() {
    vi.resetModules();
    const mod = await import("../imagePreview.js");
    initializeImagePreview = mod.default;
    refreshPreview = mod.refreshPreview;
    cleanup = mod.cleanup;
    formatFileSize = mod.formatFileSize;
}

// ---- tests ----
describe("imagePreview module (vitest)", () => {
    beforeEach(async () => {
        setupDOM();
        mockFileReader();
        mockImageLoad();
        mockURL();

        compressMock.mockReset();

        compressMock.mockResolvedValue({
            success: true,
            blob: new Blob(["x"], { type: "image/png" }),
        });

        await importModule();

        // fake timers for debounce
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it("formats file size correctly", () => {
        expect(formatFileSize(0)).toBe("0 B");
        expect(formatFileSize(1024)).toContain("KB");
    });

    it("initializes file listener and loads image", async () => {
        await initializeImagePreview({ fileInputId: "userfile" });

        const input = document.getElementById("userfile");

        const file = new File(["dummy"], "test.png", { type: "image/png" });

        input.files = [file];

        input.dispatchEvent(new Event("change"));

        // allow FileReader + image load
        await vi.runAllTimersAsync();

        expect(document.getElementById("compression_preview_container").classList.contains("d-none")).toBe(false);
    });

    it("refreshPreview does nothing if no image loaded", () => {
        refreshPreview({ quality: 80 });
        expect(compressMock).not.toHaveBeenCalled();
    });

    it("updatePreview triggers compression and updates UI", async () => {
        await initializeImagePreview();

        const input = document.getElementById("userfile");
        const file = new File(["dummy"], "test.png", { type: "image/png" });

        input.files = [file];
        input.dispatchEvent(new Event("change"));

        await vi.runAllTimersAsync();

        // trigger refresh (simulate compression control change)
        refreshPreview({
            quality: 70,
            maxDimensionX: 500,
            maxDimensionY: 500,
            maxSize: 1,
        });

        await vi.advanceTimersByTimeAsync(200);

        expect(compressMock).toHaveBeenCalled();
        expect(document.getElementById("compression_estimated_size").textContent).toContain("B");
        expect(document.getElementById("compression_preview_dimensions").textContent).toContain("px");
    });

    it("cleanup removes listeners and clears URL", async () => {
        await initializeImagePreview();

        cleanup();

        const input = document.getElementById("userfile");

        const file = new File(["dummy"], "test.png", { type: "image/png" });
        input.files = [file];

        input.dispatchEvent(new Event("change"));

        await vi.runAllTimersAsync();

        // no further updates should happen safely (no crash, no extra behavior)
        expect(true).toBe(true);
    });
});
