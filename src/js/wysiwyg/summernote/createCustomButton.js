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

        /*
        These wrappers are used to allow insertion of custom elements following the button.
        They are later removed when the editor is initialized and elements inserted.
        The main reason for they removal is to avoid breaking the summernote toolbar layout.
        A typical example of this is with the emoji picker tool, which uses the renderCallback execution to insert the picker element within the DOM.
        */
        const wrapper = $("<div class='custom-btn-wrapper'></div>");

        button.render(wrapper);

        if (tool.renderCallback) {
            if (typeof tool.renderCallback === "string") {
                window[`${tool.renderCallback}`].call(wrapper);
            } else {
                tool.renderCallback.call(wrapper);
            }
        }

        return wrapper;
    };
}

export function decodeHtmlEntities(str) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(str, "text/html");
    return doc.documentElement.textContent;
}
