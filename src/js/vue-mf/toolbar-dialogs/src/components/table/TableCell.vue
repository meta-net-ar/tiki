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

function alignCol(align) {
    let length = cells.value[myRow.value][myCol.value].length
    let $columnInputs = $(props.table).find("tr td:nth-child(" + (myCol.value + 1) + ") input")

    switch (align) {
        case "right":
            cells.value[myRow.value][myCol.value] = "-".repeat(length - 1) + ":"
            $columnInputs.removeClass("text-center text-start").addClass("text-end")
            break
        case "center":
            cells.value[myRow.value][myCol.value] = ":" + "-".repeat(length - 2) + ":"
            $columnInputs.removeClass("text-end text-start").addClass("text-center")
            break
        case "left":
        default:
            cells.value[myRow.value][myCol.value] = ":" + "-".repeat(length - 1)
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
