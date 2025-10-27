<script setup>
import { ref, computed, watch } from "vue";
import { Loading } from "@element-plus/icons-vue";

const props = defineProps(["containerId", "targetContainerId", "offset", "step", "loadUrl", "totalCount", "_bootstrapThemeFile", "_height"]);

const wrapper = ref(null);
const loading = ref(false);
const offset = ref(Number(props.offset) || 0);

const container = computed(() => wrapper.value?.querySelector(`#${props.containerId}`));
const disabled = computed(() => loading.value || (offset.value + Number(props.step)) >= Number(props.totalCount));
const loadUrl = computed(() => props.loadUrl || window.location.href);

watch(() => {
    if (!wrapper.value) return;

    const originalContainer = document.getElementById(props.containerId);
    if (!originalContainer) {
        console.error(`Container with id ${props.containerId} not found`);
        return;
    }
    wrapper.value.appendChild(originalContainer);
});

const load = async () => {
    if (disabled.value) return;

    loading.value = true;

    try {
        offset.value += Number(props.step);
        const url = new URL(loadUrl.value);
        url.searchParams.set("offset", offset.value);
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const html = await response.text();
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = html;
        const originalTargetContainer = tempDiv.querySelector(`#${props.targetContainerId}`);
        if (originalTargetContainer) {
            container.value.querySelector(`#${props.targetContainerId}`).insertAdjacentHTML("beforeend", originalTargetContainer.innerHTML);
        }
    } catch (error) {
        offset.value -= Number(props.step);
        showMessage(tr("Error loading more items"), "error");
        console.error("Error loading more items:", error);
    } finally {
        loading.value = false;
    }
}

</script>

<template>
    <link rel="stylesheet" :href="_bootstrapThemeFile" />
    <div ref="wrapper" v-loading="loading" class="infinite-list-wrapper" v-infinite-scroll="load" :infinite-scroll-disabled="disabled" :style="`height: ${_height}px`"></div>
    <el-icon class="is-loading infinite-list-loader" v-if="loading">
        <Loading />
    </el-icon>
</template>