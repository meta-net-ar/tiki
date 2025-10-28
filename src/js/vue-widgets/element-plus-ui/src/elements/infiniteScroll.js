import { defineCustomElement, h } from "vue";
import InfiniteScroll from "../components/InfiniteScroll/InfiniteScroll.vue";
import styles from "../components/InfiniteScroll/infiniteScroll.scss?inline";

export default function registerInfiniteScroll(_cssFiles) {
    customElements.define(
        "el-infinite-scroll",
        defineCustomElement(
            (props, ctx) => {
                return () => h(InfiniteScroll, { ...props, _cssFiles }, ctx.slots);
            },
            {
                styles: [styles],
            }
        )
    );
}

export { default as applyInput } from "../utils/applyInput";
