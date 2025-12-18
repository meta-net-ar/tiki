let isSubmitting = false;

$(".item-submit-btn").on("click", () => {
    isSubmitting = true;
});

const registeredListeners = [];

$("input, select, textarea").each((_, element) => {
    if (element.getAttribute("name")?.match(/^ins_(\d+)(\[\])?$/)) {
        const initialValue = $(element).val();

        const listener = (event) => {
            const currentValue = $(element).val();
            if (JSON.stringify(currentValue) !== JSON.stringify(initialValue) && !isSubmitting) {
                event.preventDefault();
            }
        };

        window.addEventListener("beforeunload", listener);

        registeredListeners.push(listener);
    }
});

$(".modal").on("hidden.bs.modal", function () {
    cleanUpDirtyCheck();
});

function cleanUpDirtyCheck() {
    registeredListeners.forEach((listener) => {
        window.removeEventListener("beforeunload", listener);
    });
}
