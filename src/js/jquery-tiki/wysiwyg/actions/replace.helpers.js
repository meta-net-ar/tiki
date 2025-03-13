export function resetMarkers(context) {
    const editable = context.layoutInfo.editable;
    editable.html(
        editable
            .html()
            .replace(/<mark>/g, "")
            .replace(/<\/mark>/g, "")
    );
}

export function setMarkers(context, value) {
    resetMarkers(context);

    if (!value) return;

    const caseSensitive = $("#caseSensitive").is(":checked");

    const pattern = new RegExp(value, caseSensitive ? "g" : "gi");

    const match = context.layoutInfo.editable.find("*").filter(function () {
        const textNodes = $(this)
            .contents()
            .filter(function () {
                return this.nodeType === Node.TEXT_NODE;
            });

        let isMatch = false;
        textNodes.each(function () {
            if (pattern.test(this.nodeValue)) {
                isMatch = true;
                pattern.lastIndex = 0; // Reset lastIndex after each test
            }
        });

        return isMatch;
    });

    match.each(function () {
        const highlighted = this.textContent.replace(pattern, "<mark>$&</mark>");
        $(this).html(highlighted);
    });
}
