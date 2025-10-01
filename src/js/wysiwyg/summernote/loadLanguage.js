import showMessage from "../../vue-widgets/element-plus-ui/src/utils/showMessage";

export default function (path, callback) {
    const script = document.createElement("script");
    script.type = "text/javascript";
    script.src = path;
    script.onload = callback;
    script.onerror = function () {
        callback();
        showMessage("Failed to load the language file", "error");
    };
    document.head.appendChild(script);
}
