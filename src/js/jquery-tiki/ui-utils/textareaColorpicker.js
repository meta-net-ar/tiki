export default function textareaColorpicker() {
    new MutationObserver((mutations) => {
        mutations.forEach(() => {
            $("sl-color-picker").each(function () {
                if (this.getAttribute("data-handled")) return;
                if (!this.getAttribute("areaId")) return;

                if (this.trigger) {
                    this.trigger.style.cssText = "visibility: hidden; position: absolute; margin-left: -50px; margin-top: -30px;"; // The spacings used are not totally accurate, but they are visually good enough for now.
                    this.addEventListener("sl-blur", function () {
                        if (this.getAttribute("section") === "sheet") {
                            $.sheet.instance[I].cellChangeStyle(this.getAttribute("type"), "");
                        } else {
                            const value =
                                this.getAttribute("type") === "color"
                                    ? "~~" + this.value + ":text~~"
                                    : "~~text-color-goes-here," + this.value + ":text~~";
                            insertAt(this.getAttribute("areaId"), value);
                        }
                    });
                    this.setAttribute("data-handled", "true");
                }
            });
        });
    }).observe(document.body, {
        childList: true,
        subtree: true,
    });
}
