import { defineCustomElement, h } from "vue";
import FileInput from "../components/FileInput/FileInput.vue";
import styles from "../components/FileInput/fileInput.scss?inline";

customElements.define(
    "el-file-input",
    defineCustomElement(
        (props, ctx) => {
            return () => h(FileInput, { ...props, _emit: ctx.emit });
        },
        { styles: [styles] }
    )
);
