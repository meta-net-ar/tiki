<script setup>
import { UploadFilled } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { ajaxUpload as defaultHttpRequest } from 'element-plus/es/components/upload/src/ajax';
import { ref, onMounted } from 'vue'
import getUploadData from '../../helpers/fileGalUploader/getUploadData';
import ConfigWrapper from '../ConfigWrapper.vue';
import handleTikiFeedback from '../../helpers/fileGalUploader/handleTikiFeedback';
import getUploadAjaxError from '../../helpers/fileGalUploader/getUploadAjaxError';
import { compressFileList, normalizeResizeDimension } from '../../../../../tiki-image-compressor/imageCompressor.js';
import initializeCompressionUI, { getCompressionSettings } from '../../../../../tiki-image-compressor/compressionControls.js';

const props = defineProps([
    'accept',
    'maxSize',
    'maxFiles',
    'vimeoUrl',
    'language',
    'compression_enabled',
    'compression_preset_type',
    'compression_quality',
    'compression_max_dimension_x',
    'compression_max_dimension_y',
    'compression_max_size_mb',
    'compression_save_original'
]);

const maxSize = JSON.parse(props.maxSize);
const maxFiles = JSON.parse(props.maxFiles);

const uploadRef = ref(null);
const insertIntoEditor = ref(false);
const uploadedFiles = ref([]);
const isCompressing = ref(false);
const totalFilesToUpload = ref(0);
const completedUploads = ref(0);
const submitCalled = ref(false);

// Preview state
const previewFiles = ref([]);
const currentPreviewIndex = ref(-1);
const showPreview = ref(false);
const previewDimensions = ref('-');
const originalSize = ref('-');
const estimatedSize = ref('-');
const needsCompression = ref(false);
const compressionReason = ref('');
let updateDebounceTimer = null;
let currentCompressionSettings = {
    quality: 85,
    maxDimensionX: 0,
    maxDimensionY: 0,
    maxSizeMb: 2
};

onMounted(async () => {
    const searcParams = new URLSearchParams(location.search);
    if (searcParams.get('filegals_manager')) {
        insertIntoEditor.value = true;
    }

    if (props.compression_enabled === 'true') {

        const prefs = {
            preset: props.compression_preset_type || 'slider',
            quality: parseInt(props.compression_quality, 10) || 85,
            maxDimensionX: normalizeResizeDimension(props.compression_max_dimension_x),
            maxDimensionY: normalizeResizeDimension(props.compression_max_dimension_y),
            maxSize: parseFloat(props.compression_max_size_mb) || 1,
            saveOriginal: props.compression_save_original,
        };

        await initializeCompressionUI(prefs);

        // Store compression settings for dynamic checks
        currentCompressionSettings = {
            quality: prefs.quality,
            maxDimensionX: prefs.maxDimensionX,
            maxDimensionY: prefs.maxDimensionY,
            maxSizeMb: prefs.maxSize
        };

        // Make preview update function globally available for compressionControls
        window.updateVueUploadPreview = updatePreviewFromSettings;
    }
})

/**
 * Format bytes to human-readable size
 */
const formatFileSize = (bytes) => {
    if (bytes === 0) return "0 B";
    const k = 1024;
    const sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
}

/**
 * Get file extension from filename
 */
const getFileExtension = (fileName) => {
    return fileName.split('.').pop()?.toUpperCase() || 'FILE';
}

/**
 * Create canvas preview with actual JPEG compression
 */
const createCanvasPreview = async (img, quality, maxDimensionX, maxDimensionY) => {
    let width = img.naturalWidth;
    let height = img.naturalHeight;

    const normalizedMaxWidth = normalizeResizeDimension(maxDimensionX);
    const normalizedMaxHeight = normalizeResizeDimension(maxDimensionY);

    if ((normalizedMaxWidth && width > normalizedMaxWidth) || (normalizedMaxHeight && height > normalizedMaxHeight)) {
        let ratioX = normalizedMaxWidth && width > normalizedMaxWidth ? normalizedMaxWidth / width : 1;
        let ratioY = normalizedMaxHeight && height > normalizedMaxHeight ? normalizedMaxHeight / height : 1;
        let ratio = Math.min(ratioX, ratioY);
        width = Math.round(width * ratio);
        height = Math.round(height * ratio);
    }

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;

    const ctx = canvas.getContext('2d');
    if (!ctx) return null;

    ctx.drawImage(img, 0, 0, width, height);

    const dataUrl = canvas.toDataURL('image/jpeg', quality / 100);
    
    const blob = await new Promise((resolve) => {
        canvas.toBlob(
            (blob) => resolve(blob),
            'image/jpeg',
            quality / 100
        );
    });

    return { blob, dataUrl, width, height };
};

