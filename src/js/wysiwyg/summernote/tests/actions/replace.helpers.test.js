import { beforeAll, describe, test } from "vitest";
import { setMarkers } from "../../actions/replace.helpers";
import $ from "jquery";

describe("setMarkers", () => {
    beforeAll(() => {
        window.$ = $;
    });

    test("should not set markers if no value is provided", () => {
        const givenContext = {
            layoutInfo: {
                editable: $("<div>foo</div>"),
            },
        };

        setMarkers(givenContext, "");

        expect(givenContext.layoutInfo.editable.html()).toBe("foo");
    });

    test.each([
        [false, "foo", "<p>foo</p> <p>Foo</p>", "<p><mark>foo</mark></p> <p><mark>Foo</mark></p>"],
        [true, "foo", "<p>foo</p> <p>Foo</p>", "<p><mark>foo</mark></p> <p>Foo</p>"],
        [false, "foo", "<p><span>foo</span></p> <p>Foo</p>", "<p><span><mark>foo</mark></span></p> <p><mark>Foo</mark></p>"],
    ])("correctly set markers when caseSensitive is %s", (caseSensitive, value, editableHtml, expectHtml) => {
        const givenContext = {
            layoutInfo: {
                editable: $(`<div>${editableHtml}</div>`),
            },
        };

        $("body").append($("<input id='caseSensitive' type='checkbox'>"));

        $("#caseSensitive").prop("checked", caseSensitive);

        setMarkers(givenContext, value);

        expect(givenContext.layoutInfo.editable.html()).toBe(expectHtml);
    });
});
