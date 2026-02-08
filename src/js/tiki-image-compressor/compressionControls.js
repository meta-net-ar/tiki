/**
 * Compression Controls Module
 * 
 * Manages UI controls for image compression settings in tiki-upload.tpl
 * Centralized state management with proper validation, error handling, and performance optimization
 */

import { compressFileList } from './imageCompressor.js';

// ============================================================================
// STATE MANAGEMENT - Single source of truth
// ============================================================================

const STATE = {
    // Server defaults - never modified
    serverDefaults: {},
    // Current settings - mix of server defaults and user customizations
    current: {},
    // Track if UI is initialized
    isInitialized: false,
    // Track attached listeners for cleanup
    listeners: [],
};

// Validation rules for settings
const VALIDATION = {
    quality: { min: 10, max: 100, type: 'number' },
    maxDimension: { min: 100, max: 10000, type: 'number' },
    maxSize: { min: 0.1, max: 1000, type: 'number' },
    saveOriginal: { type: 'enum', values: ['y', 'n'] },
    preset: { type: 'enum', values: ['slider', 'levels'] },
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

    if (rule.type === 'number') {
        const num = parseFloat(value);
        if (isNaN(num)) return STATE.serverDefaults[key];
        if (num < rule.min || num > rule.max) {
            console.warn(`[CompressionUI] ${key} out of range [${rule.min}, ${rule.max}], using default`);
            return STATE.serverDefaults[key];
        }
        return num;
    }

    if (rule.type === 'enum') {
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

    // Validate and apply updates
    for (const [key, value] of Object.entries(updates)) {
        const validated = validateSetting(key, value);
        if (STATE.current[key] !== validated) {
            changes[key] = validated;
            STATE.current[key] = validated;
        }
    }

    if (Object.keys(changes).length === 0) return;

    console.log('[CompressionUI] State updated:', changes);

    // Batch update DOM
    if (!skipDOMUpdate) {
        updateDOMFromState();
    }
}

/**
 * Reset to server defaults both state and DOM
 */
function resetToDefaults() {
    STATE.current = { ...STATE.serverDefaults };
    console.log('[CompressionUI] Reset to defaults:', STATE.current);
    updateDOMFromState();
}

/**
 * Batch update all DOM elements from current state
 * Minimizes DOM reflows
 */
function updateDOMFromState() {
    const state = STATE.current;

    // Quality display
    const qualityDisplay = document.getElementById('quality_display_main');
    if (qualityDisplay) {
        qualityDisplay.textContent = state.quality + '%';
    }

    // Quality slider
    const qualitySlider = document.getElementById('compression_quality_main');
    if (qualitySlider && qualitySlider.value != state.quality) {
        qualitySlider.value = state.quality;
    }

    // Max dimension input
    const maxDimInput = document.querySelector('[name="compression_max_dimension"]');
    if (maxDimInput && maxDimInput.value != state.maxDimension) {
        maxDimInput.value = state.maxDimension;
    }

    // Max size input
    const maxSizeInput = document.querySelector('[name="compression_max_size"]');
    if (maxSizeInput && maxSizeInput.value != state.maxSize) {
        maxSizeInput.value = state.maxSize;
    }

    // Save original checkbox
    const saveOrigChk = document.getElementById('compression_save_original_main');
    if (saveOrigChk && saveOrigChk.checked != (state.saveOriginal === 'y')) {
        saveOrigChk.checked = state.saveOriginal === 'y';
    }

    // Default summary display
    updateDefaultSummary(state);
}

/**
 * Read current DOM state ONLY for display purposes (not as truth)
 * Used when toggling UI to restore displayed values
 * @returns {Object} - Current displayed values
 */
function readDisplayedState() {
    const displayed = {};

    const qualitySlider = document.getElementById('compression_quality_main');
    if (qualitySlider?.value) {
        displayed.quality = parseInt(qualitySlider.value, 10);
    }

    const maxDimInput = document.querySelector('[name="compression_max_dimension"]');
    if (maxDimInput?.value) {
        displayed.maxDimension = parseInt(maxDimInput.value, 10);
    }

    const maxSizeInput = document.querySelector('[name="compression_max_size"]');
    if (maxSizeInput?.value) {
        displayed.maxSize = parseFloat(maxSizeInput.value);
    }

    const saveOrigChk = document.getElementById('compression_save_original_main');
    if (saveOrigChk) {
        displayed.saveOriginal = saveOrigChk.checked ? 'y' : 'n';
    }

    return displayed;
}

/**
 * Update default summary display with current settings
 */
function updateDefaultSummary(settings) {
    const updates = [
        ['default_max_size', settings.maxDimension + ' px'],
        ['default_min_file', settings.maxSize + ' MB'],
        ['default_save_original', settings.saveOriginal === 'y' ? 'Yes' : 'No'],
        ['default_unzip', 'No'],
    ];

    updates.forEach(([id, value]) => {
        const el = document.getElementById(id);
        if (el && el.textContent !== value) {
            el.textContent = value;
        }
    });
}

// ============================================================================
// EVENT MANAGEMENT - Proper cleanup and debouncing
// ============================================================================

/**
 * Attach event listener with tracking for cleanup
 */
function attachListener(element, event, handler, options = false) {
    if (!element) return;
    element.addEventListener(event, handler, options);
    STATE.listeners.push({ element, event, handler, options });
}

/**
 * Debounced event handler
 */
function debounce(key, callback, delay = DEBOUNCE_DELAY) {
    return function (...args) {
        clearTimeout(DEBOUNCE_TIMERS[key]);
        DEBOUNCE_TIMERS[key] = setTimeout(() => {
            callback(...args);
        }, delay);
    };
}

/**
 * Remove all attached event listeners
 */
function cleanup() {
    STATE.listeners.forEach(({ element, event, handler, options }) => {
        element.removeEventListener(event, handler, options);
    });
    STATE.listeners = [];
    console.log('[CompressionUI] Event listeners cleaned up');
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
            await new Promise(resolve => setTimeout(resolve, 100));
            el = document.querySelector(selector);
            attempts++;
        }
        if (el) elements[name] = el;
    }
    return elements;
}

