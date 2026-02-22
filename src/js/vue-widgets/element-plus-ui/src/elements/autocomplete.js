import { defineCustomElement, h, watch, reactive } from "vue";
import Autocomplete from "../components/Autocomplete/Autocomplete.vue";
import styles from "../components/Autocomplete/autocomplete.scss?inline";

customElements.define(
    "el-autocomplete",
    defineCustomElement(
        (props, ctx) => {
            const internalState = reactive({ ...props });

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
            return () => h(Autocomplete, { ...internalState, emitCustomEvent, _expose: ctx.expose }, ctx.slots);
        },
        {
            styles: [styles],
        }
    )
);

export { default as applyAutocomplete } from "../utils/applyAutocomplete";
