import { defineCustomElement } from "vue";
import EnterKeyModal from "../components/EnterKeyModal/EnterKeyModal.vue";

customElements.define("tiki-enter-key-modal", defineCustomElement(EnterKeyModal, { shadowRoot: false }));
