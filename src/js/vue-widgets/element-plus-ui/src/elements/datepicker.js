import { defineCustomElement, h, reactive, watch } from "vue";
import DatePicker from "../components/DatePicker/DatePicker.vue";
import styles from "../components/DatePicker/datePicker.scss?inline";

customElements.define(
    "el-date-picker",
    defineCustomElement(
        (props, ctx) => {
            const internalState = reactive({ ...props });

            watch(
                () => props,
                (newProps) => {
                    Object.keys(newProps).forEach((key) => {
                        internalState[key] = newProps[key];
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
