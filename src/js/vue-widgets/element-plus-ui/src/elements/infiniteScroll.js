import { defineCustomElement, h } from "vue";
import InfiniteScroll from "../components/InfiniteScroll/InfiniteScroll.vue";
import styles from "../components/InfiniteScroll/infiniteScroll.scss?inline";

export default function registerInfiniteScroll(_bootstrapThemeFile, _height) {
    customElements.define(
        "el-infinite-scroll",
        defineCustomElement(
            (props, ctx) => {
                return () => h(InfiniteScroll, { ...props, _bootstrapThemeFile, _height }, ctx.slots);
            },
            {
                styles: [styles],
            }
        )
    );
}

export { default as applyInput } from "../utils/applyInput";
