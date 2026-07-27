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

const isPopperElementInteraction = (event) => {
    const path = typeof event.composedPath === 'function' ? event.composedPath() : [];
    const target = path[0];

    const interactiveTags = [
        'A',
        'AREA',
        'AUDIO',
        'BUTTON',
        'DETAILS',
        'EMBED',
        'IFRAME',
        'IMG',
        'INPUT',
        'LABEL',
        'OPTION',
        'SELECT',
        'SUMMARY',
        'TEXTAREA',
        'VIDEO',
    ];

    if (interactiveTags.includes(target?.tagName)) {
        return false;
    }

    return path.some((node) => {
        if (!(node instanceof Element)) {
            return false;
        }

        return node.classList?.contains('el-popper');
    });
};

const handlePointerDownCapture = (event) => {
    // In Shadow DOM, clicking a dropdown item shifts focus away from the input, thus cancelling item selection.
    // This prevents the native focus change so Element Plus does not treat selection as blur.
    if (isPopperElementInteraction(event)) {
        event.preventDefault();
    }
};

onMounted(() => {
    loadLocale(props.language);
});
</script>

<template>
    <el-config-provider :locale="locale">
        <div @pointerdown="handlePointerDownCapture">
            <slot />
        </div>
    </el-config-provider>
</template>