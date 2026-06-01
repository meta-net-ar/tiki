<script setup>
import { ElMessage } from "element-plus";
import Cropper from "cropperjs";
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";

/*
 * Cropper.js owns crop and transform geometry. Tiki's H5P image tool cannot be
 * reused independently because it depends on H5PEditor globals and dynamically
 * loaded Darkroom/Fabric assets.
 */
const props = defineProps({
    file: {
        type: File,
        required: true,
    },
});
const emit = defineEmits(["apply", "cancel"]);

const modalRef = ref(null);
const containerRef = ref(null);
const imageRef = ref(null);
const processing = ref(true);
const cropMode = ref(false);
const hasCrop = ref(false);
const grayscale = ref(false);
const inverted = ref(false);
const objectUrl = URL.createObjectURL(props.file);
let modal = null;
let modalShown = false;
let cropper = null;
let cropHandle = null;
let cropShade = null;
let initialState = null;
let cropSnapshot = null;
let pendingResult = null;

const getImage = () => cropper?.getCropperImage();
const getSelection = () => cropper?.getCropperSelection();
const getSelectionState = () => {
    const { x, y, width, height } = getSelection();
    return { x, y, width, height };
};
const getFilter = () => [grayscale.value ? "grayscale(100%)" : "", inverted.value ? "invert(100%)" : ""].filter(Boolean).join(" ");

const updatePreview = () => {
    const image = getImage();
    if (image) image.style.filter = getFilter();
};

const alignSelectionToImage = () => {
    const selection = getSelection();
    const hidden = selection.hidden;
    const canvasBounds = cropper.getCropperCanvas().getBoundingClientRect();
    const imageBounds = getImage().getBoundingClientRect();
    selection.$change(imageBounds.left - canvasBounds.left, imageBounds.top - canvasBounds.top, imageBounds.width, imageBounds.height);
    selection.hidden = hidden;
};

const selectionCoversImage = () => {
    const canvasBounds = cropper.getCropperCanvas().getBoundingClientRect();
    const imageBounds = getImage().getBoundingClientRect();
    const selection = getSelection();
    const imageArea = {
        x: imageBounds.left - canvasBounds.left,
        y: imageBounds.top - canvasBounds.top,
        width: imageBounds.width,
        height: imageBounds.height,
    };

    return Object.entries(imageArea).every(([property, value]) => Math.abs(selection[property] - value) <= 1);
};

const updateCropPreview = () => {
    const canvas = cropper?.getCropperCanvas();
    if (!canvas) return;
    if (cropMode.value || !hasCrop.value) {
        canvas.style.clipPath = "";
        return;
    }

    const { x, y, width, height } = getSelectionState();
    const bounds = canvas.getBoundingClientRect();
    const top = Math.max(0, y);
    const right = Math.max(0, bounds.width - x - width);
    const bottom = Math.max(0, bounds.height - y - height);
    const left = Math.max(0, x);
    canvas.style.clipPath = `inset(${top}px ${right}px ${bottom}px ${left}px)`;
};

const initialize = async () => {
    if (cropper) return;
    processing.value = true;
    let instance;

    try {
        instance = new Cropper(imageRef.value, { container: containerRef.value });
        cropper = instance;
        await instance.getCropperImage().$ready();
        await nextTick();
        if (cropper !== instance) return;
        const canvas = instance.getCropperCanvas();
        canvas.style.height = "100%";
        canvas.style.width = "100%";
        cropHandle = canvas.querySelector('cropper-handle[action="select"]');
        cropShade = canvas.querySelector("cropper-shade");
        if (cropHandle) cropHandle.action = "none";
        alignSelectionToImage();
        initialState = {
            matrix: [...getImage().$getTransform()],
            selection: getSelectionState(),
        };
        getSelection().hidden = true;
        if (cropShade) cropShade.hidden = true;
    } catch (error) {
        if (instance && cropper !== instance) return;
        console.error("Could not load image for editing", error);
        ElMessage.error("Could not load image for editing");
        modal?.hide();
    } finally {
        if (!instance || cropper === instance) processing.value = false;
    }
};

const handleImageLoad = () => {
    if (modalShown) initialize();
};

const startCrop = () => {
    cropSnapshot = getSelectionState();
    cropMode.value = true;
    updateCropPreview();
    if (cropHandle) cropHandle.action = "select";
    getSelection().hidden = false;
    if (cropShade) cropShade.hidden = false;
};

const applyCrop = () => {
    const selection = getSelection();
    if (!selection.width || !selection.height) return;
    hasCrop.value = !selectionCoversImage();
    cropMode.value = false;
    if (cropHandle) cropHandle.action = "none";
    selection.hidden = true;
    if (cropShade) cropShade.hidden = true;
    updateCropPreview();
};

const cancelCrop = () => {
    if (cropSnapshot) {
        const { x, y, width, height } = cropSnapshot;
        getSelection().$change(x, y, width, height);
    }
    cropMode.value = false;
    if (cropHandle) cropHandle.action = "none";
    getSelection().hidden = true;
    if (cropShade) cropShade.hidden = true;
    updateCropPreview();
};

const rotate = async (degrees) => {
    const resetSelection = selectionCoversImage();
    const image = getImage();
    image.$rotate(`${degrees}deg`);
    image.$center("contain");
    await nextTick();
    if (resetSelection) alignSelectionToImage();
};

