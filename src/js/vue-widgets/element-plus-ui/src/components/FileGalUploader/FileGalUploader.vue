<script setup>
import { UploadFilled } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { ajaxUpload as defaultHttpRequest } from 'element-plus/es/components/upload/src/ajax';
import { ref, onMounted } from 'vue'
import getUploadData from '../../helpers/fileGalUploader/getUploadData';
import ConfigWrapper from '../ConfigWrapper.vue';
import handleTikiFeedback from '../../helpers/fileGalUploader/handleTikiFeedback';
import getUploadAjaxError from '../../helpers/fileGalUploader/getUploadAjaxError';

const props = defineProps(['accept', 'maxSize', 'maxFiles', 'maxWidth', 'maxHeight', 'vimeoUrl', 'language']);
const maxSize = JSON.parse(props.maxSize);
const maxFiles = JSON.parse(props.maxFiles);

const uploadRef = ref(null);
const insertIntoEditor = ref(false);
const uploadedFiles = ref([]);

onMounted(() => {
    const searcParams = new URLSearchParams(location.search);
    if (searcParams.get('filegals_manager')) {
        insertIntoEditor.value = true;
    }
})

const submitUpload = () => {
    uploadRef.value.submit();
}

const beforeUpload = (rawFile) => {
    if (rawFile.size > maxSize) {
        const maxSizeKB = props.maxSize / 1000;
        ElMessage.error(`File size cannot exceed ${maxSizeKB}KB`);
        return false;
    }
}

const handleUploadError = (error) => {
    ElMessage.error(error.message)
}

const handleUploadSuccess = (response, file) => {
    ElMessage.success(`${file.name} uploaded successfully`)

    // Store uploaded file info
    uploadedFiles.value.push({
        name: file.name,
        fileId: response.fileId,
        syntax: `{img fileId="${response.fileId}" thumb="box"}`
    });

    const searcParams = new URLSearchParams(location.search);
    if (insertIntoEditor.value) {
        window.opener.insertAt(searcParams.get('filegals_manager'), response.syntax, false, false, true);
        checkClose();
    }
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

        <el-button type="primary" @click="submitUpload" :data-testid="DATA_TEST_ID.SUBMIT_BUTTON">
        <span v-if="!insertIntoEditor">Upload</span>
        <span v-else>Insert</span>
        </el-button>
    </ConfigWrapper>
</template>