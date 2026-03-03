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
 *    - When max dimension changes
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
    previewCanvas: null,
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

/**
 * Estimate compressed file size based on quality and dimensions
 * Uses a heuristic based on quality and resolution
 * @param {number} width - Image width
 * @param {number} height - Image height
 * @param {number} quality - Quality 0-100 (100 = original, 10 = minimal)
 * @param {number} originalBytes - Original file size
 * @returns {number} - Estimated bytes
 */
function estimateCompressedSize(width, height, quality, originalBytes) {
    // Base calculation: quality factor (10%-90% of saved space)
    // At 100% quality, almost no reduction. At 10%, significant reduction.
    const qualityFactor = 0.1 + (quality / 100) * 0.9;
    // Estimate size based on resolution
    const pixelCount = width * height;
    const baseSizePerPixel = originalBytes / (Math.log(pixelCount) * 3);
    const estimatedSize = pixelCount * baseSizePerPixel * (qualityFactor * 0.7);

    return Math.max(estimatedSize, originalBytes * 0.1); // Minimum 10% of original
}

/**
 * Create a preview of compressed image using canvas with quality rendering
 * This renders the actual JPEG quality artifacts so the user sees the visual impact
 * @param {HTMLCanvasElement} canvas - Canvas to draw to
 * @param {HTMLImageElement} img - Image element to draw from
 * @param {number} quality - Quality 0-100
 * @param {number} maxDimension - Max width or height (for resizing)
 * @returns {Object} - {blob, dataUrl, width, height}
 */
async function createCanvasPreview(canvas, img, quality, maxDimension) {
    // Calculate dimensions with constraint
    let width = img.naturalWidth;
    let height = img.naturalHeight;

    if (width > maxDimension || height > maxDimension) {
        const ratio = width > height ? maxDimension / width : maxDimension / height;
        width = Math.round(width * ratio);
        height = Math.round(height * ratio);
    }

    canvas.width = width;
    canvas.height = height;

    const ctx = canvas.getContext("2d");
    if (!ctx) return null;

    // Draw the resized image onto canvas
    ctx.drawImage(img, 0, 0, width, height);

    // Get actual JPEG with quality compression - this creates visible artifacts
    // that show the user what quality setting does
    const dataUrl = canvas.toDataURL("image/jpeg", quality / 100);

    // Also get the blob size for file size estimate
    const blob = await new Promise((resolve) => {
        canvas.toBlob((blob) => resolve(blob), "image/jpeg", quality / 100);
    });

    return { blob, dataUrl, width, height };
}

/**
 * Update preview display with debouncing
 * Listens to any settings change and re-renders the preview with new quality/dimensions
 * @param {Object} settings - {quality, maxDimension}
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

        const { quality = 85, maxDimension = 1600 } = settings;

        try {
            // Create canvas if not exists
            if (!PREVIEW_STATE.previewCanvas) {
                PREVIEW_STATE.previewCanvas = document.createElement("canvas");
            }

            // Generate preview with actual JPEG compression rendering
            const result = await createCanvasPreview(PREVIEW_STATE.previewCanvas, PREVIEW_STATE.originalImage, quality, maxDimension);

            if (!result) {
                console.warn("[ImagePreview] Failed to create preview");
                return;
            }

            const { blob, dataUrl, width, height } = result;

            // Update preview image - shows actual JPEG artifacts from compression
            const previewImg = document.getElementById("compression_preview_image");
            if (previewImg) {
                previewImg.src = dataUrl;
            }

            // Update file size display
            const originalSizeEl = document.getElementById("compression_original_size");
            const estimatedSizeEl = document.getElementById("compression_estimated_size");

            if (originalSizeEl) {
                originalSizeEl.textContent = formatFileSize(PREVIEW_STATE.originalBlob.size);
            }

            if (estimatedSizeEl) {
                const reduction = Math.round(((PREVIEW_STATE.originalBlob.size - blob.size) / PREVIEW_STATE.originalBlob.size) * 100);
                estimatedSizeEl.textContent = `${formatFileSize(blob.size)} (${reduction}% smaller)`;
                // Color code based on reduction amount
                if (reduction > 50) {
                    estimatedSizeEl.className = "text-success fw-bold";
                } else if (reduction > 20) {
                    estimatedSizeEl.className = "text-info fw-bold";
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
                maxDimension: state.current?.maxDimension || 1600,
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

    // Clear any pending updates
    if (PREVIEW_STATE.updateDebounceTimer) {
        clearTimeout(PREVIEW_STATE.updateDebounceTimer);
    }
}

/**
 * Refresh preview from external module (compressionControls.js)
 * Called whenever quality or maxDimension settings change
 * @param {Object} settings - {quality, maxDimension}
 */
function refreshPreview(settings) {
    // Only update if we have an image loaded
    if (!PREVIEW_STATE.originalImage || !PREVIEW_STATE.originalFile) {
        return;
    }

    // Trigger update with new settings
    updatePreview({
        quality: settings.quality || 85,
        maxDimension: settings.maxDimension || 1600,
    });
}

export default initializeImagePreview;
export { cleanup, refreshPreview, formatFileSize };
