<template>
    <!-- Modal -->
    <Teleport to="body">
        <div class="modal modal-lg fade" :id="`formAddTrackerItemModal${trackerId}`" tabindex="-1" role="dialog" aria-labelledby="formAddTrackerItemLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="formAddTrackerItemLabel">Create tracker item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Render the form here -->
                        <form>
                            <div v-if="tracker.options.show_status" class="tracker-field-group mb-3">
                                <label for="trackerinput_status">{{ tr('Status') }}</label>
                                <div id="trackerinput_status">
                                    <select name="status" class="form-control" v-model="editedItem.values.status">
                                        <option v-for="(stinfo, st) in tracker.options.status_types" :value="st" :class="`tracker-${stinfo.iconname}`">
                                            {{ stinfo.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="tracker-field-group mb-3" v-for="(field, index) in editableFields(formFields)">
                                <label v-if="! field.options_map.labelasplaceholder"
                                    :for="fieldId(field.fieldId)"
                                    :class="[field.type == 'h' ? `h${field.options_map.level}` : '']">
                                    {{field.name}}
                                    <strong class='mandatory_star text-danger tips' title=":This field is mandatory" v-if="field.isMandatory == 'y'">*</strong>
                                </label>
                                <div :id="fieldId(field.fieldId)">
                                    <component :is="fieldTypeToComponent(field)" :field="field" v-model="editedItem.values[field.ins_id]"></component>
                                    <div class="description form-text" v-if="field.description && field.type != 'S'">{{field.description}}</div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" @click="submitForm">
                            <span v-if="editedItem.itemId !== null">Update Item</span>
                            <span v-if="editedItem.itemId === null">Create Item</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
    <Button variant="primary" sm data-bs-toggle="modal" :data-bs-target="`#formAddTrackerItemModal${trackerId}`" @click="handleNewItem">
        <i class="fas fa-plus"></i>
        <span class="ml-2">Create item</span>
    </Button>
</template>

<script>
import { inject, toRaw } from 'vue'
import { Button } from '@vue-mf/styleguide';
import { addFormValidation } from '../../plugins/validation'
import store from '../../store';

// Import form field components
import AutoIncrement from '../Fields/AutoIncrement.vue';
import Checkbox from '../Fields/CheckBox.vue';
import Checkboxes from '../Fields/Checkboxes.vue';
import DateTime from '../Fields/DateTime.vue';
import Dropdown from '../Fields/Dropdown.vue';
import DynamicList from '../Fields/DynamicList.vue';
import Files from '../Fields/Files.vue';
import ItemLink from '../Fields/ItemLink.vue';
import ItemsList from '../Fields/ItemsList.vue';
import JsCalendar from '../Fields/JsCalendar.vue';
import Numeric from '../Fields/Numeric.vue';
import Radios from '../Fields/Radios.vue';
import Text from '../Fields/Text.vue';
import TextArea from '../Fields/TextArea.vue';
import UserSelector from '../Fields/UserSelector.vue';

export default {
    name: 'FormAddTrackerItem',
    // Props received from parent component
    props: {
        trackerId: Number,
        formFields: Array, // Configuration object defining form fields
    },
    // Registering child components
    components: {
        AutoIncrement,
        Button,
        Checkbox,
        Checkboxes,
        DateTime,
        Dropdown,
        DynamicList,
        Files,
        ItemLink,
        ItemsList,
        JsCalendar,
        Numeric,
        Radios,
        Text,
        TextArea,
        UserSelector,
    },
    data() {
        store.commit('changeEditedItem', {trackerId: this.trackerId, itemId: null})
        return {
            editedItem: store.state.editedItem[this.trackerId],
            tracker: store.getters.getTracker(this.trackerId)
        }
    },
    mounted() {
        if (jqueryTiki.validate) {
            addFormValidation(`#formAddTrackerItemModal${this.trackerId} form`, this.tracker)
        }
    },
    setup() {
        const dataSync = inject('dataSync')
        const tr = inject('tr')
        return { dataSync, tr }
    },
    methods: {
        submitForm() {
            if (! $(`#formAddTrackerItemModal${this.trackerId} form`).valid()) {
                return
            }
            store.commit('storeEditedItem', {trackerId: this.trackerId})
            store.commit('changeEditedItem', {trackerId: this.trackerId, itemId: null})
            this.dataSync(toRaw(store.getters.getOfflineTrackers()));
            $('#formAddTrackerItemModal' + this.trackerId).modal('hide')
        },
        handleNewItem() {
            store.commit('changeEditedItem', {trackerId: this.trackerId, itemId: null})
        },
        editableFields(fields) {
            return fields.filter(field => field.visibleInEditMode == 'y')
        },
        fieldId (fieldId) {
            return "trackerinput_" + fieldId
        },
        fieldTypeToComponent(field) {
            switch (field.type) {
                case 'a':
                    return TextArea;
                case 'c':
                    return Checkbox;
                case 'd':
                case 'D':
                    return Dropdown;
                case 'f':
                    return DateTime;
                case 'FG':
                    return Files;
                case 'j':
                    return JsCalendar;
                case 'l':
                    return ItemsList;
                case 'n':
                    return Numeric;
                case 'M':
                    return field.options_map.inputtype == 'm' ? Dropdown : Checkboxes;
                case 'q':
                    return AutoIncrement;
                case 'r':
                    return ItemLink;
                case 'R':
                    return Radios;
                case 't':
                    return Text;
                case 'u':
                    return UserSelector;
                case 'w':
                    return DynamicList;
            }
        }
    }
};
</script>
