export default function (textarea) {
    let savedValue = textarea.summernote("code");
    window.addEventListener("beforeunload", function (event) {
        if (!textarea.data("summernote") || textarea.data("is-submitting")) return;
        const currentValue = textarea.summernote("code");
        if (currentValue !== savedValue) {
            event.preventDefault();
        }
    });

    textarea.on("autosaveSuccess", () => {
        savedValue = textarea.summernote("code");
    });
}
