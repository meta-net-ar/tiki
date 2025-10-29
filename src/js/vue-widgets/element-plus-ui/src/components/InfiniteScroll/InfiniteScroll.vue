<script setup>
import { ref, computed, watchEffect } from "vue";
import { Loading } from "@element-plus/icons-vue";

const props = defineProps(["containerId", "targetContainerId", "offset", "step", "loadUrl", "totalCount", "offsetArg", "_cssFiles"]);

const cssFiles = computed(() => props._cssFiles.split(","));

const scrollbar = ref(null);
const loading = ref(false);
const offset = ref(Number(props.offset) || 0);

const container = computed(() => scrollbar.value.wrapRef?.querySelector(`#${props.containerId}`));
const disabled = computed(() => loading.value || (offset.value + Number(props.step)) >= Number(props.totalCount));
const loadUrl = computed(() => props.loadUrl || window.location.href);
const height = ref(0);

watchEffect(() => {
    if (!scrollbar.value) return;
    const wrapper = scrollbar.value.wrapRef.querySelector(".el-scrollbar__view");
    const originalContainer = document.getElementById(props.containerId);
    if (!originalContainer) {
        console.error(`Container with id ${props.containerId} not found`);
        return;
    }
    height.value = originalContainer.clientHeight;
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

            mirrorInputs(content);

            if (props.targetContainerId) {
                container.value.querySelector(`#${props.targetContainerId}`).appendChild(content);
            } else {
                container.value.appendChild(content);
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

// Mirror out all inputs inside the container, so form state is preserved
const mirrorInputs = (sourceContainer) => {
    const inputs = sourceContainer.querySelectorAll("input, select, textarea");
    const targetContainer = scrollbar.value.wrapRef.getRootNode().host.parentElement;

    inputs.forEach(input => {
        const mirror = input.cloneNode(true);
        mirror.style.display = "none";
        targetContainer.appendChild(mirror);

        input.addEventListener("change", () => {
            if (mirror.type === "checkbox" || mirror.type === "radio") {
                mirror.checked = input.checked;
            } else {
                mirror.value = input.value;
            }
            mirror.dispatchEvent(new Event("change"));
        });

        // sync back independent mirror changes (usually triggered programmatically) to the original input
        $(mirror).on("change", () => {
            if (input.type === "checkbox" || input.type === "radio") {
                input.checked = mirror.checked;
            } else {
                input.value = mirror.value;
            }
        });
    });
};

</script>

<template>
    <template v-for="cssFile in cssFiles" :key="cssFile">
        <link rel="stylesheet" :href="cssFile" />
    </template>
    <el-scrollbar :height="`calc(${height}px - 35vh)`" max-height="80vh" @end-reached="load" ref="scrollbar"></el-scrollbar>
    <el-icon class="is-loading infinite-list-loader" v-if="loading">
        <Loading />
    </el-icon>
</template>