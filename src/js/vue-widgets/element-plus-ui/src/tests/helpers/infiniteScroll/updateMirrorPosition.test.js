import { beforeAll, describe, expect, test } from "vitest";
import $ from "jquery";
import updateMirrorPosition from "../../../helpers/infiniteScroll/updateMirrorPosition";

describe("updateMirrorPosition", () => {
    beforeAll(() => {
        window.$ = $;
    });

    test("correctly updates the position of the mirror element to match the source element", () => {
        const sourceEl = $("<div></div>");
        const mirrorEl = $("<div></div>");

        sourceEl.css({
            position: "absolute",
            top: "100px",
            left: "200px",
            width: "300px",
            height: "400px",
        });

        updateMirrorPosition(mirrorEl[0], sourceEl[0]);

        expect(mirrorEl.css("top")).toBe(sourceEl.offset().top + sourceEl.outerHeight() / 4 - 5 + "px");
        expect(mirrorEl.css("left")).toBe(sourceEl.offset().left + sourceEl.outerWidth() / 2 + "px");
        expect(parseInt(mirrorEl.css("height"))).toBe(sourceEl.outerHeight());
    });
});
