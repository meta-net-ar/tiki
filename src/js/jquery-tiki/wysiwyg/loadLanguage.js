export default function (path, callback) {
    const script = document.createElement("script");
    script.type = "text/javascript";
    script.src = path;
    script.onload = callback;
    script.onerror = function () {
        callback();
        $.notify("Failed to load the language file");
    };
    document.head.appendChild(script);
}
