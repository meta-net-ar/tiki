<script setup>
import { ref, watch, computed, onMounted, onUnmounted, watchEffect } from 'vue';
import Sortable from "sortablejs";
import { sortOptions } from '../../helpers/select/sortable';
import { startDrag, getDraggedItem, endDrag, onDragStateChange, isDragging, setRefusal, refusalFor, isRefusing, isCopyRequested } from '../../helpers/select/dragBetweenFields';
import ConfigWrapper from '../ConfigWrapper.vue';
import { usePropParsers } from '../../composables/usePropParsers'
const { normalize, parseValue } = usePropParsers()



defineOptions({ inheritAttrs: false })

const props = defineProps(['options', 'placeholder', 'emitValueChange', 'value', 'multiple', 'isInvalid', 'max', 'clearable', 'collapseTags', 'filterable', 'allowCreate', 'maxCollapseTags', 'ordering', 'group', 'language', 'size', 'remoteSourceUrl', 'dragGroup', 'allowedValues']);

const modelValue = ref(parseValue(props.value));

watch(() => props.value, (newValue) => {
    modelValue.value = parseValue(newValue);
});

const isInvalid = computed(() => normalize(props.isInvalid, false));
const normalizeBooleanAttribute = (value) => {
    const normalized = normalize(value, false);
    // HTML boolean attributes can be serialized as an empty string.
    return normalized === "" || Boolean(normalized);
}
const multiple = computed(() => normalizeBooleanAttribute(props.multiple))
const clearable = computed(() => normalize(props.clearable, false));
const collapseTags = computed(() => normalize(props.collapseTags, false));
const filterable = computed(() =>
    Boolean(props.remoteSourceUrl) || normalize(props.filterable, false)
);
const allowCreate = computed(() => normalize(props.allowCreate, false));
const grouped = computed(() => normalize(props.group, false));
const getOptionsProp = computed(() => {
    const parsedOptions = parseValue(props.options) || [];
    // Ensure we have an array to work with
    const optionsArray = Array.isArray(parsedOptions) ? parsedOptions : [];
    return optionsArray.reduce((acc, item) => {
        if (!grouped.value) {
            acc.push(item);
            return acc;
        }
        const group = acc.find(group => group.label === item.group);
        if (group) {
            group.options.push({
                label: item.label,
                value: item.value,
                disabled: item.disabled,
            });
        } else {
            acc.push({
                label: item.group,
                options: [{
                    label: item.label,
                    value: item.value,
                    disabled: item.disabled,
                }],
            });
        }
        return acc;
    }, []);
});
const options = ref([]);
const wrapperRef = ref(null);
const selectRef = ref(null);
const deduplicateOptionsByValue = (items) => {
    const seenValues = new Set();
    return items.filter((item) => {
        if (seenValues.has(item.value)) {
            return false;
        }
        seenValues.add(item.value);
        return true;
    });
};

const handleValueChange = (value) => {
    // check if the checkbox with id "intertrans-active" is checked
    const checkbox = document.getElementById("intertrans-active");
    if (!checkbox || !checkbox.checked) {
        props.emitValueChange({
            value,
        });
    }

    if (typeof window.vueselectIntertransHandler === "function") {
        const option = options.value.find(opt => opt.value === value);
        window.vueselectIntertransHandler(option); // on envoie le label
    }
};

