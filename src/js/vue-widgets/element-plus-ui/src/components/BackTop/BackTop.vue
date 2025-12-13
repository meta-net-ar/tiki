<script setup>
import { onMounted, ref, nextTick } from 'vue';
import ConfigWrapper from '../ConfigWrapper.vue';

const props = defineProps({
    target: {
        type: String,
        default: ''
    },
    placement: {
        type: String,
        default: 'right'
    },
    sideDistance: {
        type: Number,
        default: 40
    },
    bottom: {
        type: Number,
        default: 40
    },
    visibilityHeight: {
        type: Number,
        default: 0
    },
    title: {
        type: Number,
        default: 'Back to top'
    },
    language: {
        type: String,
        default: 'en'
    }
});

const backtopRef = ref(null);

onMounted(async () => {
    await nextTick();
    const backtop = backtopRef.value?.$el;

    if (props.placement === "left" && backtop) {
        backtop.style.right = "auto";
        backtop.style.left = props.sideDistance;
    }
});
</script>

<template>
    <ConfigWrapper :language="language">
        <el-backtop ref="backtopRef" :title="title" :placement="placement" :right="right" :bottom="bottom" :visibility-height="visibilityHeight" :target="target" />
    </ConfigWrapper>
</template>