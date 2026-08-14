import { afterEach, beforeEach, describe, expect, test, vi } from "vitest";
import { getExtensionFromUrl, sanitizeColor, setupLighting } from "../model3dviewer_helper.js";

describe("model3dviewer_helper", () => {
    afterEach(() => {
        vi.resetAllMocks();
        vi.clearAllMocks();
    });

    describe("getExtensionFromUrl", () => {
        test("extracts extension from filename query param", () => {
            expect(getExtensionFromUrl("http://localhost/tiki-download_file.php?fileId=5&display&filename=robot.glb")).toBe("glb");
        });

        test("extracts extension from filename param with multiple dots", () => {
            expect(getExtensionFromUrl("http://localhost/download?filename=my.model.v2.stl")).toBe("stl");
        });

        test("extracts extension from pathname when no filename param", () => {
            expect(getExtensionFromUrl("https://example.com/models/fox.gltf")).toBe("gltf");
        });

        test("returns lowercase extension", () => {
            expect(getExtensionFromUrl("https://example.com/Model.FBX")).toBe("fbx");
        });

        test("returns null for URL with no extension", () => {
            expect(getExtensionFromUrl("https://example.com/model")).toBeNull();
        });

        test("handles all supported formats", () => {
            const formats = ["glb", "gltf", "stl", "obj", "fbx", "dae", "ply", "3ds"];
            for (const fmt of formats) {
                expect(getExtensionFromUrl(`https://example.com/model.${fmt}`)).toBe(fmt);
            }
        });
    });

    describe("sanitizeColor", () => {
        test("returns valid 6-digit hex color as-is", () => {
            expect(sanitizeColor("#ff0000")).toBe("#ff0000");
        });

        test("expands 3-digit hex to 6-digit", () => {
            expect(sanitizeColor("#abc")).toBe("#aabbcc");
        });

        test("adds missing # to hex string", () => {
            expect(sanitizeColor("ff0000")).toBe("#ff0000");
        });

        test("adds # and expands 3-digit hex", () => {
            expect(sanitizeColor("abc")).toBe("#aabbcc");
        });

        test("truncates 8-digit hex to 6-digit", () => {
            expect(sanitizeColor("#ff0000ff")).toBe("#ff0000");
        });

        test("returns fallback for non-string input", () => {
            expect(sanitizeColor(null)).toBe("#ffffff");
            expect(sanitizeColor(undefined)).toBe("#ffffff");
            expect(sanitizeColor(123)).toBe("#ffffff");
        });

        test("returns empty string for empty input", () => {
            expect(sanitizeColor("")).toBe("");
        });

        test("uses custom fallback for non-string input", () => {
            expect(sanitizeColor(null, "#000000")).toBe("#000000");
        });

        test("trims whitespace", () => {
            expect(sanitizeColor("  #ff0000  ")).toBe("#ff0000");
        });

        test("is case-insensitive", () => {
            expect(sanitizeColor("#AABB00")).toBe("#aabb00");
        });
    });

    describe("setupLighting", () => {
        let scene;

        beforeEach(() => {
            const children = [];
            scene = {
                traverse: vi.fn((fn) => {
                    [...children].forEach(fn);
                }),
                add: vi.fn((obj) => children.push(obj)),
                remove: vi.fn((obj) => {
                    const idx = children.indexOf(obj);
                    if (idx !== -1) children.splice(idx, 1);
                }),
            };
        });

        test("adds lights for default (empty) light type", () => {
            setupLighting(scene, false, "");
            expect(scene.add).toHaveBeenCalled();
            expect(scene.add.mock.calls.length).toBeGreaterThanOrEqual(3);
        });

        test("adds lights for studio preset", () => {
            setupLighting(scene, false, "studio");
            expect(scene.add).toHaveBeenCalled();
            expect(scene.add.mock.calls.length).toBeGreaterThanOrEqual(4);
        });

        test("adds lights for rembrandt preset", () => {
            setupLighting(scene, false, "rembrandt");
            expect(scene.add).toHaveBeenCalled();
        });

        test("adds lights for portrait preset", () => {
            setupLighting(scene, false, "portrait");
            expect(scene.add).toHaveBeenCalled();
        });

        test("adds lights for soft preset", () => {
            setupLighting(scene, false, "soft");
            expect(scene.add).toHaveBeenCalled();
        });

        test("falls back to hemisphere light for unknown preset", () => {
            setupLighting(scene, false, "nonexistent");
            expect(scene.add).toHaveBeenCalled();
        });

        test("enables shadow casting when shadow is true", () => {
            setupLighting(scene, true, "studio");
            const addedObjects = scene.add.mock.calls.map((c) => c[0]);
            const shadowCasters = addedObjects.filter((obj) => obj.castShadow === true);
            expect(shadowCasters.length).toBeGreaterThanOrEqual(1);
        });
    });
});
