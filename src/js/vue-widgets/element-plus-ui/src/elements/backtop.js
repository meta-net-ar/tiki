import { defineCustomElement, h } from "vue";
import BackTop from "../components/BackTop/BackTop.vue";
import styles from "../components/BackTop/backTop.scss?inline";

customElements.define(
    "el-backtop",
    defineCustomElement(
        (props, ctx) => {
            return () => h(BackTop, { ...props, _emit: ctx.emit, _expose: ctx.expose });
        },
        { styles: [styles] }
    )
);
