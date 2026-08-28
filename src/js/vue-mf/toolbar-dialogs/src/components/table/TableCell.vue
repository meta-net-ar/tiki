<script setup>
import { ref } from "vue";
import { cells } from './store.js'

const props = defineProps({
  row: Number,
  col: Number,
  isMarkdown: Boolean,
  table: ref,
});

const myRow = ref(props.row)
const myCol = ref(props.col)

function update(e) {
    cells.value[myRow.value][myCol.value] = e.target.value.trim()
}

function alignmentMarker(align, length) {
    const hyphenLength = Math.max(length - (align === "center" ? 2 : 1), 3);
    const hyphens = "-".repeat(hyphenLength);
    switch (align) {
        case "right":
            return hyphens + ":";
        case "center":
            return ":" + hyphens + ":";
        case "left":
        default:
            return ":" + hyphens;
    }
}

function alignCol(align) {
    let length = cells.value[myRow.value][myCol.value].length;
    let $columnInputs = $(props.table).find("tr td:nth-child(" + (myCol.value + 1) + ") input");
    cells.value[myRow.value][myCol.value] = alignmentMarker(align, length);

    switch (align) {
        case "right":
            $columnInputs.removeClass("text-center text-start").addClass("text-end")
            break
        case "center":
            $columnInputs.removeClass("text-end text-start").addClass("text-center")
            break
        case "left":
        default:
            $columnInputs.removeClass("text-center text-end").addClass("text-start")
            break
    }

    return false
}

function getAlign() {
    const val = cells.value[myRow.value][myCol.value];
    if (val.startsWith(':') && val.endsWith(':')) {
        return "center"
    } else if (val.endsWith(':')) {
        return "right"
    } else {
        return "left"
    }
}

</script>

<template>
  <input
    v-if="myRow !== 1 || ! isMarkdown"
    :value="cells[myRow][myCol]"
    @change="update"
    @blur="update"
    @vnode-mounted="({ el }) => el.focus()"
    class="form-control form-control-sm"
    :title="myCol + ':' + myRow"
    ref="myInput"
  >
    <div v-if="myRow === 1 && isMarkdown" class="d-flex">
        <a href="#"
           @click="alignCol('left')"
           class="btn btn-sm flex-fill"
        >
            <i :class="'fa-solid fa-align-left' + (getAlign() === 'left' ? ' text-primary' : ' text-muted')"></i>
        </a>
        <a href="#"
           @click="alignCol('center')"
           class="btn btn-sm flex-fill"
        >
            <i :class="'fa-solid fa-align-center' + (getAlign() === 'center' ? ' text-primary' : ' text-muted')"></i>
        </a>
        <a href="#"
           @click="alignCol('right')"
           class="btn btn-sm flex-fill"
        >
            <i :class="'fa-solid fa-align-right' + (getAlign() === 'right' ? ' text-primary' : ' text-muted')"></i>
        </a>
    </div>
</template>
