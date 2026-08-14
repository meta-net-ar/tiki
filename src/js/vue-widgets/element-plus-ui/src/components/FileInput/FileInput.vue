<script setup>
import { ref } from 'vue';
import ConfigWrapper from '../ConfigWrapper.vue';
import { UploadFilled } from '@element-plus/icons-vue'

const props = defineProps(['multiple', 'accept', '_emit']);

const fileList = ref([]);

const elUploadRef = ref(null);

const handleChange = (file, files) => {
    if (props.multiple) {
        props._emit('change', files);
        fileList.value = files;
    } else {
        props._emit('change', file);
        if (fileList.value.length) {
            elUploadRef.value.handleRemove(fileList.value[0]);
        }
        fileList.value = [file];
    }
}

</script>

<script>
export const DATA_TEST_ID = {
    FILE_INPUT: 'file-input',
    UPLOAD_ICON: 'upload-icon',
    UPLOAD_ICON_WRAPPER: 'upload-icon-wrapper',
};
</script>

<template>
    <ConfigWrapper locale="en">
        <el-upload
            :multiple="multiple"
            :accept="accept"
            :drag="true"
            :on-change="handleChange"
            :auto-upload="false"
            v-model="fileList"
            ref="elUploadRef"
            :data-testid="DATA_TEST_ID.FILE_INPUT"
        >
            <el-icon class="el-icon--upload" :data-testid="DATA_TEST_ID.UPLOAD_ICON_WRAPPER"><upload-filled :data-testid="DATA_TEST_ID.UPLOAD_ICON" /></el-icon>
        </el-upload>
    </ConfigWrapper>
</template>