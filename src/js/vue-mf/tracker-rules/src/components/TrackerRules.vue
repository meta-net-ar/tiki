<template>
    <div ref="tracker-predicates" class="tracker-rules-container">
        <div class="card mb-3">
            <div class="card-header">
                Conditions
            </div>
            <div class="card-body conditions">
                <ui-predicate 
                    v-model="conditionsData" 
                    :columns="conditionsColumns" 
                    :ui="ui"
                    @changed="onChangeConditions" 
                    @initialized="onPredicateInitialized($event, 'conditions')" 
                />
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">
                Actions
            </div>
            <div class="card-body actions">
                <ui-predicate 
                    v-model="actionsData" 
                    :columns="actionsColumns" 
                    :ui="ui" 
                    @changed="onChangeActions"
                    @initialized="onPredicateInitialized($event, 'actions')" 
                />
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">
                Else
                <button 
                    @click.prevent="handleInvertActions"
                    class="btn btn-info btn-xs float-end tips" 
                    title='Set "Else" to be the opposite of "Actions"'>
                    Invert Actions
                </button>
            </div>
            <div class="card-body else">
                <ui-predicate 
                    :key="`else-predicate-${elseKey}`" 
                    v-model="elseData" 
                    :columns="actionsColumns" 
                    :ui="ui"
                    @changed="onChangeElse"
                    @initialized="onPredicateInitialized($event, 'else')" 
                />
            </div>
        </div>

        <div class="card">
            <div class="card-body tips">
                <h5 class="card-title">Tips</h5>
                <div class="card=text">
                    <p>Tips: Use <code>alt + click</code> to create a sub-group.</p>
                    <p>
                        <button class="btn btn-warning btn-sm" @click.prevent="handleReset">Clear All</button>
                    </p>
                </div>
            </div>
        </div>

        <div class="card d-none">
            <div class="card-header">
                Conditions Output
            </div>
            <div class="card-content">
                <textarea name="conditions" class="form-control" readonly="readonly">{{ conditionsOutput }}</textarea>
            </div>
        </div>
        <div class="card d-none">
            <div class="card-header">
                Actions Output
            </div>
            <div class="card-content">
                <textarea name="actions" class="form-control" readonly="readonly">{{ actionOutput }}</textarea>
            </div>
        </div>
        <div class="card d-none">
            <div class="card-header">
                Else Output
            </div>
            <div class="card-content">
                <textarea name="else" class="form-control" readonly="readonly">{{ elseOutput }}</textarea>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, reactive, useTemplateRef, nextTick, onBeforeMount } from 'vue';

/** UI PREDICATE DEFAULT COMPONENT OVERRIDES */
import PredicateAdd from "./Overrides/PredicateAdd.vue";
import PredicateRemove from "./Overrides/PredicateRemove.vue";
import PredicateTargets from "./Overrides/PredicateTargets.vue";
import PredicateOperators from "./Overrides/PredicateOperators.vue";
import PredicateLogicalTypes from "./Overrides/PredicateLogicalTypes.vue";
/** END UI PREDICATE DEFAULT COMPONENT OVERRIDES */

import BoolArgument from './Rules/BoolArgument.vue';
import TextArgument from './Rules/TextArgument.vue';
import DateArgument from './Rules/DateArgument.vue';
import NumberArgument from './Rules/NumberArgument.vue';
import NothingArgument from './Rules/NothingArgument.vue';
import DefaultArgument from './Rules/DefaultArgument.vue';
import CollectionArgument from './Rules/CollectionArgument.vue';

// defineOptions({ name: "TrackerRules" });

const props = defineProps({
    trackerRulesObject: {
        type: Object,
        required: true,
    },
});

const conditionsData = ref(null);
const actionsData = ref(null);
const elseData = ref(null);

const conditionsOutput = ref({});
const actionOutput = ref({});
const elseOutput = ref({});

const elseKey = ref(0);

const trackerPredicateRef = useTemplateRef('tracker-predicates');

const rules = computed(() => props.trackerRulesObject.rules);
const fieldId = computed(() => props.trackerRulesObject.fieldId);
const fieldType = computed(() => props.trackerRulesObject.fieldType);
const definition = computed(() => props.trackerRulesObject.definition);
const targetFields = computed(() => props.trackerRulesObject.targetFields);

