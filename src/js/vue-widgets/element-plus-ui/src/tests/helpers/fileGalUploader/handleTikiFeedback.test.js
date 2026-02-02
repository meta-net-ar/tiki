import { beforeAll, beforeEach, describe, expect, test, vi } from "vitest";
import $ from "jquery";
import handleTikiFeedback from "../../../helpers/fileGalUploader/handleTikiFeedback";

describe("fileGalUploader handleTikiFeedback helper", () => {
    beforeAll(() => {
        window.$ = $;
        window.placeFeedback = vi.fn();
        $.fn.fadeIn = vi.fn(function (duration, callback) {
            $(this).data("mocked-fadein", duration);
            callback.call(this);
        });
    });

    beforeEach(() => {
        $("body").empty();
        const feedbackContainer = $(
            "<div id='tikifeedback'><div class='alert'><span class='rboxtitle'> Title </span><div class='rboxcontent'> Content </div></div><button class='clear'>Clear</button></div>"
        );
        $("body").append(feedbackContainer);
    });

    test("places the given feedback on the page", () => {
        const givenFeedbackHeader = encodeURIComponent(
            "<div id='tikifeedback'><div class='alert'><span class='rboxtitle'> New Title </span><div class='rboxcontent'> New Content </div></div></div>"
        );
        const mockXhr = {
            getResponseHeader: (headerName) => {
                if (headerName === "X-Tiki-Feedback") {
                    return givenFeedbackHeader;
                }
            },
        };

        handleTikiFeedback(mockXhr);

        const tikiFeedbackElement = $("#tikifeedback");
        expect(tikiFeedbackElement.data("mocked-fadein")).toBe(200);
        expect(tikiFeedbackElement.find("span.rboxtitle").text().trim()).toBe("New Title");
        expect(tikiFeedbackElement.find("div.rboxcontent").text().trim()).toBe("New Content");
        expect(window.placeFeedback).toHaveBeenCalledWith(tikiFeedbackElement);
    });

    test("clears the feedback when the clear button is clicked", () => {
        handleTikiFeedback({
            getResponseHeader: (headerName) => null,
        });

        const tikiFeedbackElement = $("#tikifeedback");

        const clearButton = tikiFeedbackElement.find("button.clear");
        clearButton.trigger("click");

        expect(tikiFeedbackElement.html()).toBe("");
        expect(tikiFeedbackElement.css("z-index")).toBe("");
        expect(tikiFeedbackElement.css("position")).toBe("");
        expect(tikiFeedbackElement.css("top")).toBe("");
    });
});
