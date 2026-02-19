/**
 * Image Compressor Module
 *
 * Centralized compression for images.
 * - Images: browser-image-compression
 * Handles file type validation, graceful fallbacks, returns unsupported as-is.
 */

import imageCompression from "browser-image-compression";

const ALL_SUPPORTED = ["image/jpeg", "image/jpg", "image/png", "image/webp", "image/gif", "image/bmp"];

/**
 * Check if file type is supported
 *
 * @param {Blob|File} file - File to check
 * @param {String} mimeType - Optional MIME type override
 * @returns {Boolean} - true if supported
 */
function isSupported(file, mimeType) {
    const mime = mimeType || file?.type || "";
    return ALL_SUPPORTED.includes(mime);
}

/**
 * Check if file is an image
 *
 * @param {String} mimeType - MIME type
 * @returns {Boolean}
 */
function isImage(mimeType) {
    return SUPPORTED_TYPES.images.includes(mimeType);
}

/**
 * Compress image using browser-image-compression
 *
 * @private
 */
async function compressImage(fileBlob, prefs) {
    const quality = prefs.quality || 65;
    const maxDimension = prefs.maxDimension || 1600;
    const maxSize = prefs.maxSize || 1;

    const compressionOptions = {
        maxSizeMB: maxSize,
        maxWidthOrHeight: maxDimension,
        useWebWorker: typeof Worker !== "undefined",
        quality: quality / 100, // Convert 0-100 to 0-1
        alwaysKeepResolution: false,
    };

    return imageCompression(fileBlob, compressionOptions);
}

/**
 * Compress a single file based on type and preferences
 * Returns file as-is if type not supported (no error).
 *
 * @param {Blob|File} fileBlob - File to compress
 * @param {Object} prefs - Compression preferences
 *   - quality: 0-100
 *   - maxDimension: pixels for images
 *   - maxSize: MB
 * @param {Object} options - Additional options
 *   - mimeType: override file MIME type
 *   - fallbackOnError: return original if compression fails (default: true)
 * @returns {Promise<{blob: Blob, success: Boolean, formatted: String, error?: String, skipped?: Boolean}>}
 */
async function compress(fileBlob, prefs = {}, options = {}) {
    const result = {
        blob: fileBlob,
        success: false,
        error: null,
        skipped: false,
        formatted: "unknown",
    };

    try {
        if (!fileBlob) {
            throw new Error("File blob is required");
        }

        const mimeType = options.mimeType || fileBlob?.type || "";
        if (!isSupported(fileBlob, mimeType)) {
            result.skipped = true;
            result.formatted = "unsupported";
            return result;
        }

        let compressedBlob;

        if (isImage(mimeType)) {
            result.formatted = "image";
            compressedBlob = await compressImage(fileBlob, prefs);
        } else {
            result.skipped = true;
            return result;
        }

        if (!compressedBlob || compressedBlob.size === 0) {
            throw new Error("Compression resulted in empty blob");
        }

        result.blob = compressedBlob;
        result.success = true;

        return result;
    } catch (error) {
        const errorMsg = error.message || "Unknown compression error";
        result.error = errorMsg;
        result.success = false;

        if (options.fallbackOnError !== false) {
            result.blob = fileBlob;
        }

        console.log("[ImageCompressor]", error);
        return result;
    }
}

/**
 * Compress multiple files
 *
 * @param {Blob[]|File[]} files - Array of files
 * @param {Object} prefs - Compression preferences
 * @param {Object} options - Additional options
 * @returns {Promise<Array>} - Array of results
 */
async function compressMultiple(files, prefs = {}, options = {}) {
    if (!Array.isArray(files)) {
        throw new Error("Files must be an array");
    }

    const results = [];
    for (const file of files) {
        const result = await compress(file, prefs, options);
        results.push(result);
    }

    return results;
}

/**
 * Compress FileList from form input in-place
 * Updates file input with compressed files using DataTransfer
 *
 * @param {FileList} fileList - FileList from input[type="file"]
 * @param {Object} prefs - Compression settings (quality, maxDimension, maxSize)
 * @returns {Promise<DataTransfer>} - DataTransfer with compressed files
 */
async function compressFileList(fileList, prefs = {}) {
    if (!fileList || fileList.length === 0) {
        return new DataTransfer();
    }

    const dataTransfer = new DataTransfer();
    const results = [];

    for (const file of fileList) {
        if (!isSupported(file)) {
            dataTransfer.items.add(file);
            results.push({
                name: file.name,
                original: file.size,
                compressed: file.size,
                success: false,
                skipped: true,
                reason: "unsupported",
            });
            continue;
        }

        const minSizeMB = prefs.maxSize || 1;
        const fileSizeMB = file.size / (1024 * 1024);
        if (fileSizeMB < minSizeMB) {
            dataTransfer.items.add(file);
            results.push({
                name: file.name,
                original: file.size,
                compressed: file.size,
                success: false,
                skipped: true,
                reason: "too_small",
            });
            continue;
        }

        try {
            const result = await compress(file, prefs);

            if (result.success) {
                const compressedFile = new File([result.blob], file.name, { type: result.blob.type });
                dataTransfer.items.add(compressedFile);

                results.push({
                    name: file.name,
                    original: file.size,
                    compressed: result.blob.size,
                    success: true,
                    savings: (100 - (result.blob.size / file.size) * 100).toFixed(1),
                });
            } else if (result.skipped) {
                dataTransfer.items.add(file);
                results.push({
                    name: file.name,
                    original: file.size,
                    compressed: file.size,
                    success: false,
                    skipped: true,
                    reason: result.formatted,
                });
            } else {
                dataTransfer.items.add(file);
                results.push({
                    name: file.name,
                    original: file.size,
                    compressed: file.size,
                    success: false,
                    error: result.error,
                });
            }
        } catch (err) {
            console.error("[ImageCompressor] Error compressing file:", file.name, err);
            dataTransfer.items.add(file);
            results.push({
                name: file.name,
                original: file.size,
                compressed: file.size,
                success: false,
                error: err.message,
            });
        }
    }

    console.log("[ImageCompressor] Compression results:", results);
    return dataTransfer;
}

export { compress, compressMultiple, compressFileList, isSupported, isImage };
export default compress;
