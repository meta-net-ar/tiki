/**
 * @vitest-environment jsdom
 */

import { describe, test, expect, vi, beforeEach } from "vitest";

import imageCompression from "browser-image-compression";

import { compress, compressMultiple, compressFileList, isSupported, isImage, normalizeResizeDimension } from "../imageCompressor";

vi.mock("browser-image-compression", () => ({
    default: vi.fn(),
}));

describe("normalizeResizeDimension", () => {
    test("returns fallback for invalid values", () => {
        expect(
            normalizeResizeDimension("abc", {
                fallback: 500,
            })
        ).toBe(500);
    });

    test("returns 0 for negative values", () => {
        expect(normalizeResizeDimension(-100)).toBe(0);
    });

    test("returns 0 for zero", () => {
        expect(normalizeResizeDimension(0)).toBe(0);
    });

    test("enforces minimum of 100", () => {
        expect(normalizeResizeDimension(50)).toBe(100);
    });

    test("returns value when >= 100", () => {
        expect(normalizeResizeDimension(500)).toBe(500);
    });

    test("applies max constraint", () => {
        expect(
            normalizeResizeDimension(5000, {
                max: 1000,
            })
        ).toBe(1000);
    });
});

describe("isSupported", () => {
    test("returns true for jpeg", () => {
        expect(
            isSupported({
                type: "image/jpeg",
            })
        ).toBe(true);
    });

    test("returns true for png", () => {
        expect(
            isSupported({
                type: "image/png",
            })
        ).toBe(true);
    });

    test("returns false for pdf", () => {
        expect(
            isSupported({
                type: "application/pdf",
            })
        ).toBe(false);
    });

    test("uses mimeType override", () => {
        expect(isSupported({ type: "application/pdf" }, "image/jpeg")).toBe(true);
    });
});

describe("compress", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    test("fails when file is missing", async () => {
        const result = await compress(null);

        expect(result.success).toBe(false);
        expect(result.error).toContain("File blob is required");
    });

    test("skips unsupported files", async () => {
        const file = new File(["test"], "test.pdf", {
            type: "application/pdf",
        });

        const result = await compress(file);

        expect(result.skipped).toBe(true);
        expect(result.formatted).toBe("unsupported");
    });
});

describe("compressMultiple", () => {
    test("throws when input is not an array", async () => {
        await expect(compressMultiple(null)).rejects.toThrow("Files must be an array");
    });

    test("returns one result per file", async () => {
        const pdf = new File(["x"], "file.pdf", {
            type: "application/pdf",
        });

        const results = await compressMultiple([pdf]);

        expect(results).toHaveLength(1);
        expect(results[0].skipped).toBe(true);
    });
});

describe("compressFileList", () => {
    beforeEach(() => {
        global.DataTransfer = class {
            constructor() {
                this.items = {
                    list: [],
                    add: (file) => {
                        this.items.list.push(file);
                    },
                };
            }
        };
    });

    test("returns empty DataTransfer for empty input", async () => {
        const result = await compressFileList([]);

        expect(result).toBeInstanceOf(DataTransfer);
    });

    test("keeps unsupported files unchanged", async () => {
        const file = new File(["test"], "file.pdf", {
            type: "application/pdf",
        });

        const result = await compressFileList([file]);

        expect(result.items.list).toHaveLength(1);
    });

    test("skips files smaller than maxSize threshold", async () => {
        const file = new File([new Uint8Array(1000)], "small.jpg", {
            type: "image/jpeg",
        });

        const result = await compressFileList([file], {
            maxSize: 10,
        });

        expect(result.items.list).toHaveLength(1);
    });
});