const remoteMethod = async (query) => {
    if (!query) return;

    try {
        const url = new URL(props.remoteSourceUrl);
        url.searchParams.append("q", query);
        const response = await fetch(url.href, {
            headers: {
                Accept: "application/json",
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        const loadedOptions = data.map(item => (typeof item === "string" ? { value: item, label: item } : item));
        const newOptions = deduplicateOptionsByValue([
            ...options.value.filter(item => modelValue.value?.includes(item.value)),
            ...loadedOptions,
        ]);
        options.value = newOptions;
    } catch (error) {
        console.error('Error loading remote options:', error);
    }
};

const translateFn = window.tr ?? ((text) => text);

const refusalReason = ref('');
const carryingAValue = ref(false);
let stopListeningToDrags = null;
let draggedNodeHome;
let sortable = null;
const refusalMessage = computed(() => {
    if (refusalReason.value === 'duplicate') {
        return translateFn('This user is already selected in this field.');
    }
    if (refusalReason.value === 'not-offered') {
        return translateFn('This user does not belong to any group allowed for this field.');
    }
    return '';
});
/*
    The options of a field filtered by group hold whom it shows, not whom it may hold, and the
    filter is a way of searching: refusing on it would turn it into a rule.
*/
const acceptableValues = computed(() => {
    const allowed = parseValue(props.allowedValues);
    if (Array.isArray(allowed)) {
        return new Set(allowed);
    }
    return new Set((parseValue(props.options) || []).map((option) => option.value));
});

const stopRefusing = () => setRefusal(sortable, '');

const acceptsDrop = (to, from) => {
    if (to === from) {
        setRefusal(to, '');
        return true;
    }
    const item = getDraggedItem();
    if (! item) {
        return false;
    }
    if (modelValue.value.includes(item.value)) {
        setRefusal(to, 'duplicate');
        return false;
    }
    if (! acceptableValues.value.has(item.value)) {
        setRefusal(to, 'not-offered');
        return false;
    }
    setRefusal(to, '');
    return true;
};

/*
    A field crossed on the way takes the value in, and holds on to it when the field the user was
    aiming for turns it down. The refusal under the pointer at the release speaks for the drop as a
    whole, whichever field the value was left in.
*/
const handleDropFromOtherField = () => {
    const item = getDraggedItem();
    if (! item || isRefusing() || modelValue.value.includes(item.value)) {
        return;
    }
    modelValue.value = [...modelValue.value, item.value];
    handleValueChange(modelValue.value);
};

const handleTakenByOtherField = (event) => {
    const item = getDraggedItem();
    if (! item || isRefusing() || isCopyRequested(event?.originalEvent)) {
        return;
    }
    modelValue.value = modelValue.value.filter((value) => value !== item.value);
    handleValueChange(modelValue.value);
};

/*
    Vue draws the tags and keeps a map of where it put them, which the carrying breaks by moving
    one to another field: its next drawing lands on a node it no longer finds and throws, leaving
    the tags of both fields saying something other than their value. The node goes back where it
    was taken from, and the value alone tells the tags apart.
*/
const putTagBack = (event) => {
    if (event.from !== event.to && draggedNodeHome !== undefined) {
        event.from.insertBefore(event.item, draggedNodeHome);
    }
    draggedNodeHome = undefined;
};

onMounted(() => {
    try {
        const orderingConfig = props.ordering ? JSON.parse(props.ordering) : null;
        const dragGroup = props.dragGroup || '';
        if (! (orderingConfig || dragGroup) || ! multiple.value || ! wrapperRef.value) {
            return;
        }
        const selectionElement = wrapperRef.value.querySelector('.el-select__selection');
        if (! selectionElement) {
            return;
        }
        sortable = new Sortable(selectionElement, {
            animation: 150,
            sort: Boolean(orderingConfig),
            /*
                The wrapper holding the text input is no value of the field: it cannot be carried
                off, and it is not the last of them either, which is where a field ends for the
                drop landing past its values.
            */
            draggable: '.el-select__selected-item:not(.el-select__input-wrapper)',
            group: dragGroup ? { name: dragGroup, put: acceptsDrop } : undefined,
            /*
                Carries the values around itself instead of leaving it to the drag and drop of the
                browser, whose events say nothing of the modifier keys on Linux: a copy could never
                be told apart from a move. Plain mouse events answer for every system.
            */
            forceFallback: true,
            onStart: (event) => {
                draggedNodeHome = event.item.nextSibling;
                // Taking hold of a value opens the list of choices, which covers the field below
                selectRef.value?.blur();
                startDrag({ value: modelValue.value[event.oldIndex] });
            },
            onSort: (event) => {
                if (event.from !== event.to) {
                    return;
                }
                sortOptions(wrapperRef.value, JSON.parse(props.options));
            },
            onAdd: handleDropFromOtherField,
            onRemove: handleTakenByOtherField,
            onEnd: (event) => {
                putTagBack(event);
                endDrag();
            },
        });
        stopListeningToDrags = onDragStateChange(() => {
            carryingAValue.value = isDragging();
            refusalReason.value = refusalFor(sortable);
        });
        /*
            A field is asked whether it takes the value while the pointer is on it, never told the
            pointer went elsewhere, so its refusal would stay on the screen. Ordinary mouse events
            still reach it, what follows the pointer being closed to them.
        */
        wrapperRef.value.addEventListener('mouseleave', stopRefusing);
    } catch (error) {
        console.warn('Invalid ordering configuration:', error);
    }
})

onUnmounted(() => {
    stopListeningToDrags?.();
    wrapperRef.value?.removeEventListener('mouseleave', stopRefusing);
})

watchEffect(() => {
    options.value = getOptionsProp.value;
});
</script>

<script>
const uniqueId = new Date().getTime();
export const DATA_TEST_ID = {
    SELECT_WRAPPER: `select-wrapper-${uniqueId}`,
    SELECT_ELEMENT: `select-element-${uniqueId}`,
    SELECT_OPTION: `select-option-${uniqueId}`,
    SELECT_OPTION_GROUP: `select-option-group-${uniqueId}`,
    DROP_REFUSAL: `drop-refusal-${uniqueId}`,
};
</script>

<template>
    <ConfigWrapper :language="language">
        <div :class="{ 'invalid': isInvalid, 'carrying-a-value': carryingAValue, 'refusing-a-value': Boolean(refusalMessage) }"
            :data-testid="DATA_TEST_ID.SELECT_WRAPPER" ref="wrapperRef">
            <el-select ref="selectRef" v-model="modelValue" :multiple="multiple" :filterable="filterable" :allow-create="allowCreate"
                default-first-option :reserve-keyword="false" :placeholder="placeholder" :teleported="false" :automatic-dropdown="multiple"
                @change="handleValueChange" :multiple-limit="parseInt(max ?? 0, 10)" :clearable="clearable"
                :collapse-tags="collapseTags" :max-collapse-tags="parseInt(maxCollapseTags ?? 0, 10)" :size="size"
                :data-testid="DATA_TEST_ID.SELECT_ELEMENT" :remote-method="remoteMethod"
                :remote="Boolean(remoteSourceUrl)" v-bind="$attrs" :empty-values="[null, undefined]">
                <template v-if="grouped">
                    <el-option-group v-for="group in options" :key="group.label" :label="group.label"
                        :data-testid="DATA_TEST_ID.SELECT_OPTION_GROUP">
                        <el-option v-for="item in group.options" :key="item.value" :label="item.label"
                            :value="item.value" :disabled="item.disabled" :data-testid="DATA_TEST_ID.SELECT_OPTION" />
                    </el-option-group>
                </template>
                <template v-else>
                    <el-option v-for="item in options" :key="item.value" :label="item.label" :value="item.value"
                        :disabled="item.disabled" :data-testid="DATA_TEST_ID.SELECT_OPTION" />
                </template>
            </el-select>
            <div v-if="refusalMessage" class="drop-refusal" role="status" aria-live="polite"
                :data-testid="DATA_TEST_ID.DROP_REFUSAL">{{ refusalMessage }}</div>
        </div>
    </ConfigWrapper>
</template>
