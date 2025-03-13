export default function (tool) {
    return (context) => {
        const button = $.summernote.ui.button({
            contents: decodeHtmlEntities(tool.icon),
            tooltip: tool.label,
            click: function () {
                if (tool.callback) {
                    if (typeof tool.callback === "string") {
                        window[`${tool.callback}`].call(this);
                    } else {
                        tool.callback.call(this, context);
                    }
                }
            },
        });

        return button.render();
    };
}

function decodeHtmlEntities(str) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(str, "text/html");
    return doc.documentElement.textContent;
}
