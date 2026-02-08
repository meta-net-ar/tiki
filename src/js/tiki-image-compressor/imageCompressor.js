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
    images: ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'],
    videos: ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/mpeg'],
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
        console.warn('[MediaCompressor] FFmpeg initialization failed:', error.message);
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
    const mime = mimeType || file?.type || '';
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
        useWebWorker: typeof Worker !== 'undefined',
        quality: quality / 100, // Convert 0-100 to 0-1
        alwaysKeepResolution: false,
    };

    return imageCompression.compress(fileBlob, compressionOptions);
}

/**
 * Compress video using FFmpeg
 * 
 * @private
 */
async function compressVideo(fileBlob, prefs) {
    const ffmpeg = await initFFmpeg();
    if (!ffmpeg) {
        throw new Error('FFmpeg not available for video compression');
    }

    const quality = prefs.quality || 65;
    const crf = Math.max(18, Math.min(51, Math.round((100 - quality) * 0.33))); // CRF scale

    try {
        const buffer = await fetchFile(fileBlob);
        
        ffmpeg.writeFile('input.mp4', buffer);
        
        // Use libx265 for better compression
        await ffmpeg.exec([
            '-i', 'input.mp4',
            '-c:v', 'libx265',
            '-crf', crf.toString(),
            '-c:a', 'aac',
            '-b:a', '128k',
            'output.mp4'
        ]);

        const data = ffmpeg.readFile('output.mp4');
        ffmpeg.deleteFile('input.mp4');
        ffmpeg.deleteFile('output.mp4');

        return new Blob([data.buffer], { type: 'video/mp4' });
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
 *   - quality: 0-100 (default: 65)
 *   - maxDimension: pixels for images (default: 1600)
 *   - maxSize: MB (default: 1)
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
        formatted: 'unknown',
    };

    try {
        // Validate input
        if (!fileBlob) {
            throw new Error('File blob is required');
        }

        const mimeType = options.mimeType || fileBlob?.type || '';

        // If not supported, return as-is (no error)
        if (!isSupported(fileBlob, mimeType)) {
            result.skipped = true;
            result.formatted = 'unsupported';
            return result;
        }

        let compressedBlob;

        // Route to appropriate compressor
        if (isImage(mimeType)) {
            result.formatted = 'image';
            compressedBlob = await compressImage(fileBlob, prefs);
        } else if (isVideo(mimeType)) {
            result.formatted = 'video';
            compressedBlob = await compressVideo(fileBlob, prefs);
        } else {
            result.skipped = true;
            return result;
        }

        // Validate result
        if (!compressedBlob || compressedBlob.size === 0) {
            throw new Error('Compression resulted in empty blob');
        }

        result.blob = compressedBlob;
        result.success = true;

        return result;
    } catch (error) {
        const errorMsg = error.message || 'Unknown compression error';
        result.error = errorMsg;
        result.success = false;

        // Fallback: return original file
        if (options.fallbackOnError !== false) {
            result.blob = fileBlob;
        }

        console.warn('[MediaCompressor]', errorMsg);
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
        throw new Error('Files must be an array');
    }

    const results = [];
    for (const file of files) {
        const result = await compress(file, prefs, options);
        results.push(result);
    }

    return results;
}

export { compress, compressMultiple, isSupported, isImage, isVideo };
export default compress;
