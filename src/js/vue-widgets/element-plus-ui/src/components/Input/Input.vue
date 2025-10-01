<script setup>
import { ref, watchEffect } from "vue";
import * as Icons from "@element-plus/icons-vue";
import ConfigWrapper from "../ConfigWrapper.vue";

defineOptions({ inheritAttrs: false });

const props = defineProps([
    "_emit",
    "_expose",
    "placeholder",
    "value",
    "prefixIcon",
    "suffixIcon",
    "clearable",
    "showPassword",
    "autocomplete",
    "name",
    "disabled",
    "isInvalid",
    "type",
    "prependText",
    "appendText",
]);
const showPassword = props.showPassword === "true";
const clearable = props.clearable === "true";
const prefixIcon = props.prefixIcon ? Icons[props.prefixIcon] : null;
const suffixIcon = props.suffixIcon ? Icons[props.suffixIcon] : null;

const isInvalid = ref(props.isInvalid === "true");
const modelValue = ref(props.value);

props._expose({ value: modelValue });

watchEffect(() => {
    isInvalid.value = props.isInvalid === "true";
    modelValue.value = props.value;
});
</script>

<script>
export const DATA_TEST_ID = {
    INPUT: "input",
};
</script>

<template>
    <ConfigWrapper language="en">
        <div :class="{ invalid: isInvalid }">
            <el-input
                v-model="modelValue"
                :placeholder="placeholder"
                :prefix-icon="prefixIcon"
                :suffix-icon="suffixIcon"
                :clearable="clearable"
                :show-password="showPassword"
                :autocomplete="autocomplete ?? 'on'"
                :name="name"
                :disabled="disabled"
                :type="type"
                @change="(value) => _emit('change', value)"
                @input="(value) => _emit('input', value)"
                @blur="() => _emit('blur')"
                @focus="() => _emit('focus')"
                @keyup.enter="() => _emit('enter')"
                @keyup="() => _emit('keyup')"
                @keydown="() => _emit('keydown')"
                :data-testid="DATA_TEST_ID.INPUT"
                v-bind="$attrs"
            >
            >
                <template #prepend v-if="prependText">{{ prependText }}</template>
                <template #append v-if="appendText">{{ appendText }}</template>
            </el-input>
        </div>
    </ConfigWrapper>
</template>
