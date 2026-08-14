<script setup>
import { onMounted } from 'vue';
import { ElMessage } from 'element-plus'

const props = defineProps(['type', 'message', 'closable', 'duration', '_emit']);

onMounted(() => {
    ElMessage({
        type: props.type,
        message: props.message,
        duration: props.duration ? parseInt(props.duration): 3000,
        showClose: props.closable === 'true',
        onClose: () => {
            props._emit('close');
        }
    });
});
</script>
