import { defineCustomElement, h, watch, reactive } from "vue";
import Transfer from "../components/Transfer/Transfer.vue";
import styles from "../components/Transfer/transfer.scss?inline";

customElements.define(
    "el-transfer",
    defineCustomElement(
        (props, ctx) => {
            const internalState = reactive({ ...props });

            const emitValueChange = (detail) => {
                ctx.emit("transfer-change", detail);
            };

            const emitCustomEvent = (eventName, detail) => {
                ctx.emit(eventName, detail);
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
            return () => h(Transfer, { ...internalState, emitValueChange, emitCustomEvent }, ctx.slots);
        },
        {
            styles: [styles],
        }
    )
);