// ============================================================================
// UI CONTROLS INITIALIZATION
// ============================================================================

/**
 * Initialize all event listeners
 */
async function initializeEventListeners() {
    // Wait for DOM elements to be ready
    const elements = await waitForElements({
        qualitySlider: '#compression_quality_main',
        qualityDisplay: '#quality_display_main',
        maxDimInput: '[name="compression_max_dimension"]',
        maxSizeInput: '[name="compression_max_size"]',
        saveOrigChk: '#compression_save_original_main',
        toggleBtn: '#customize-compression-toggle',
        backBtn: '#compression-back-to-default',
    });

    // Quality slider - immediate
    if (elements.qualitySlider) {
        const handler = (e) => {
            const value = parseInt(e.target.value, 10);
            updateState({ quality: value });
        };
        attachListener(elements.qualitySlider, 'input', handler);
        console.log('[CompressionUI] Quality slider listener attached');
    }

    // Max dimension input - immediate
    if (elements.maxDimInput) {
        const handler = (e) => {
            const value = parseInt(e.target.value, 10) || 0;
            updateState({ maxDimension: value });
        };
        attachListener(elements.maxDimInput, 'input', handler);
        console.log('[CompressionUI] Max dimension listener attached');
    }

    // Max size input - immediate
    if (elements.maxSizeInput) {
        const handler = (e) => {
            const value = parseFloat(e.target.value) || 0;
            updateState({ maxSize: value });
        };
        attachListener(elements.maxSizeInput, 'input', handler);
        console.log('[CompressionUI] Max size listener attached');
    }

    // Save original checkbox - immediate
    if (elements.saveOrigChk) {
        const handler = (e) => {
            updateState({ saveOriginal: e.target.checked ? 'y' : 'n' });
        };
        attachListener(elements.saveOrigChk, 'change', handler);
        console.log('[CompressionUI] Save original listener attached');
    }

    // Toggle button - immediate
    if (elements.toggleBtn) {
        const handler = (e) => {
            e.preventDefault();
            toggleCustomUI(true);
        };
        attachListener(elements.toggleBtn, 'click', handler);
        console.log('[CompressionUI] Toggle button listener attached');
    }

    // Back button - immediate
    if (elements.backBtn) {
        const handler = (e) => {
            e.preventDefault();
            toggleCustomUI(false);
        };
        attachListener(elements.backBtn, 'click', handler);
        console.log('[CompressionUI] Back button listener attached');
    }

    console.log('[CompressionUI] ✓ All event listeners attached');
}

