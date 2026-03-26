import { compress, normalizeResizeDimension } from "./imageCompressor.js";

/**
 * Image Preview Module
 *
 * LISTENS TO:
 * 1. File Input: Configurable ID (default: "userfile")
 *    - When user selects image(s), preview loads with current compression settings
 *    - Pass {fileInputId: "your-id"} to initializeImagePreview() to use different input
 *
 * 2. Compression Settings: Called from compressionControls.js via refreshPreview()
 *    - When quality slider changes
 *    - When max dimensions (X/Y) change
 *    - Updates canvas rendering to show visual impact
 *
 * DISPLAYS:
 * - Real-time visual preview with JPEG quality artifacts
 * - Original vs estimated file size
 * - Final dimensions after resizing
 *
 * Works with batch uploads (displays first image selected)
 */

const PREVIEW_STATE = {
    originalFile: null,
    originalBlob: null,
    originalImage: null,
    previewObjectUrl: null,
    listeners: [],
    updateDebounceTimer: null,
    DEBOUNCE_DELAY: 150, // ms - delay before rendering preview after setting change
    fileInputId: "userfile", // Configurable via initializeImagePreview(config)
};

/**
 * Format bytes to human-readable size
 * @param {number} bytes - Size in bytes
 * @returns {string} - Formatted size (e.g., "2.5 MB", "450 KB")
 */
function formatFileSize(bytes) {
    if (bytes === 0) return "0 B";
    const k = 1024;
    const sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
}

function revokePreviewObjectUrl() {
    if (PREVIEW_STATE.previewObjectUrl) {
        URL.revokeObjectURL(PREVIEW_STATE.previewObjectUrl);
        PREVIEW_STATE.previewObjectUrl = null;
    }
}

function formatSizeDelta(originalBytes, resultBytes) {
    const delta = resultBytes - originalBytes;
    const percent = Math.round(Math.abs(delta / originalBytes) * 100);

    if (delta < 0) {
        return `${formatFileSize(resultBytes)} (${percent}% smaller)`;
    }

    if (delta > 0) {
        return `${formatFileSize(resultBytes)} (${percent}% larger)`;
    }

    return `${formatFileSize(resultBytes)} (no change)`;
}

async function getPreviewCompressionResult(file, settings) {
    const result = await compress(
        file,
        {
            quality: settings.quality,
            maxDimensionX: normalizeResizeDimension(settings.maxDimensionX),
            maxDimensionY: normalizeResizeDimension(settings.maxDimensionY),
            maxSize: settings.maxSize,
        },
        { fallbackOnError: false }
    );

    if (!result.success || !result.blob) {
        return null;
    }

    const objectUrl = URL.createObjectURL(result.blob);

    try {
        const dimensions = await new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve({ width: image.naturalWidth, height: image.naturalHeight });
            image.onerror = reject;
            image.src = objectUrl;
        });

        return {
            blob: result.blob,
            objectUrl,
            width: dimensions.width,
            height: dimensions.height,
        };
    } catch (error) {
        URL.revokeObjectURL(objectUrl);
        throw error;
    }
}

/**
 * Update preview display with debouncing
 * Listens to any settings change and re-renders the preview with new quality/dimensions
 * @param {Object} settings - {quality, maxDimensionX, maxDimensionY, maxSize}
 */
async function updatePreview(settings) {
    // Clear any pending update
    if (PREVIEW_STATE.updateDebounceTimer) {
        clearTimeout(PREVIEW_STATE.updateDebounceTimer);
    }

    // Debounce the actual preview rendering to avoid hammering canvas
    PREVIEW_STATE.updateDebounceTimer = setTimeout(async () => {
        if (!PREVIEW_STATE.originalImage || !PREVIEW_STATE.originalFile) {
            return;
        }

        const { quality = 85, maxDimensionX = 0, maxDimensionY = 0, maxSize = 1 } = settings;

        try {
            const result = await getPreviewCompressionResult(PREVIEW_STATE.originalFile, {
                quality,
                maxDimensionX,
                maxDimensionY,
                maxSize,
            });

            if (!result) {
                return;
            }

            const { blob, objectUrl, width, height } = result;

            const previewImg = document.getElementById("compression_preview_image");
            if (previewImg) {
                revokePreviewObjectUrl();
                PREVIEW_STATE.previewObjectUrl = objectUrl;
                previewImg.src = objectUrl;
            }

            const originalSizeEl = document.getElementById("compression_original_size");
            const estimatedSizeEl = document.getElementById("compression_estimated_size");

            if (originalSizeEl) {
                originalSizeEl.textContent = formatFileSize(PREVIEW_STATE.originalBlob.size);
            }

            if (estimatedSizeEl) {
                const delta = blob.size - PREVIEW_STATE.originalBlob.size;
                estimatedSizeEl.textContent = formatSizeDelta(PREVIEW_STATE.originalBlob.size, blob.size);
                if (delta < -0.5 * PREVIEW_STATE.originalBlob.size) {
                    estimatedSizeEl.className = "text-success fw-bold";
                } else if (delta < -0.2 * PREVIEW_STATE.originalBlob.size) {
                    estimatedSizeEl.className = "text-info fw-bold";
                } else if (delta > 0) {
                    estimatedSizeEl.className = "text-warning fw-bold";
                } else {
                    estimatedSizeEl.className = "text-muted";
                }
            }

            // Update dimensions display
            const dimensionsEl = document.getElementById("compression_preview_dimensions");
            if (dimensionsEl) {
                dimensionsEl.textContent = `${width}×${height}px`;
            }
        } catch (err) {
            console.error("[ImagePreview] Preview update failed:", err);
        }
    }, PREVIEW_STATE.DEBOUNCE_DELAY);
}

