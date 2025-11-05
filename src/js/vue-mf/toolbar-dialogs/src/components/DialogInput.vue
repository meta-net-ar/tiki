<script setup>
import { ref, watch } from "vue";
import { ElInput } from "element-plus";

defineProps({
    label: {
        type: String,
        required: true,
    },
});

const model = defineModel()

const isElementPlusActive = ref(!!window.elementPlus);

</script>

<template>
    <el-input
        :placeholder="label"
        v-model="model"
        v-if="isElementPlusActive"
    />
    <input
        v-model="model"
        class="form-control form-control-sm"
        type="text"
        :placeholder="label"
        v-else
    />
</template>