/**
 * Toggle between default summary and custom controls
 */
function toggleCustomUI(showCustom) {
    const defaultSummary = document.getElementById('compression_default_summary');
    const customControls = document.getElementById('compression_custom_controls');

    if (showCustom) {
        // Show custom controls
        if (defaultSummary) defaultSummary.classList.add('d-none');
        if (customControls) customControls.classList.remove('d-none');
        console.log('[CompressionUI] Custom UI shown');
    } else {
        // Show default summary and reset to defaults
        if (defaultSummary) defaultSummary.classList.remove('d-none');
        if (customControls) customControls.classList.add('d-none');
        resetToDefaults();
        console.log('[CompressionUI] Default UI shown, reset to defaults');
    }
}

// ============================================================================
// COMPRESSION EXECUTION
// ============================================================================

/**
 * Compress files before AJAX form submission
 * Used as beforeSubmit callback in jQuery ajaxForm
 * Must return true/false for ajaxForm to proceed
 */
async function compressBeforeAjaxSubmit(formData, jqForm, options) {
    console.log('[CompressionUI] beforeSubmit: Starting compression');

    try {
        const form = jqForm[0];
        const fileInputs = form.querySelectorAll('input[type="file"][name="userfile[]"]');

        if (fileInputs.length === 0) {
            console.log('[CompressionUI] No file inputs found, proceeding without compression');
            return true;
        }

        console.log('[CompressionUI] Compressing', fileInputs.length, 'file input(s)');

        let filesCompressed = 0;
        for (const fileInput of fileInputs) {
            if (!fileInput.files || fileInput.files.length === 0) {
                console.log('[CompressionUI] File input empty, skipping');
                continue;
            }

            try {
                console.log('[CompressionUI] Processing', fileInput.files.length, 'files from input');
                const dataTransfer = await compressFileList(fileInput.files, STATE.current);
                fileInput.files = dataTransfer.files;
                filesCompressed++;
                console.log('[CompressionUI] ✓ Files compressed for input');
            } catch (err) {
                console.error('[CompressionUI] Compression failed for this input:', err);
                // Don't fail the whole submission, just log and continue
                // This handles graceful degradation
            }
        }

        console.log('[CompressionUI] ✓ Compression complete, proceeding with upload');
        return true;
    } catch (err) {
        console.error('[CompressionUI] Unexpected error in compression:', err);
        // Don't block submission on unexpected errors
        return true;
    }
}

// ============================================================================
// INITIALIZATION
// ============================================================================

/**
 * Initialize compression controls with server settings
 * @param {Object} config - Server preferences
 */
async function initializeCompressionUI(config = {}) {
    if (STATE.isInitialized) {
        console.log('[CompressionUI] Already initialized, skipping');
        return;
    }

    try {
        // Set server defaults (never modified)
        STATE.serverDefaults = {
            quality: validateSetting('quality', config.quality || 65),
            maxDimension: validateSetting('maxDimension', config.maxDimension || 1600),
            maxSize: validateSetting('maxSize', config.maxSize || 1),
            saveOriginal: validateSetting('saveOriginal', config.saveOriginal || 'n'),
            preset: validateSetting('preset', config.preset || 'slider'),
        };

        // Initialize current state from defaults
        STATE.current = { ...STATE.serverDefaults };

        console.log('[CompressionUI] Server defaults:', STATE.serverDefaults);

        // Wait for DOM to be ready
        await waitForElements({
            summary: '#compression_default_summary',
            controls: '#compression_custom_controls',
        });

        // Sync UI with state
        updateDOMFromState();

        // Attach all event listeners
        await initializeEventListeners();

        STATE.isInitialized = true;
        console.log('[CompressionUI] ✓ Fully initialized');
    } catch (err) {
        console.error('[CompressionUI] Initialization failed:', err);
        STATE.isInitialized = false;
    }
}

// ============================================================================
// EXPORTS
// ============================================================================

export default initializeCompressionUI;
export { compressBeforeAjaxSubmit, cleanup };