/**
 * Load image from file input
 * Handles single or first of multiple selected images
 * @param {File} file - File from input
 */
async function loadImageFromFile(file) {
    if (!file || !file.type.startsWith("image/")) {
        console.warn("[ImagePreview] File is not an image:", file?.name);
        return;
    }

    PREVIEW_STATE.originalFile = file;
    PREVIEW_STATE.originalBlob = file;

    const reader = new FileReader();
    reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
            // Store original image for reuse
            PREVIEW_STATE.originalImage = img;

            // Show preview container
            const previewContainer = document.getElementById("compression_preview_container");
            if (previewContainer) {
                previewContainer.classList.remove("d-none");
            }

            // Initial preview with current settings
            const state = window.COMPRESSION_STATE || {};
            updatePreview({
                quality: state.current?.quality || 85,
                maxDimensionX: state.current?.maxDimensionX ?? 0,
                maxDimensionY: state.current?.maxDimensionY ?? 0,
                maxSize: state.current?.maxSize ?? 1,
            });
        };
        img.onerror = () => {
            console.error("[ImagePreview] Failed to load image:", file.name);
        };
        img.src = e.target.result;
    };
    reader.onerror = () => {
        console.error("[ImagePreview] Failed to read file:", file.name);
    };
    reader.readAsDataURL(file);
}

/**
 * Attach event listener with tracking for cleanup
 */
function attachListener(element, event, handler, options = false) {
    if (!element) return;
    element.addEventListener(event, handler, options);
    PREVIEW_STATE.listeners.push({ element, event, handler, options });
}

/**
 * Initialize file input listener
 * Listens for changes to the file input configured in PREVIEW_STATE.fileInputId
 */
async function initializeFileListener() {
    // Listen to the file upload input (ID configurable)
    const fileInput = document.getElementById(PREVIEW_STATE.fileInputId);
    if (fileInput) {
        const handler = (e) => {
            // Load first selected image (handles batch uploads)
            // When user selects one or more images, preview the first one
            if (e.target.files && e.target.files.length > 0) {
                loadImageFromFile(e.target.files[0]);
            }
        };
        attachListener(fileInput, "change", handler);
    } else {
        console.warn(`[ImagePreview] Could not find file input #${PREVIEW_STATE.fileInputId}`);
    }
}

/**
 * Initialize image preview with real-time updates
 * @param {Object} config - Configuration object
 * @param {string} config.fileInputId - ID of file input element (default: "userfile")
 *
 * This function:
 * 1. Listens to file input changes (configurable ID)
 * 2. Listens to compression control changes (quality, maxDimension)
 * 3. Updates preview immediately when any setting changes
 */
async function initializeImagePreview(config = {}) {
    try {
        // Allow override of file input ID
        if (config.fileInputId) {
            PREVIEW_STATE.fileInputId = config.fileInputId;
        }

        await initializeFileListener();

        // Request compression controls to notify us of setting changes
        // The compressionControls.js will call refreshPreview() when settings change
    } catch (err) {
        console.error("[ImagePreview] Initialization failed:", err);
    }
}

/**
 * Cleanup preview listeners
 */
function cleanup() {
    PREVIEW_STATE.listeners.forEach(({ element, event, handler, options }) => {
        element.removeEventListener(event, handler, options);
    });
    PREVIEW_STATE.listeners = [];
    revokePreviewObjectUrl();

    // Clear any pending updates
    if (PREVIEW_STATE.updateDebounceTimer) {
        clearTimeout(PREVIEW_STATE.updateDebounceTimer);
    }
}

/**
 * Refresh preview from external module (compressionControls.js)
 * Called whenever quality or dimension settings change
 * @param {Object} settings - {quality, maxDimensionX, maxDimensionY, maxSize}
 */
function refreshPreview(settings) {
    // Only update if we have an image loaded
    if (!PREVIEW_STATE.originalImage || !PREVIEW_STATE.originalFile) {
        return;
    }

    // Trigger update with new settings
    updatePreview({
        quality: settings.quality || 85,
        maxDimensionX: settings.maxDimensionX ?? 0,
        maxDimensionY: settings.maxDimensionY ?? 0,
        maxSize: settings.maxSize ?? 1,
    });
}

export default initializeImagePreview;
export { cleanup, refreshPreview, formatFileSize };