/**
 * Get current preview file object
 */
const currentPreviewFile = () => {
    if (currentPreviewIndex.value >= 0 && currentPreviewIndex.value < previewFiles.value.length) {
        return previewFiles.value[currentPreviewIndex.value];
    }
    return null;
};

/**
 * Check if current image needs compression based on settings
 */
const checkIfCompressionNeeded = (imageWidth, imageHeight, fileSize, settings) => {
    const fileSizeMb = fileSize / (1024 * 1024);
    const exceedsMinFileSize = fileSizeMb >= settings.maxSizeMb;
    const exceedsMaxWidth = settings.maxDimensionX > 0 && imageWidth > settings.maxDimensionX;
    const exceedsMaxHeight = settings.maxDimensionY > 0 && imageHeight > settings.maxDimensionY;

    // Check if compression is needed: file size OR dimensions exceed limits
    if (exceedsMinFileSize || exceedsMaxWidth || exceedsMaxHeight) {
        needsCompression.value = true;
        let reason = [];
        if (exceedsMinFileSize) {
            reason.push(`file size (${fileSizeMb.toFixed(2)}MB) exceeds minimum threshold of ${settings.maxSizeMb}MB`);
        }
        if (exceedsMaxWidth) {
            reason.push(`width (${imageWidth}px) exceeds maximum of ${settings.maxDimensionX}px`);
        }
        if (exceedsMaxHeight) {
            reason.push(`height (${imageHeight}px) exceeds maximum of ${settings.maxDimensionY}px`);
        }
        compressionReason.value = `Image will be compressed because ${reason.join(', ')}.`;
        return true;
    } else {
        needsCompression.value = false;
        compressionReason.value = `File is small (${fileSizeMb.toFixed(2)}MB) and dimensions (${imageWidth}×${imageHeight}px) are within limits. No compression needed.`;
        return false;
    }
};

/**
 * Update preview when compression settings change (called from compressionControls)
 */
const updatePreviewFromSettings = async (settings) => {
    const current = currentPreviewFile();
    if (!current || !current.image || !current.type.startsWith('image/')) return;

    if (updateDebounceTimer) clearTimeout(updateDebounceTimer);

    updateDebounceTimer = setTimeout(async () => {
        try {
            const quality = settings.quality || 85;
            const maxDimensionX = normalizeResizeDimension(settings.maxDimensionX);
            const maxDimensionY = normalizeResizeDimension(settings.maxDimensionY);
            const maxSizeMb = settings.maxSize ?? settings.maxSizeMb ?? currentCompressionSettings.maxSizeMb;
            
            // Update current settings
            currentCompressionSettings = { quality, maxDimensionX, maxDimensionY, maxSizeMb };
            
            // Check if compression is actually needed
            const compressionNeeded = checkIfCompressionNeeded(
                current.image.naturalWidth,
                current.image.naturalHeight,
                current.file.size,
                { maxDimensionX, maxDimensionY, maxSizeMb }
            );
            
            if (!compressionNeeded) {
                // Image doesn't need compression, don't show size comparison
                previewDimensions.value = `${current.image.naturalWidth}×${current.image.naturalHeight}px`;
                originalSize.value = formatFileSize(current.file.size);
                estimatedSize.value = '-';
                return;
            }
            
            const result = await createCanvasPreview(
                current.image,
                quality,
                maxDimensionX,
                maxDimensionY
            );

            if (!result) return;

            const { blob, dataUrl, width, height } = result;

            // Update the file object with new preview data (Vue reactive)
            current.dataUrl = dataUrl;
            previewDimensions.value = `${width}×${height}px`;
            
            const reduction = Math.round(
                ((current.file.size - blob.size) / current.file.size) * 100
            );
            
            originalSize.value = formatFileSize(current.file.size);
            estimatedSize.value = `${formatFileSize(blob.size)} (${reduction}% smaller)`;
        } catch (err) {
            console.error('[FileGalUploader] Preview update failed:', err);
        }
    }, 150);
};

/**
 * Load preview for a specific file
 */
const selectFileForPreview = (index) => {
    if (index < 0 || index >= previewFiles.value.length) return;
    
    const file = previewFiles.value[index];
    
    if (!file.type.startsWith('image/')) {
        // Non-image file selected, no preview available
        currentPreviewIndex.value = index;
        return;
    }

    if (!file.image) return;

    currentPreviewIndex.value = index;
    showPreview.value = true;

    const state = window.COMPRESSION_STATE || {};
    updatePreviewFromSettings({
        quality: state.current?.quality || 85,
        maxDimensionX: state.current?.maxDimensionX ?? 0,
        maxDimensionY: state.current?.maxDimensionY ?? 0,
        maxSizeMb: state.current?.maxSize ?? state.current?.maxSizeMb ?? currentCompressionSettings.maxSizeMb ?? 1
    });
};

