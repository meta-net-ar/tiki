/**
 * @vitest-environment happy-dom
 */

import { afterEach, beforeEach, describe, expect, test, vi } from "vitest";
import initializeCompressionUI, { cleanup, getCompressionSettings } from "../compressionControls";
import initializeImagePreview, { refreshPreview } from "../imagePreview";
import { normalizeResizeDimension } from "../imageCompressor";

vi.mock("../imagePreview", () => ({
    default: vi.fn().mockResolvedValue(),
    refreshPreview: vi.fn(),
}));

vi.mock("../imageCompressor", () => ({
    normalizeResizeDimension: vi.fn((value) => value),
}));

function setupDOM() {
    document.body.innerHTML = `
        <span id="quality_display_main"></span>

        <input
            id="compression_quality_main"
            type="range"
            value="65"
        >

        <input
            name="compression_max_dimension_x"
            value="0"
        >

        <input
            name="compression_max_dimension_y"
            value="0"
        >

        <input
            name="compression_max_size"
            value="1"
        >

        <input
            id="compression_save_original_main"
            type="checkbox"
        >

        <div id="compression_default_summary"></div>

        <div
            id="compression_custom_controls"
            class="d-none"
        ></div>

        <button id="customize-compression-toggle"></button>
        <button id="compression-back-to-default"></button>

        <span id="default_max_size_x"></span>
        <span id="default_max_size_y"></span>
        <span id="default_min_file"></span>
        <span id="default_save_original"></span>
        <span id="default_unzip"></span>

        <span id="compression_max_dimension_x_msg"></span>
        <span id="compression_max_dimension_y_msg"></span>
    `;
}

describe("compressionControls", () => {
    beforeEach(() => {
        setupDOM();
        vi.clearAllMocks();
    });

    afterEach(() => {
        cleanup();
    });

    test("initializes UI from config", async () => {
        await initializeCompressionUI({
            quality: 80,
            maxDimensionX: 1200,
            maxDimensionY: 800,
            maxSize: 5,
            saveOriginal: "y",
        });

        expect(document.getElementById("quality_display_main").textContent).toBe("80%");

        expect(document.getElementById("compression_quality_main").value).toBe("80");

        expect(document.querySelector("[name='compression_max_dimension_x']").value).toBe("1200");

        expect(document.querySelector("[name='compression_max_dimension_y']").value).toBe("800");

        expect(document.querySelector("[name='compression_max_size']").value).toBe("5");

        expect(document.getElementById("compression_save_original_main").checked).toBe(true);
    });

    test("initializes image preview", async () => {
        await initializeCompressionUI();

        expect(initializeImagePreview).toHaveBeenCalledTimes(1);
    });

    test("updates quality when slider changes", async () => {
        await initializeCompressionUI({
            quality: 65,
        });

        const slider = document.getElementById("compression_quality_main");

        slider.value = "90";
        slider.dispatchEvent(new Event("input"));

        expect(document.getElementById("quality_display_main").textContent).toBe("90%");

        expect(refreshPreview).toHaveBeenCalled();
    });

    test("updates maxDimensionX", async () => {
        await initializeCompressionUI();

        const input = document.querySelector("[name='compression_max_dimension_x']");

        input.value = "500";
        input.dispatchEvent(new Event("input"));

        expect(normalizeResizeDimension).toHaveBeenCalled();
        expect(refreshPreview).toHaveBeenCalled();
    });

    test("shows low dimension warning for X", async () => {
        await initializeCompressionUI();

        const input = document.querySelector("[name='compression_max_dimension_x']");

        input.value = "50";
        input.dispatchEvent(new Event("input"));

        expect(document.getElementById("compression_max_dimension_x_msg").textContent).toContain("Too low");
    });

    test("updates maxDimensionY", async () => {
        await initializeCompressionUI();

        const input = document.querySelector("[name='compression_max_dimension_y']");

        input.value = "500";
        input.dispatchEvent(new Event("input"));

        expect(normalizeResizeDimension).toHaveBeenCalled();
        expect(refreshPreview).toHaveBeenCalled();
    });

    test("shows low dimension warning for Y", async () => {
        await initializeCompressionUI();

        const input = document.querySelector("[name='compression_max_dimension_y']");

        input.value = "50";
        input.dispatchEvent(new Event("input"));

        expect(document.getElementById("compression_max_dimension_y_msg").textContent).toContain("Too low");
    });

    test("updates maxSize", async () => {
        await initializeCompressionUI();

        const input = document.querySelector("[name='compression_max_size']");

        input.value = "10";
        input.dispatchEvent(new Event("input"));

        const state = getCompressionSettings();

        expect(state.current.maxSize).toBe(10);
    });

    test("updates saveOriginal checkbox", async () => {
        await initializeCompressionUI();

        const checkbox = document.getElementById("compression_save_original_main");

        checkbox.checked = true;
        checkbox.dispatchEvent(new Event("change"));

        const state = getCompressionSettings();

        expect(state.current.saveOriginal).toBe("y");
    });

    test("shows custom controls", async () => {
        await initializeCompressionUI();

        document.getElementById("customize-compression-toggle").click();

        expect(document.getElementById("compression_default_summary").classList.contains("d-none")).toBe(true);

        expect(document.getElementById("compression_custom_controls").classList.contains("d-none")).toBe(false);
    });

    test("returns to summary view", async () => {
        await initializeCompressionUI();

        document.getElementById("customize-compression-toggle").click();

        document.getElementById("compression-back-to-default").click();

        expect(document.getElementById("compression_default_summary").classList.contains("d-none")).toBe(false);

        expect(document.getElementById("compression_custom_controls").classList.contains("d-none")).toBe(true);
    });

    test("resets quality to defaults when returning from custom view", async () => {
        await initializeCompressionUI({
            quality: 65,
        });

        document.getElementById("customize-compression-toggle").click();

        const slider = document.getElementById("compression_quality_main");

        slider.value = "90";
        slider.dispatchEvent(new Event("input"));

        expect(slider.value).toBe("90");

        document.getElementById("compression-back-to-default").click();

        expect(slider.value).toBe("65");
    });
});
