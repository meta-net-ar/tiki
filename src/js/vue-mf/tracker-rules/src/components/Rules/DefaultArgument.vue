<template>
    <div>
        <el-input
            :placeholder="label"
            v-if="isElementPlusActive"
            v-model="modelValue"
            @change="$emit('change', modelValue)"
        />
        <input class="form-control" type="text" @change="$emit('change', $event.target.value)" :value="value" v-else />
    </div>
</template>

<script setup>
import { ref } from "vue";

defineOptions({ name: "DefaultArgument" });

defineEmits(["change"])

const props = defineProps({
    value: {
        type: null,
        required: true,
    },
});

const modelValue = ref(props.value);

const isElementPlusActive = ref(!!window.elementPlus);
</script>