/**
 * Load previews when files are selected
 */
const loadImagePreviews = (files) => {
    previewFiles.value = [];
    currentPreviewIndex.value = -1;
    
    if (!files || files.length === 0) {
        showPreview.value = false;
        return;
    }

    // Process all selected files
    files.forEach((file, index) => {
        const rawFile = file.raw;
        const fileObj = {
            file: rawFile,
            image: null,
            dataUrl: null,  // Store preview data URL for reactivity
            name: rawFile.name,
            type: rawFile.type,
            index: index
        };

        if (rawFile.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                // Set dataUrl immediately for thumbnail display
                fileObj.dataUrl = e.target.result;
                
                const img = new Image();
                img.onload = () => {
                    fileObj.image = img;
                    // Auto-load preview for first image
                    if (currentPreviewIndex.value === -1) {
                        selectFileForPreview(index);
                    }
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(rawFile);
        }

        previewFiles.value.push(fileObj);
    });

    const hasImages = previewFiles.value.some(file => file.type.startsWith('image/'));
    if (hasImages) {
        showPreview.value = true;
        if (currentPreviewIndex.value === -1) {
            const firstImageIndex = previewFiles.value.findIndex(file => file.type.startsWith('image/'));
            if (firstImageIndex !== -1) {
                selectFileForPreview(firstImageIndex);
            }
        }
    } else {
        showPreview.value = false;
    }
};

/**
 * Handle file change event
 */
const handleFileChange = (file, fileList) => {
    loadImagePreviews(fileList);
    const compressionSection = document.getElementById('image_compression_controls');
    if (compressionSection) {
        const hasImageFile = fileList && fileList.length > 0 && fileList.some(fileItem => {
            return fileItem.raw && fileItem.raw.type.startsWith('image/');
        });

        if (hasImageFile) {
            compressionSection.classList.remove('d-none');
        } else {
            compressionSection.classList.add('d-none');
        }
    }
};

const submitUpload = async () => {
    uploadRef.value.submit();
}

const beforeUpload = async (rawFile) => {
    if (rawFile.size > maxSize) {
        const maxSizeKB = props.maxSize / 1000;
        ElMessage.error(`File size cannot exceed ${maxSizeKB}KB`);
        return false;
    }

    if (props.compression_enabled !== 'true') return true;

    try {
        isCompressing.value = true;
        const pressets =  getCompressionSettings();

        if(pressets.current.saveOriginal !== "y"){
            ElMessage.info(`Compressing file ${rawFile.name}...`);
            const compressedFiles = await compressFileList([rawFile], pressets.current);

            if (compressedFiles?.files?.length) {
                const compressedFile = compressedFiles.files[0];
                console.log('[FileGalUploader] Replacing upload file', {original: rawFile.size, compressed: compressedFile.size});
                ElMessage.success(`File ${rawFile.name} compressed successfully (${(rawFile.size / 1000).toFixed(2)}KB → ${(compressedFile.size / 1000).toFixed(2)}KB)`);

                return compressedFile;
            }
        }
        return true;
    } catch (err) {
        ElMessage.error("Compression failed: " + err.message);
        return false;
    } finally {
        isCompressing.value = false;
    }
}

const submitUpload = async () => {
    // Compress files first if handler available
    const canProceed = await compressFiles();
    if (!canProceed) {
        return;
    }

    submitCalled.value = true;
    if (uploadRef.value && uploadRef.value.uploadFiles) {
        totalFilesToUpload.value = uploadRef.value.uploadFiles.length;
        completedUploads.value = 0;
    } else if (uploadRef.value) {
        totalFilesToUpload.value = 0;
        completedUploads.value = 0;
    }

    uploadRef.value.submit();
}

const beforeUpload = (rawFile) => {
    if (rawFile.size > maxSize) {
        const maxSizeKB = props.maxSize / 1000;
        ElMessage.error(`File size cannot exceed ${maxSizeKB}KB`);
        return false;
    }
}

/**
 * When opened as a file gallery manager (filegals_manager=…), inserts use
 * window.opener.insertAt; closing the popup is handled by the shared jQuery
 * helper on window (reads #keepOpenCbx — see tiki-jquery.js).
 */
const closeGalleryManagerAfterInsert = () => {
    window.tikiCloseFileGalleryManagerWindow?.();
};

const checkAllUploadsComplete = () => {
    if (totalFilesToUpload.value > 0) {
        if (completedUploads.value >= totalFilesToUpload.value) {
            if (insertIntoEditor.value) {
                closeGalleryManagerAfterInsert();
            }
        }
    }
}

const handleUploadError = (error) => {
    ElMessage.error(error.message)
    completedUploads.value++;
    checkAllUploadsComplete();
}

/**
 * Reset preview state after all uploads complete
 */
const resetPreview = () => {
    previewFiles.value = [];
    currentPreviewIndex.value = -1;
    showPreview.value = false;
    previewDimensions.value = '-';
    originalSize.value = '-';
    estimatedSize.value = '-';
    needsCompression.value = false;
    compressionReason.value = '';

    // Clear file list in upload component
    // uploadRef.value?.clearFiles();
};

/**
 * Check if all files have finished uploading
 */
const areAllFilesUploaded = () => {
    const uploadFiles = uploadRef.value?.uploadFiles || [];
    if (uploadFiles.length === 0) return true;
    
    // Check if any file is still uploading or has failed
    return uploadFiles.every(file => file.status === 'success' || file.status === 'fail');
};

const handleUploadSuccess = (response, file) => {
    ElMessage.success(`${file.name} uploaded successfully`)

    const syntax = response.syntax || `{img fileId="${response.fileId}" thumb="box"}`;

    uploadedFiles.value.push({
        name: file.name,
        fileId: response.fileId,
        syntax: syntax
    });

    if (areAllFilesUploaded()) {
        resetPreview();
    }

    const searcParams = new URLSearchParams(location.search);
    if (insertIntoEditor.value) {
        window.opener.insertAt(searcParams.get('filegals_manager'), syntax, false, false, true);
    }

    if (totalFilesToUpload.value === 0) {
        if (uploadRef.value && uploadRef.value.uploadFiles && uploadRef.value.uploadFiles.length > 0) {
            totalFilesToUpload.value = uploadRef.value.uploadFiles.length;
        } else if (!submitCalled.value) {
            totalFilesToUpload.value = 1;
        }
    }

    completedUploads.value++;

    checkAllUploadsComplete();
    
    if (props.vimeoUrl) {
        completeVimeoUpload(file.name);
    }
}

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
        ElMessage.success('Copied to clipboard!');
    }).catch(() => {
        ElMessage.error('Failed to copy');
    });
}

