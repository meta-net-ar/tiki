import { defineCustomElement, h, reactive, watch } from "vue";
import DatePicker from "../components/DatePicker/DatePicker.vue";
import styles from "../components/DatePicker/datePicker.scss?inline";

customElements.define(
    "el-date-picker",
    defineCustomElement(
        (props, ctx) => {
            const internalState = reactive({ ...props });
            if (internalState.minutestep !== undefined) {
                internalState.minuteStep = Number(internalState.minutestep);
            }
            if (internalState.enforcestep !== undefined) {
                internalState.enforceStep = Number(internalState.enforcestep);
            }

            watch(
                () => props,
                (newProps) => {
                    Object.keys(newProps).forEach((key) => {
                        internalState[key] = newProps[key];
                        if (key === "minutestep") {
                            internalState.minuteStep = Number(newProps[key]);
                        }
                        if (key === "enforcestep") {
                            internalState.enforceStep = Number(newProps[key]);
                        }
                    });
                },
                { immediate: true, deep: true }
            );
            return () => h(DatePicker, { ...internalState, _emit: ctx.emit, _expose: ctx.expose }, ctx.slots);
        },
        {
            styles: [styles],
        }
    )
);
