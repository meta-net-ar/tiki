/**
 * Media Compressor Module
 *
 * Centralized compression for images and videos.
 * - Images: browser-image-compression
 * - Videos: ffmpeg.wasm
 * Handles file type validation, graceful fallbacks, returns unsupported as-is.
 */

import imageCompression from "browser-image-compression";
import { FFmpeg } from "@ffmpeg/ffmpeg";
import { fetchFile } from "@ffmpeg/util";

// Supported MIME types
const SUPPORTED_TYPES = {
    images: ["image/jpeg", "image/jpg", "image/png", "image/webp", "image/gif", "image/bmp"],
    videos: ["video/mp4", "video/webm", "video/quicktime", "video/x-msvideo", "video/mpeg"],
};

const ALL_SUPPORTED = [...SUPPORTED_TYPES.images, ...SUPPORTED_TYPES.videos];

// FFmpeg instance (lazy loaded)
let ffmpegInstance = null;

/**
 * Initialize ffmpeg (lazy load)
 * Only loads if needed for video compression
 */
async function initFFmpeg() {
    if (ffmpegInstance) return ffmpegInstance;
    
    try {
        const ffmpeg = new FFmpeg();
        await ffmpeg.load();
        ffmpegInstance = ffmpeg;
        return ffmpegInstance;
    } catch (error) {
        console.warn("[MediaCompressor] FFmpeg initialization failed:", error.message);
        return null;
    }
}

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
 * Check if file is a video
 * 
 * @param {String} mimeType - MIME type
 * @returns {Boolean}
 */
function isVideo(mimeType) {
    return SUPPORTED_TYPES.videos.includes(mimeType);
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
 * Compress video using FFmpeg
 * 
 * @private
 */
async function compressVideo(fileBlob, prefs) {
    const ffmpeg = await initFFmpeg();
    if (!ffmpeg) {
        throw new Error("FFmpeg not available for video compression");
    }

    const quality = prefs.quality || 65;
    const crf = Math.max(18, Math.min(51, Math.round((100 - quality) * 0.33))); // CRF scale

    try {
        const buffer = await fetchFile(fileBlob);

        ffmpeg.writeFile("input.mp4", buffer);

        // Use libx265 for better compression
        await ffmpeg.exec([
            "-i", "input.mp4",
            "-c:v", "libx265",
            "-crf", crf.toString(),
            "-c:a", "aac",
            "-b:a", "128k",
            "output.mp4"
        ]);

        const data = ffmpeg.readFile("output.mp4");
        ffmpeg.deleteFile("input.mp4");
        ffmpeg.deleteFile("output.mp4");

        return new Blob([data.buffer], { type: "video/mp4" });
    } catch (error) {
        throw new Error(`Video compression failed: ${error.message}`);
    }
}

/**
 * Compress media file (image or video)
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
        } else if (isVideo(mimeType)) {
            result.formatted = "video";
            compressedBlob = await compressVideo(fileBlob, prefs);
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

        console.log("[MediaCompressor]", error);
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
                const compressedFile = new File(
                    [result.blob],
                    file.name,
                    { type: result.blob.type }
                );
                dataTransfer.items.add(compressedFile);

                results.push({
                    name: file.name,
                    original: file.size,
                    compressed: result.blob.size,
                    success: true,
                    savings: (100 - (result.blob.size / file.size * 100)).toFixed(1),
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
            console.error("[MediaCompressor] Error compressing file:", file.name, err);
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

    console.log("[MediaCompressor] Compression results:", results);
    return dataTransfer;
}

export { compress, compressMultiple, compressFileList, isSupported, isImage, isVideo };
export default compress;
