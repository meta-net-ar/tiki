<script setup>
import { ref, computed, watch } from "vue";
import { Loading } from "@element-plus/icons-vue";

const props = defineProps(["containerId", "targetContainerId", "offset", "step", "loadUrl", "totalCount", "_bootstrapThemeFile"]);

const wrapper = ref(null);
const loading = ref(false);
const offset = ref(Number(props.offset) || 0);

const container = computed(() => wrapper.value?.querySelector(`#${props.containerId}`));
const disabled = computed(() => loading.value || (offset.value + Number(props.step)) >= Number(props.totalCount));
const loadUrl = computed(() => props.loadUrl || window.location.href);
const height = computed(() => container.value ? container.value.clientHeight: 0);

watch(() => {
    if (!wrapper.value) return;

    const originalContainer = document.getElementById(props.containerId);
    if (!originalContainer) {
        console.error(`Container with id ${props.containerId} not found`);
        return;
    }

    wrapper.value.appendChild(originalContainer);

    mirrorInputs(originalContainer);
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
            const content = document.createDocumentFragment();
            content.append(...originalTargetContainer.childNodes);

            mirrorInputs(content);

            container.value.querySelector(`#${props.targetContainerId}`).appendChild(content);
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
    const targetContainer = wrapper.value.getRootNode().host.parentElement;

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
    <link rel="stylesheet" :href="_bootstrapThemeFile" />
    <div ref="wrapper" v-loading="loading" class="infinite-list-wrapper" v-infinite-scroll="load" :infinite-scroll-disabled="disabled" :style="`height: calc(${height}px - 35vh);`"></div>
    <el-icon class="is-loading infinite-list-loader" v-if="loading">
        <Loading />
    </el-icon>
</template>