<script setup>
import { onMounted, shallowRef } from 'vue';
import getBasePath from '../helpers/getBasePath';

const props = defineProps({
    language: {
        type: String,
        default: 'en',
    },
    minuteStep: Number,
    enforceStep: Number,
});

const locale = shallowRef(null);

const loadLocale = async (localeName) => {
    try {
        const importedLocale = await import(`${getBasePath()}/public/generated/js/vendor_dist/element-plus/dist/locale/${localeName}.min.mjs`);
        locale.value = importedLocale.default;
    } catch (error) {
        console.error('Error loading locale:', error);
    }
};

onMounted(() => {
    loadLocale(props.language);
});
</script>

<template>
    <el-config-provider :locale="locale">
        <slot />
    </el-config-provider>
</template>