// UI Predicate overrides
const ui = {
    ['TARGETS']: PredicateTargets,
    ['LOGICAL_TYPES']: PredicateLogicalTypes,
    ['OPERATORS']: PredicateOperators,
    ['PREDICATE_ADD']: PredicateAdd,
    ['PREDICATE_REMOVE']: PredicateRemove,
    // If UIPredicate can't find a component related to your argumentType_id
    // This component will be used as a fallback.
    // By default it just an <input type="text">
    ['ARGUMENT_DEFAULT']: DefaultArgument
}

const conditionsColumns = {
    targets: null,
    // besides array list names, everything else follows convention
    // https://github.com/FGRibreau/sql-convention
    operators: null,
    types: null,
    logicalTypes: [
        {
            logicalType_id: "any",
            label: "Any",
        },
        {
            logicalType_id: "all",
            label: "All",
        },
        {
            logicalType_id: "none",
            label: "None",
        },
    ],
    argumentTypes: [
        {
            argumentType_id: "DateTime",
            component: DateArgument,
        },
        {
            argumentType_id: "Text",
            component: TextArgument,
        },
        {
            argumentType_id: "Number",
            component: NumberArgument,
        },
        {
            argumentType_id: "Boolean",
            component: BoolArgument,
        },
        {
            argumentType_id: "Nothing",
            component: NothingArgument,
        },
        {
            argumentType_id: "Collection",
            component: CollectionArgument,
        },
    ],
}

const actionsColumns = {
    targets: null,
    operators: null,
    types: null,
    // TODO logicalTypes should be removed for actions
    logicalTypes: [
        {
            logicalType_id: "any",
            label: "Any",
        },
        {
            logicalType_id: "all",
            label: "All",
        },
        {
            logicalType_id: "none",
            label: "None",
        },
    ],
    argumentTypes: [
        {
            argumentType_id: "Nothing",
            component: NothingArgument,
        },
    ],
}

const onChangeConditions = (data) => {
    conditionsOutput.value = data;
}

const onChangeActions = (data) => {
    actionOutput.value = data;
}

const onChangeElse = (data) => {
    elseOutput.value = data;
}

const onPredicateInitialized = (control, type) => {
    if (typeof control.toJSON === "function") {
        const data = control.toJSON();

        switch (type) {
            case "conditions":
                conditionsOutput.value = data;
                break;
            case "actions":
                actionOutput.value = data;
                break;
            case "else":
                elseOutput.value = data;
                break;
        }
    }
}

const handleReset = () => {

    conditionsOutput.value = "";
    actionOutput.value = "";
    elseOutput.value = "";
    conditionsData.value = "";
    actionsData.value = "";
    elseData.value = "";

    // $(trackerPredicateRef.value).find(".card-body:not(.tips)").empty();
}

const handleInvertActions = () => {

    // Convert actions to a plain JS object
    const actions = typeof actionOutput.value.toJSON === "function"
        ? actionOutput.value.toJSON()
        : actionOutput.value;

    if (!actions || !Array.isArray(actions.predicates)) {
        return ; // no valid actions
    }

    const invertedOperators = {
        Show: "Hide",
        Hide: "Show",
        Editable: "NotEditable",
        NotEditable: "Editable",
        Required: "NotRequired",
        NotRequired: "Required",
    };

    // Clone deeply so inverting Else never mutates Actions source data.
    // Some runtimes do not expose structuredClone yet.
    const oppositeActions = JSON.parse(JSON.stringify(actions));

    // Invert operator IDs
    oppositeActions.predicates.forEach((pred) => {
        if (invertedOperators[pred.operator_id]) {
            pred.operator_id = invertedOperators[pred.operator_id];
        }
    });

    // Validate targets in the inverted actions
    oppositeActions.predicates = getPredicates(oppositeActions.predicates);

    // Merge with existing Else predicates
    const combined = (elseData.value?.predicates || []).concat(getPredicates(oppositeActions.predicates));

    // Remove duplicate predicates based on argument, operator_id, and target_id.
    const uniquePredicates = combined.filter((item, pos) => {
        const found = combined.find(pred =>
            pred.argument === item.argument &&
            pred.operator_id === item.operator_id &&
            pred.target_id === item.target_id
        );
        return (combined.indexOf(found) === pos) && (found.operator_id !== "NoOp");
    });

    if (uniquePredicates.length === 0) {
        return ;
    }
    // Update elseData with the merged, deduplicated predicates
    if (!elseData.value) {
        elseData.value = { logicalType_id: "any", predicates: uniquePredicates };
    } else {
        elseData.value.predicates = uniquePredicates;
    }

    // Force the Else <ui-predicate>  to re-mount by changing its key
    elseKey.value++;
}