const httpRequest = (option) => {
    const xhr = defaultHttpRequest(option);
    const originalOnError = option.onError;
    option.onError = () => {
        originalOnError(getUploadAjaxError(option, xhr));
        handleTikiFeedback(xhr);
    };
}

</script>

<script>
const uniqueId = new Date().getTime();
export const DATA_TEST_ID = {
    UPLOAD_ELEMENT: `upload-element-${uniqueId}`,
    UPLOAD_ICON: `upload-icon-${uniqueId}`,
    UPLOAD_TEXT: `upload-text-${uniqueId}`,
    SUBMIT_BUTTON: `upload-button-${uniqueId}`,
}
export const DEFAULT_ACTION_URL = 'tiki-ajax_services.php?controller=file&action=upload';
</script>

<template>
    <ConfigWrapper :language="language">
        <div v-if="showPreview && previewFiles.length > 0" class="files-preview-container mt-4 p-3 border rounded bg-light">
            <strong class="d-block mb-3">Selected Files</strong>
            <!-- Horizontal scrollable file list -->
            <div class="file-list-scroll" style="overflow-x: auto; overflow-y: hidden; margin-bottom: 2rem; padding-bottom: 0.5rem;">
                <div class="d-flex gap-2" style="min-width: max-content;">
                    <button 
                        v-for="(fileObj, index) in previewFiles" 
                        :key="index"
                        type="button"
                        class="file-thumbnail-btn"
                        :class="{ active: currentPreviewIndex === index }"
                        @click="selectFileForPreview(index)"
                        :title="fileObj.name"
                        style="border: 2px solid #ccc; border-radius: 4px; padding: 0.5rem; background: white; cursor: pointer; transition: all 0.2s; min-width: 100px; text-align: center;"
                        :style="{ borderColor: currentPreviewIndex === index ? '#409EFF' : '#ccc', backgroundColor: currentPreviewIndex === index ? '#E6F7FF' : 'white' }"
                    >
                        <!-- Image preview thumbnail -->
                        <img 
                            v-if="fileObj.dataUrl && fileObj.type.startsWith('image/')"
                            :src="fileObj.dataUrl" 
                            :alt="fileObj.name"
                            style="width: 80px; height: 80px; object-fit: contain; border-radius: 2px; display: block; margin: 0 auto 0.5rem;"
                        >
                        <!-- File type icon for non-images -->
                        <div 
                            v-else
                            style="width: 80px; height: 80px; margin: 0 auto 0.5rem; display: flex; align-items: center; justify-content: center; background: #f5f5f5; border-radius: 2px; font-size: 2rem; font-weight: bold; color: #666;"
                        >
                            {{ getFileExtension(fileObj.name) }}
                        </div>
                        <small class="text-truncate d-block" style="font-size: 0.8rem;">{{ fileObj.name }}</small>
                    </button>
                </div>
            </div>
        </div>
        <div v-if="currentPreviewFile() && currentPreviewFile().type.startsWith('image/')" class="image-preview-layout">
           <div class="preview-image-container">
                <img
                    v-if="currentPreviewFile().dataUrl"
                    :src="currentPreviewFile().dataUrl"
                    :alt="currentPreviewFile().name"
                    class="preview-main"
                >
            </div>

            <div v-if="props.compression_enabled === 'true'" class="preview-info-container">
                <div v-if="needsCompression" class="preview-info-panel">
                    <h6 class="preview-title">File: {{ currentPreviewFile().name }}</h6>
                    <div class="preview-dimensions">
                        Dimensions: {{ previewDimensions }}
                    </div>
                    <hr class="preview-divider">
                    <h6 class="preview-subtitle">
                        File Size Comparison
                    </h6>
                    <div class="preview-size-row">
                        <span>Original size:</span>
                        <span class="preview-size-value">{{ originalSize }}</span>
                    </div>
                    <div class="preview-size-row">
                        <span>Estimated after compression:</span>
                        <span class="preview-size-value preview-size-compressed">{{ estimatedSize }}</span>
                    </div>
                    <p class="preview-note">Preview updates as you adjust compression settings.</p>
                </div>
                <div v-else class="preview-info-panel">
                    <h6 class="preview-title">File: {{ currentPreviewFile().name }}</h6>
                    <div class="preview-dimensions">
                        Dimensions: {{ previewDimensions }}
                    </div>
                    <hr class="preview-divider">
                    <div class="compression-not-needed-alert">
                        <i class="fas fa-info-circle"></i> 
                        <strong>No compression needed.</strong> {{ compressionReason }}
                    </div>
                </div>
            </div>
        </div>
        <div v-else-if="currentPreviewFile()" class="no-preview-container">
            <div class="no-preview-content">
                <div class="no-preview-emoji">📄</div>
                <p class="no-preview-title">No preview available for this file type</p>
                <small class="no-preview-filename">{{ currentPreviewFile().name }}</small>
            </div>
        </div>

        <el-upload
            :drag="true"
            :multiple="true"
            :auto-upload="false"
            ref="uploadRef"
            :data="getUploadData"
            :before-upload="beforeUpload"
            @change="handleFileChange"
            :on-error="handleUploadError"
            :on-success="handleUploadSuccess"
            :limit="maxFiles"
            :accept="accept"
            :headers="{ accept: 'application/json' }"
            :action="vimeoUrl ? vimeoUrl : DEFAULT_ACTION_URL"
            :method="vimeoUrl ? 'PUT' : 'POST'"
            :data-testid="DATA_TEST_ID.UPLOAD_ELEMENT"
            :http-request="httpRequest"
        >
            <el-icon class="el-icon--upload" :data-testid="DATA_TEST_ID.UPLOAD_ICON"><upload-filled /></el-icon>
            <div class="el-upload__text" :data-testid="DATA_TEST_ID.UPLOAD_TEXT">
            Drop file here or <em>click to upload</em>
            </div>
        </el-upload>

        <div v-if="uploadedFiles.length > 0" class="uploaded-files-list">
            <h4>Uploaded Files:</h4>
            <div v-for="file in uploadedFiles" :key="file.fileId" class="uploaded-file-item">
                <div class="file-name">{{ file.name }} (fileId: {{ file.fileId }})</div>
                <div class="file-syntax-row">
                    <code>{{ file.syntax }}</code>
                    <button
                        @click="copyToClipboard(file.syntax)"
                        class="copy-button"
                        title="Copy to clipboard"
                    >
                        Copy
                    </button>
                </div>
            </div>
        </div>

        <el-button type="primary" @click="submitUpload" :data-testid="DATA_TEST_ID.SUBMIT_BUTTON" :disabled="isCompressing">
            <span v-if="isCompressing">Compressing...</span>
            <span v-else-if="!insertIntoEditor">Upload</span>
            <span v-else>Insert</span>
        </el-button>
    </ConfigWrapper>
</template>