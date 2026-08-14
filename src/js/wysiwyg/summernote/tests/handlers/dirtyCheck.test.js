import { afterEach, beforeAll, beforeEach, describe, expect, test, vi } from "vitest";
import $ from "jquery";
import { dirtyCheck } from "../../handlers";

describe("dirtyCheck handler", () => {
    beforeAll(() => {
        window.$ = $;
        $.fn.summernote = vi.fn(function (action) {
            if (action === "code") {
                return this.val();
            }
            return this;
        });
    });

    test("should not prevent the window from reloading when the changes have been saved", () => {
        const textarea = $("<textarea></textarea>");
        textarea.data("summernote", {});
        const event = new Event("beforeunload");
        const preventDefaultSpy = vi.spyOn(event, "preventDefault");

        dirtyCheck(textarea);

        textarea.val("new value");
        textarea.trigger("autosaveSuccess");

        window.dispatchEvent(event);

        expect(preventDefaultSpy).not.toHaveBeenCalled();
    });

    test("should not prevent the window from reloading when the editor has not been initialized", () => {
        const textarea = $("<textarea></textarea>");
        const event = new Event("beforeunload");
        const preventDefaultSpy = vi.spyOn(event, "preventDefault");

        dirtyCheck(textarea);

        textarea.val("new value");

        window.dispatchEvent(event);

        expect(preventDefaultSpy).not.toHaveBeenCalled();
    });

    test("should not prevent the window from reloading when the textarea is being submitted", () => {
        const textarea = $("<textarea></textarea>");
        textarea.data("summernote", {});
        const event = new Event("beforeunload");
        const preventDefaultSpy = vi.spyOn(event, "preventDefault");

        dirtyCheck(textarea);

        textarea.val("new value");
        textarea.data("is-submitting", true);

        window.dispatchEvent(event);

        expect(preventDefaultSpy).not.toHaveBeenCalled();
    });

    test("shoudld prevent the window from reloading when the textarea has unsaved changes", () => {
        const textarea = $("<textarea></textarea>");
        textarea.data("summernote", {});
        const event = new Event("beforeunload");
        const preventDefaultSpy = vi.spyOn(event, "preventDefault");

        dirtyCheck(textarea);

        textarea.val("new value");

        window.dispatchEvent(event);

        expect(preventDefaultSpy).toHaveBeenCalled();
    });
});
