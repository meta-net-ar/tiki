import { defineCustomElement, h } from "vue";
import Message from "../components/Message/Message.vue";
import styles from "../components/Message/message.scss?inline";

customElements.define(
    "el-message",
    defineCustomElement(
        (props, ctx) => {
            return () => h(Message, { ...props, _emit: ctx.emit }, ctx.slots);
        },
        { styles: [styles] }
    )
);

export { default as showMessage } from "../utils/showMessage";
