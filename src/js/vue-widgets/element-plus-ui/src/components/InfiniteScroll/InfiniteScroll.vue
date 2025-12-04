<script setup>
import { ref, computed, watchEffect } from "vue";
import { Loading } from "@element-plus/icons-vue";
import mirrorInputs from "../../helpers/infiniteScroll/mirrorInputs";

const props = defineProps(["containerId", "targetContainerId", "offset", "step", "loadUrl", "totalCount", "offsetArg", "_cssFiles"]);

const cssFiles = computed(() => props._cssFiles.split(","));

const scrollbar = ref(null);
const loading = ref(false);
const offset = ref(Number(props.offset) || 0);
const height = ref(0);
const itemsCount = ref(0);

const container = ref(null);
const disabled = computed(() => loading.value || (offset.value + Number(props.step)) >= Number(props.totalCount));
const loadUrl = computed(() => props.loadUrl || window.location.href);

watchEffect(() => {
    if (!scrollbar.value) return;
    const wrapper = scrollbar.value.wrapRef.querySelector(".el-scrollbar__view");
    const originalContainer = document.getElementById(props.containerId);
    if (!originalContainer) {
        console.error(`Container with id ${props.containerId} not found`);
        return;
    }
    height.value = originalContainer.clientHeight || DEFAULT_HEIGHT;

    mirrorInputs(originalContainer, scrollbar, props.containerId);

    itemsCount.value = originalContainer.querySelector(`#${props.targetContainerId}`)?.childElementCount || originalContainer.childElementCount;

    container.value = originalContainer;
    wrapper.appendChild(originalContainer);
});

const load = async (direction) => {
    if (disabled.value || direction !== "bottom") return;

    loading.value = true;

    try {
        offset.value += Number(props.step);
        const url = new URL(loadUrl.value);
        url.searchParams.set(props.offsetArg || "offset", offset.value);
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const html = await response.text();
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = html;
        const originalTargetContainer = tempDiv.querySelector(`#${props.targetContainerId || props.containerId}`);
        if (originalTargetContainer) {
            const content = document.createDocumentFragment();
            content.append(...originalTargetContainer.childNodes);

            mirrorInputs(content, scrollbar, props.containerId);

            if (props.targetContainerId) {
                container.value.querySelector(`#${props.targetContainerId}`).appendChild(content);
            } else {
                container.value.appendChild(content)
            }
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

<script>
export const DEFAULT_HEIGHT = 800;
export const DATA_TESTID = {
    LOADER: "infinite-scroll-loader",
    CSS_STYLE: "infinite-scroll-css-style",
}
</script>

<template>
    <template v-for="cssFile in cssFiles" :key="cssFile">
        <link rel="stylesheet" :href="cssFile" :data-testid="DATA_TESTID.CSS_STYLE" />
    </template>
    <el-scrollbar :height="itemsCount >= Number(step) && itemsCount < Number(totalCount) && `calc(${height}px - 35vh)`" max-height="80vh" @end-reached="load" ref="scrollbar"></el-scrollbar>
    <el-icon class="is-loading infinite-list-loader" v-if="loading" :data-testid="DATA_TESTID.LOADER">
        <Loading />
    </el-icon>
</template>