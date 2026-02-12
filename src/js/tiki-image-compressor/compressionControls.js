/**
 * Compression Controls Module
 *
 * Manages UI controls for image compression settings in tiki-upload.tpl
 * Centralized state management with proper validation, error handling, and performance optimization
 */

// STATE MANAGEMENT - Single source of truth
const STATE = {
    serverDefaults: {},
    current: {},
    isInitialized: false,
    listeners: [],
};

// Validation rules for settings
const VALIDATION = {
    quality: { min: 10, max: 100, type: "number" },
    maxDimension: { min: 100, max: 10000, type: "number" },
    maxSize: { min: 0.1, max: 1000, type: "number" },
    saveOriginal: { type: "enum", values: ["y", "n"] },
    preset: { type: "enum", values: ["slider", "levels"] },
};

/**
 * Validate and sanitize a setting value
 * @param {string} key - Setting name
 * @param {*} value - Value to validate
 * @returns {*} - Validated value or original if invalid
 */
function validateSetting(key, value) {
    const rule = VALIDATION[key];
    if (!rule) return value;

    if (rule.type === "number") {
        const num = parseFloat(value);
        if (isNaN(num)) return STATE.serverDefaults[key];
        if (num < rule.min || num > rule.max) {
            console.warn(`[CompressionUI] ${key} out of range [${rule.min}, ${rule.max}], using default`);
            return STATE.serverDefaults[key];
        }
        return num;
    }

    if (rule.type === "enum") {
        if (!rule.values.includes(value)) {
            console.warn(`[CompressionUI] Invalid ${key} value: ${value}`);
            return STATE.serverDefaults[key];
        }
        return value;
    }

    return value;
}

/**
 * Update state and synchronize with DOM
 * Single function to ensure state and DOM stay in sync
 * @param {Object} updates - Object with setting updates
 * @param {boolean} skipDOMUpdate - Skip DOM updates (for cleanup only)
 */
function updateState(updates, skipDOMUpdate = false) {
    const changes = {};

    for (const [key, value] of Object.entries(updates)) {
        const validated = validateSetting(key, value);
        if (STATE.current[key] !== validated) {
            changes[key] = validated;
            STATE.current[key] = validated;
        }
    }

    if (Object.keys(changes).length === 0) return;

    if (!skipDOMUpdate) {
        updateDOMFromState();
    }
}

/**
 * Reset to server defaults both state and DOM
 */
function resetToDefaults() {
    STATE.current = { ...STATE.serverDefaults };
    updateDOMFromState();
}

/**
 * Batch update all DOM elements from current state
 * Minimizes DOM reflows
 */
function updateDOMFromState() {
    const state = STATE.current;

    // Quality display
    const qualityDisplay = document.getElementById("quality_display_main");
    if (qualityDisplay) {
        qualityDisplay.textContent = state.quality + "%";
    }

    // Quality slider
    const qualitySlider = document.getElementById("compression_quality_main");
    if (qualitySlider && qualitySlider.value != state.quality) {
        qualitySlider.value = state.quality;
    }

    // Max dimension input
    const maxDimInput = document.querySelector("[name='compression_max_dimension']");
    if (maxDimInput && maxDimInput.value != state.maxDimension) {
        maxDimInput.value = state.maxDimension;
    }

    // Max size input
    const maxSizeInput = document.querySelector("[name='compression_max_size']");
    if (maxSizeInput && maxSizeInput.value != state.maxSize) {
        maxSizeInput.value = state.maxSize;
    }

    // Save original checkbox
    const saveOrigChk = document.getElementById("compression_save_original_main");
    if (saveOrigChk && saveOrigChk.checked != (state.saveOriginal === "y")) {
        saveOrigChk.checked = state.saveOriginal === "y";
    }

    // Default summary display
    updateDefaultSummary(state);
}

/**
 * Update default summary display with current settings
 */
function updateDefaultSummary(settings) {
    const updates = [
        ["default_max_size", settings.maxDimension + " px"],
        ["default_min_file", settings.maxSize + " MB"],
        ["default_save_original", settings.saveOriginal === "y" ? "Yes" : "No"],
        ["default_unzip", "No"],
    ];

    updates.forEach(([id, value]) => {
        const el = document.getElementById(id);
        if (el && el.textContent !== value) {
            el.textContent = value;
        }
    });
}

/**
 * Attach event listener with tracking for cleanup
 */
function attachListener(element, event, handler, options = false) {
    if (!element) return;
    element.addEventListener(event, handler, options);
    STATE.listeners.push({ element, event, handler, options });
}

/**
 * Remove all attached event listeners
 */
function cleanup() {
    STATE.listeners.forEach(({ element, event, handler, options }) => {
        element.removeEventListener(event, handler, options);
    });
    STATE.listeners = [];
}

