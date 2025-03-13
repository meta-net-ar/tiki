<script setup>

// see https://vuejs.org/examples/#cells

import TableCell from "./TableCell.vue"
import { ref, computed, onMounted } from "vue"
import { cells } from "./store.js"

const props = defineProps({
    toolbarObject: {
        type: Object,
        required: true,
    },
});

const tableBuilderTable = ref();

const toolbarObject = computed(() => props.toolbarObject);

function addRow() {
    cells.value.push(Array(cells.value[0].length).fill(""))
    const tm = setTimeout(function () {
        $("tr:nth(" + (cells.value.length - 1) + ") td", tableBuilderTable.value).first().find("input").trigger("focus");
        clearTimeout(tm);
    }, 10)
}

function addCol() {
    cells.value.forEach((row) => {
        row.push("")
    })
}

function deleteRow(row) {
    //cells = cells.slice(0); // make copy
    cells.value.splice(row, 1);
}

function deleteCol(col) {
    cells.value.forEach(row => row.splice(col, 1))
}

function _shown() {
    let selection = getTASelection($("#" + toolbarObject.value.domElementId).get(0));

    let lines = []
    cells.value = []

    if (toolbarObject.value.editor.isMarkdown) {
        // thanks to a clever comment on https://stackoverflow.com/a/29616512/2459703
        lines = selection.match(/((?:\|[^|\r\n]*)+\|(?:\r?\n|\r)?)/gs)
        if (lines) {
            lines.forEach(function (line) {
                let parts = line.split("|").map(part => part.trim()).filter(cell => cell.length)
                cells.value.push(parts)
            })
        }
    } else {
        lines = selection.match(/\|\|(.*?)\|\|/sm)
        if (lines) {
            lines = lines[1].split(/[\r\n]+/)
            lines.forEach(function (line) {
                let parts = line.split("|").map(part => part.trim())
                cells.value.push(parts)
            })
        }
    }

    if (! cells.value.length) {
        cells.value.push(Array(3).fill(""))
        if (toolbarObject.value.editor.isMarkdown) {
            cells.value.push(Array(3).fill(""))

        }
    }
    // click the aligned buttons to set the input alignments
    const tm = setTimeout(function () {
        $(".text-primary", tableBuilderTable.value).trigger("click");
        clearTimeout(tm);
    }, 10)
}

function _insert() {
    let output = "";
    if (toolbarObject.value.editor.isMarkdown) {
        cells.value.forEach(function (row) {
            output += "| " + row.join(" | ") + " |\n"
        })
        output = output.replace("  |", " |").replace("|  ", "| ")
        output = output.substring(0, output.length - 1)
    } else {
        output = "|| ";
        cells.value.forEach(function (row) {
            output += row.join(" | ") + "\n"
        })
        output = output.replace("  |", " |").replace("|  ", "| ")
        output = output.substring(0, output.length - 1) + " ||"
    }
    output = '{DIV(class="table-responsive")}' + output + '{DIV}'
    insertAt(toolbarObject.value.domElementId, output, true, false, true)

    return output
}

onMounted(_shown);

defineExpose({ execute: _insert, shown: _shown });
</script>

<template>
    <table class="w-100 table table-sm table-borderless" ref="tableBuilderTable">
      <tbody>
        <tr v-for="i in cells.length">
          <td v-for="(c, j) in cells[0]">
            <TableCell :row="i - 1" :col="j" :is-markdown="toolbarObject.editor.isMarkdown" :table="tableBuilderTable"></TableCell>
          </td>
          <td class="text-nowrap">
            <a v-if="cells.length > 1 && (i !== 2 || ! toolbarObject.editor.isMarkdown)" @click="deleteRow(i - 1)" class="btn btn-sm btn-link p-0">
                <i class="fa-solid fa-circle-minus text-danger"></i>
            </a>
            <a v-if="i === cells.length" @click="addRow()" title="Add row" class="btn btn-sm btn-link p-0 tips">
                <i class="fa-solid fa-circle-plus text-success"></i>
            </a>
          </td>
        </tr>
        <tr>
          <td v-for="(c, j) in cells[0]" class="text-nowrap">
            <a v-if="cells[0].length > 1" @click="deleteCol(j)" class="btn btn-sm btn-link p-0">
              <i class="fa-solid fa-circle-minus text-danger"></i>
            </a>
            <a v-if="j === cells[0].length - 1" @click="addCol()" title="Add column" class="btn btn-sm btn-link p-0">
                <i class="fa-solid fa-circle-plus text-success"></i>
            </a>
          </td>
        </tr>
      </tbody>
    </table>
</template>
