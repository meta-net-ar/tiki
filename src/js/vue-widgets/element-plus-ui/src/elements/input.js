import { defineCustomElement, h } from "vue";
import Input from "../components/Input/Input.vue";
import styles from "../components/Input/input.scss?inline";

customElements.define(
    "el-input",
    defineCustomElement(
        (props, ctx) => {
            return () => h(Input, { ...props, _emit: ctx.emit, _expose: ctx.expose });
        },
        { styles: [styles] }
    )
);

export { default as applyInput } from "../utils/applyInput";
