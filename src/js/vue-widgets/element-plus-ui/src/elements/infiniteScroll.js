import { defineCustomElement, h } from "vue";
import InfiniteScroll from "../components/InfiniteScroll/InfiniteScroll.vue";
import styles from "../components/InfiniteScroll/infiniteScroll.scss?inline";

customElements.define(
    "el-infinite-scroll",
    defineCustomElement(
        (props) => {
            return () => h(InfiniteScroll, props);
        },
        {
            styles: [styles],
        }
    )
);

export { default as applyInput } from "../utils/applyInput";
