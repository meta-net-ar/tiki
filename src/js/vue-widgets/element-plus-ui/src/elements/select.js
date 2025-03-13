import { defineCustomElement, h, watch, reactive } from "vue";
import Select from "../components/Select/Select.vue";
import styles from "../components/Select/select.scss?inline";

customElements.define(
    "el-select",
    defineCustomElement(
        (props, ctx) => {
            const internalState = reactive({ ...props });

            const emitValueChange = (detail) => {
                ctx.emit("select-change", detail);
            };

            // Allow to update the comoponent state by changing HTML element attributes
            watch(
                () => props,
                (newProps) => {
                    Object.keys(newProps).forEach((key) => {
                        internalState[key] = newProps[key];
                    });
                },
                { immediate: true, deep: true }
            );
            return () => h(Select, { ...internalState, emitValueChange }, ctx.slots);
        },
        {
            styles: [styles],
        }
    )
);

export { default as applySelect } from "../utils/applySelect";