/**
 * validate the targets in case options have changed or fields deleted
 * @param predicates 
 */
const getPredicates = function (predicates) {
    return predicates.filter(predicate => {
        let found = actionsColumns.targets.find(target => {
            if (predicate.target_id === target.target_id) {
                return true;
            }
        });
        if (!found) {
            // try for partial matches - can happen if field options change (from or to a collection)
            found = actionsColumns.targets.find(target => {
                if (predicate.target_id.indexOf(target.target_id) > -1 || target.target_id.indexOf(predicate.target_id) > -1) {
                    return true;
                }
            });

            if (found) {
                if (predicate.target_id.indexOf("[]") > -1) {
                    predicate.target_id = predicate.target_id.replace("[]", "");
                } else {
                    predicate.target_id = predicate.target_id + "[]";
                }
            } else {
                console.error("Tracker Field Rules: field " + predicate.target_id + " not found in predicates");
            }
        }
        return found;
    });
};

onBeforeMount(() => {

    const fields = targetFields.value;
    let field = {};
    const conditionsTargets = [];
    const actionsTargets = [{
        target_id: "NoTarget",
        label: "",
        type_id: "Nothing"
    }];

    conditionsColumns.operators = definition.value.operators;
    conditionsColumns.types = definition.value.types;
    actionsColumns.operators = definition.value.actions;
    actionsColumns.types = definition.value.types;

    if (fields !== undefined) {

        fields.forEach(function (value) {

            conditionsTargets.push({
                target_id: value.ins_id,
                label: value.name,
                type_id: value.argumentType,
            });

            actionsTargets.push({
                target_id: value.ins_id,
                label: value.name,
                type_id: "Field",
            });

            if (value.fieldId === fieldId.value ||
                value.argumentType === "Collection" && value.fieldId === (fieldId.value + "[]")) {
                field = value;
            }
        });

        conditionsColumns.targets = conditionsTargets;
        actionsColumns.targets = actionsTargets;
    }

    const defaultCondition = function () {
        let operatorId = "";

        if (field.argumentType === "Text") {
            operatorId = "TextContains";
        } else if (field.argumentType === "Number") {
            operatorId = "NumberEquals";
        } else if (field.argumentType === "Boolean") {
            operatorId = "BooleanTrueFalse";
        } else if (field.argumentType === "DateTime") {
            operatorId = "DateTimeOn";
        } else if (field.argumentType === "Collection") {
            operatorId = "CollectionContains";
        }

        return {
            logicalType_id: "any",
            predicates: [{
                target_id: "ins_" + field.fieldId + (field.argumentType === "Collection" ? "[]" : ""),
                operator_id: operatorId,
                argument: "",
            }]
        };
    };

    // set conditions field to this one if nothing else set
    if (!rules.value.conditions) {
        conditionsData.value = defaultCondition();
    } else {
        conditionsData.value = rules.value.conditions;
        conditionsData.value.predicates = getPredicates(rules.value.conditions.predicates);

        if (conditionsData.value.predicates.length === 0) {
            // we need at least one condition
            conditionsData.value = defaultCondition();
        }
    }

    if (rules.value.actions) {
        actionsData.value = rules.value.actions;
        actionsData.value.predicates = getPredicates(rules.value.actions.predicates);
    }
    if (rules.value.else) {
        elseData.value = rules.value.else;
        elseData.value.predicates = getPredicates(rules.value.else.predicates);
    }
});

</script>
<style lang="scss" scoped>
.tracker-rules-container :deep(.ui-predicate__row) {
    display: flex;
    flex-direction: row;
    margin-bottom: 0.4rem;
    gap: 0.2rem;

    .ui-predicate__options {
        gap: 0.1rem;
    }

    .btn,
    .form-control,
    .form-select {
        padding: 0.17rem 0.75rem;
    }

    .form-control {
        width: 135px;
    }

    .form-select {
        padding: .17rem .5rem;
        background-position: right 0.5rem center;

        &.ui-predicate__logic {
            min-width: 70px;
        }

        &.ui-predicate__targets {
            min-width: 85px;
        }

        &.ui-predicate__operators {
            min-width: 33px;
        }
    }

    button:disabled {
        cursor: not-allowed;
        pointer-events: auto;
        opacity: .5;
    }
}
</style>