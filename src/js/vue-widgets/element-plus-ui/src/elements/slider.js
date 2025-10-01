import { defineCustomElement, h } from "vue";
import styles from "../components/Slider/slider.scss?inline";
import Slider from "../components/Slider/Slider.vue";

customElements.define(
    "el-slider",
    defineCustomElement(
        (props, ctx) => {
            return () => h(Slider, { ...props, _emit: ctx.emit, _expose: ctx.expose });
        },
        { styles: [styles] }
    )
);
