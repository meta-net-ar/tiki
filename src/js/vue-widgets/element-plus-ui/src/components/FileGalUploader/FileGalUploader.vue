<script setup>
import { UploadFilled } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { ajaxUpload as defaultHttpRequest } from 'element-plus/es/components/upload/src/ajax';
import { ref, onMounted } from 'vue'
import getUploadData from '../../helpers/fileGalUploader/getUploadData';
import ConfigWrapper from '../ConfigWrapper.vue';
import handleTikiFeedback from '../../helpers/fileGalUploader/handleTikiFeedback';
import getUploadAjaxError from '../../helpers/fileGalUploader/getUploadAjaxError';
import { compressFileList } from '../../../../../tiki-image-compressor/imageCompressor.js';
import initializeCompressionUI, { getCompressionSettings } from '../../../../../tiki-image-compressor/compressionControls.js';

const props = defineProps([
    'accept',
    'maxSize',
    'maxFiles',
    'maxWidth',
    'maxHeight',
    'vimeoUrl',
    'language',
    'compression_enabled',
    'compression_preset_type',
    'compression_quality',
    'compression_max_dimension',
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

onMounted(async () => {
    const searcParams = new URLSearchParams(location.search);
    if (searcParams.get('filegals_manager')) {
        insertIntoEditor.value = true;
    }

    if (props.compression_enabled === 'true') {

        const prefs = {
            preset: props.compression_preset_type || 'slider',
            quality: parseInt(props.compression_quality, 10) || 85,
            maxDimension: parseInt(props.compression_max_dimension, 10) || 1600,
            maxSize: parseFloat(props.compression_max_size_mb) || 1,
            saveOriginal: props.compression_save_original,
        };

        await initializeCompressionUI(prefs);
    }
})

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

        ElMessage.info(`Compressing file ${rawFile.name}...`);
        const compressedFiles = await compressFileList([rawFile], getCompressionSettings().current);

        if (compressedFiles?.files?.length) {
            const compressedFile = compressedFiles.files[0];
            console.log('[FileGalUploader] Replacing upload file', {original: rawFile.size, compressed: compressedFile.size});
            ElMessage.success(`File ${rawFile.name} compressed successfully (${(rawFile.size / 1000).toFixed(2)}KB → ${(compressedFile.size / 1000).toFixed(2)}KB)`);

            return compressedFile;
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

const handleUploadSuccess = (response, file) => {
    ElMessage.success(`${file.name} uploaded successfully`)

    const syntax = response.syntax || `{img fileId="${response.fileId}" thumb="box"}`;

    // Store uploaded file info
    uploadedFiles.value.push({
        name: file.name,
        fileId: response.fileId,
        syntax: syntax
    });

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
        <el-upload
            :drag="true"
            :multiple="true"
            :auto-upload="false"
            ref="uploadRef"
            :data="getUploadData"
            :before-upload="beforeUpload"
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