const flip = (horizontal) => {
    getImage().$scale(horizontal ? -1 : 1, horizontal ? 1 : -1);
};

const toggleEffect = (effect) => {
    if (effect === "grayscale") grayscale.value = !grayscale.value;
    else inverted.value = !inverted.value;
    updatePreview();
};

const reset = () => {
    getImage().$setTransform(initialState.matrix);
    const { x, y, width, height } = initialState.selection;
    getSelection().$change(x, y, width, height);
    getSelection().hidden = true;
    cropMode.value = false;
    if (cropHandle) cropHandle.action = "none";
    if (cropShade) cropShade.hidden = true;
    hasCrop.value = false;
    updateCropPreview();
    grayscale.value = false;
    inverted.value = false;
    updatePreview();
};

const canvasToBlob = (canvas, type) =>
    new Promise((resolve, reject) => {
        canvas.toBlob((blob) => (blob ? resolve(blob) : reject(new Error("Could not encode image"))), type, 0.92);
    });

const apply = async () => {
    const selection = getSelection();
    if (cropMode.value || !selection?.width || !selection?.height) return;

    processing.value = true;
    try {
        const type = props.file.type || "image/png";
        const [a, b] = getImage().$getTransform();
        const imageScale = Math.hypot(a, b) || 1;
        const canvas = await selection.$toCanvas({
            width: Math.max(1, Math.round(selection.width / imageScale)),
            beforeDraw(context, targetCanvas) {
                if (type === "image/jpeg") {
                    context.fillStyle = "#fff";
                    context.fillRect(0, 0, targetCanvas.width, targetCanvas.height);
                }
                context.filter = getFilter() || "none";
            },
        });
        if (!cropper) return;
        const blob = await canvasToBlob(canvas, type);
        if (!cropper) return;
        const file = new File([blob], props.file.name, { type, lastModified: Date.now() });
        file.uid = props.file.uid;
        pendingResult = file;
        modal.hide();
    } catch (error) {
        console.error("Could not apply image edits", error);
        ElMessage.error("Could not apply image edits");
    } finally {
        if (cropper) processing.value = false;
    }
};

const handleShown = () => {
    modalShown = true;
    if (imageRef.value?.complete && imageRef.value.naturalWidth) initialize();
};

const handleHidden = () => {
    if (pendingResult) emit("apply", pendingResult);
    else emit("cancel");
};

onMounted(() => {
    modalRef.value.addEventListener("shown.bs.modal", handleShown);
    modalRef.value.addEventListener("hidden.bs.modal", handleHidden);
    modal = window.bootstrap.Modal.getOrCreateInstance(modalRef.value);
    modal.show();
});

onBeforeUnmount(() => {
    modalRef.value?.removeEventListener("shown.bs.modal", handleShown);
    modalRef.value?.removeEventListener("hidden.bs.modal", handleHidden);
    modal?.dispose();
    const instance = cropper;
    cropper = null;
    instance?.destroy();
    URL.revokeObjectURL(objectUrl);
});
</script>

<template>
    <Teleport to="body">
        <div ref="modalRef" class="modal fade" tabindex="-1" aria-labelledby="image-editor-title" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="image-editor-title" class="modal-title">Edit image</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-0">
                        <div class="row g-0">
                            <div class="col-lg-9">
                                <div ref="containerRef" style="height: min(70vh, 600px); min-height: 320px">
                                    <img ref="imageRef" class="d-block mw-100" :src="objectUrl" :alt="`Edit ${file.name}`" @load="handleImageLoad" />
                                </div>
                            </div>

                            <aside class="col-lg-3 border-start p-3 d-grid gap-2 align-content-start">
                                <button
                                    v-if="!cropMode"
                                    type="button"
                                    class="btn btn-outline-secondary text-start"
                                    :disabled="processing"
                                    @click="startCrop"
                                >
                                    {{ hasCrop ? "Edit crop" : "Crop" }}
                                </button>
                                <template v-else>
                                    <button type="button" class="btn btn-primary text-start" :disabled="processing" @click="applyCrop">
                                        Apply crop
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary text-start" :disabled="processing" @click="cancelCrop">
                                        Cancel crop
                                    </button>
                                </template>
                                <button type="button" class="btn btn-outline-secondary text-start" :disabled="processing" @click="rotate(-90)">
                                    Rotate left
                                </button>
                                <button type="button" class="btn btn-outline-secondary text-start" :disabled="processing" @click="rotate(90)">
                                    Rotate right
                                </button>
                                <button type="button" class="btn btn-outline-secondary text-start" :disabled="processing" @click="flip(true)">
                                    Flip horizontal
                                </button>
                                <button type="button" class="btn btn-outline-secondary text-start" :disabled="processing" @click="flip(false)">
                                    Flip vertical
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary text-start"
                                    :class="{ active: grayscale }"
                                    :aria-pressed="grayscale"
                                    :disabled="processing"
                                    @click="toggleEffect('grayscale')"
                                >
                                    Grayscale
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary text-start"
                                    :class="{ active: inverted }"
                                    :aria-pressed="inverted"
                                    :disabled="processing"
                                    @click="toggleEffect('invert')"
                                >
                                    Invert colors
                                </button>
                                <button type="button" class="btn btn-outline-secondary text-start" :disabled="processing" @click="reset">
                                    Reset
                                </button>
                            </aside>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" :disabled="processing || cropMode" @click="apply">Apply changes</button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
