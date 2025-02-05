import { defineCustomElement, h, watch, reactive } from "vue";
import FileGalUploader from "../components/FileGalUploader/FileGalUploader.vue";
import styles from "../components/FileGalUploader/fileGalUploader.scss?inline";

customElements.define(
    "tiki-el-file-gal-uploader",
    defineCustomElement(
        (props, ctx) => {
            const internalState = reactive({ ...props });

            watch(
                () => props,
                (newProps) => {
                    Object.keys(newProps).forEach((key) => {
                        internalState[key] = newProps[key];
                    });
                },
                { immediate: true, deep: true }
            );
            return () => h(FileGalUploader, internalState, ctx.slots);
        },
        {
            styles: [styles],
        }
    )
);
