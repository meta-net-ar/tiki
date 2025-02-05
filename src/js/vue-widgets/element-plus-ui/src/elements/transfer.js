import { defineCustomElement, h, watch, reactive } from "vue";
import Transfer from "../components/Transfer/Transfer.vue";
import styles from "../components/Transfer/transfer.scss?inline";

customElements.define(
    "el-transfer",
    defineCustomElement(
        (props, ctx) => {
            const internalState = reactive({ ...props });

            const emitValueChange = (detail) => {
                ctx.emit("transfer-change", detail);
            };

            const emitCustomEvent = (eventName, detail) => {
                ctx.emit(eventName, detail);
            };

            // Allow to update the comoponent state by changing HTML element attributes
            watch(
                () => props,
                (newProps) => {
                    Object.keys(newProps).forEach((key) => {
                        internalState[key] = newProps[key];
                    });
                },
                { immediate: true, deep: true }
            );
            return () => h(Transfer, { ...internalState, emitValueChange, emitCustomEvent }, ctx.slots);
        },
        {
            styles: [styles],
        }
    )
);

/*
    Sync the Transfer hidden select with the form
*/
new MutationObserver((mutations) => {
    if (mutations.some((m) => m.target.querySelector("el-transfer"))) {
        const elements = document.querySelectorAll("el-transfer");
        elements.forEach((el) => {
            if (el.getAttribute("id")) {
                return;
            }

            const elementUniqueId = Math.random().toString(36).substring(7);
            el.setAttribute("id", elementUniqueId);

            const fieldName = el.getAttribute("field-name");
            let select = el.closest("form").querySelector(`select[name="${fieldName}"]`);
            if (!select) {
                select = document.createElement("select");
                select.name = fieldName;
                select.multiple = true;
                select.setAttribute("aria-hidden", "true");

                select.setAttribute("element-plus-ref", elementUniqueId);

                const defaultValues = el.getAttribute("default-value");
                if (defaultValues) {
                    const values = JSON.parse(defaultValues);
                    values.forEach((v) => {
                        const option = document.createElement("option");
                        option.value = v;
                        option.selected = true;
                        select.appendChild(option);
                    });
                }
                // Enclose it in a hidden div so its related error messages are hidden too
                const div = document.createElement("div");
                div.style.display = "none";
                div.appendChild(select);
                el.parentNode.insertAdjacentElement("afterend", div);
            }
            el.addEventListener("transfer-change", function (event) {
                const value = event.detail[0].value;
                select.innerHTML = "";
                value.forEach((v) => {
                    const option = document.createElement("option");
                    option.value = v;
                    option.selected = true;
                    select.appendChild(option);
                });
                $(select).trigger("change");
            });
        });
    }
}).observe(document.body, { childList: true, subtree: true });
