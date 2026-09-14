import { defineCustomElement } from "vue";
import EncryptedField from "../components/EncryptedField/EncryptedField.vue";

customElements.define("tiki-encrypted-field", defineCustomElement(EncryptedField, { shadowRoot: false }));