/**
 * Ensure elements exist in DOM, wait if needed
 */
async function waitForElements(selectors) {
    const elements = {};
    for (const [name, selector] of Object.entries(selectors)) {
        let el = document.querySelector(selector);
        let attempts = 0;
        while (!el && attempts < 10) {
            await new Promise((resolve) => setTimeout(resolve, 100));
            el = document.querySelector(selector);
            attempts++;
        }
        if (el) elements[name] = el;
    }
    return elements;
}

/**
 * Initialize all event listeners
 */
async function initializeEventListeners() {
    const elements = await waitForElements({
        qualitySlider: "#compression_quality_main",
        qualityDisplay: "#quality_display_main",
        maxDimInput: "[name='compression_max_dimension']",
        maxSizeInput: "[name='compression_max_size']",
        saveOrigChk: "#compression_save_original_main",
        toggleBtn: "#customize-compression-toggle",
        backBtn: "#compression-back-to-default",
        maxSizeMsgEl: "#compression_max_dimension_msg",
    });

    if (elements.qualitySlider) {
        const handler = (e) => {
            const value = parseInt(e.target.value, 10);
            updateState({ quality: value });
        };
        attachListener(elements.qualitySlider, "input", handler);
    }

    if (elements.maxDimInput) {
        const handler = (e) => {
            const msgEl = elements.maxSizeMsgEl;
            let value = parseInt(e.target.value, 10);
            const min = VALIDATION.maxDimension.min;
            const max = VALIDATION.maxDimension.max;

            if (isNaN(value)) {
                msgEl.textContent = "";
                return;
            }

            let clamped = value;
            if (value < min) {
                clamped = min;
                msgEl.textContent = `Too low, will use ${min} px internally.`;
                msgEl.className = "text-success small mt-1";
            } else if (value > max) {
                clamped = max;
                msgEl.textContent = `Too high, will use ${max} px internally.`;
                msgEl.className = "text-success small mt-1";
            } else {
                msgEl.textContent = "";
            }

            STATE.current.maxDimension = clamped;
        };
        attachListener(elements.maxDimInput, "input", handler);
    }

    if (elements.maxSizeInput) {
        const handler = (e) => {
            const value = parseFloat(e.target.value) || 0;
            updateState({ maxSize: value });
        };
        attachListener(elements.maxSizeInput, "input", handler);
    }

    if (elements.saveOrigChk) {
        const handler = (e) => {
            updateState({ saveOriginal: e.target.checked ? "y" : "n" });
        };
        attachListener(elements.saveOrigChk, "change", handler);
    }

    if (elements.toggleBtn) {
        const handler = (e) => {
            e.preventDefault();
            toggleCustomUI(true);
        };
        attachListener(elements.toggleBtn, "click", handler);
    }

    if (elements.backBtn) {
        const handler = (e) => {
            e.preventDefault();
            toggleCustomUI(false);
        };
        attachListener(elements.backBtn, "click", handler);
    }
}

/**
 * Toggle between default summary and custom controls
 */
function toggleCustomUI(showCustom) {
    const defaultSummary = document.getElementById("compression_default_summary");
    const customControls = document.getElementById("compression_custom_controls");

    if (showCustom) {
        // Show custom controls
        if (defaultSummary) defaultSummary.classList.add("d-none");
        if (customControls) customControls.classList.remove("d-none");
    } else {
        // Show default summary and reset to defaults
        if (defaultSummary) defaultSummary.classList.remove("d-none");
        if (customControls) customControls.classList.add("d-none");
        resetToDefaults();
    }
}

/**
 * Initialize compression controls with server settings
 * @param {Object} config - Server preferences
 */
async function initializeCompressionUI(config = {}) {
    if (STATE.isInitialized) {
        return;
    }

    try {
        STATE.serverDefaults = {
            quality: validateSetting("quality", config.quality || 65),
            maxDimension: validateSetting("maxDimension", config.maxDimension || 1600),
            maxSize: validateSetting("maxSize", config.maxSize || 1),
            saveOriginal: validateSetting("saveOriginal", config.saveOriginal || "n"),
            preset: validateSetting("preset", config.preset || "slider"),
        };

        STATE.current = { ...STATE.serverDefaults };

        await waitForElements({
            summary: "#compression_default_summary",
            controls: "#compression_custom_controls",
        });

        updateDOMFromState();

        await initializeEventListeners();

        STATE.isInitialized = true;
    } catch (err) {
        console.error("[CompressionUI] Initialization failed:", err);
        STATE.isInitialized = false;
    }
}

/**
 * Return current live compression settings
 */
function getCompressionSettings() {
    return { ...STATE };
}

export default initializeCompressionUI;
export { cleanup, getCompressionSettings };
