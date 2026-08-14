/**
 * Shows any Tiki feedback message received from the ajax response headers.
 * @param {XMLHttpRequest} xhr
 */
export default function (xhr) {
    const feedback = xhr.getResponseHeader("X-Tiki-Feedback");
    const tikiFeedbackElement = $("#tikifeedback");
    if (feedback) {
        const feedbackContent = decodeURIComponent(feedback);
        tikiFeedbackElement.fadeIn(200, function () {
            tikiFeedbackElement.html($($.parseHTML(feedbackContent)).filter("#tikifeedback").html());
            tikiFeedbackElement.find("div.alert").each(function () {
                const title = $(this).find("span.rboxtitle").text().trim();
                const content = $(this).find("div.rboxcontent").text().trim();
                $(this).find("span.rboxtitle").text(title);
                $(this).find("div.rboxcontent").text(content);
            });

            placeFeedback(tikiFeedbackElement);
        });
    }

    tikiFeedbackElement.find(".clear").on("click", function () {
        $(tikiFeedbackElement).empty();
        //move back to usual position and clear style attribute so subsequent feedback appears properly
        $("div#col1").prepend(tikiFeedbackElement);
        tikiFeedbackElement.css({ "z-index": "", position: "", top: "" });
        return true;
    });
}
