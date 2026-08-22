import { afterEach, beforeAll, describe, expect, test, vi } from "vitest";
import $ from "jquery";
import showPickerModal from "../showPickerModal";
import { getAvatarElement, getRandomAvatars, handleAvatarClick } from "../showPickerModal.helpers";
import stylesConstant from "../styles.constant";
import { createAvatar } from "@dicebear/core";
import * as collections from "@dicebear/collection";
import { AVATAR_RADIUS, AVATAR_SIZE } from "../avatarOptions.constant";

vi.mock("@dicebear/core", () => ({
    createAvatar: vi.fn(() => ({
        toString: () => "avatar",
        toDataUri: () => "data:avatar/uri",
    })),
}));

vi.mock("../showPickerModal.helpers", () => ({
    getAvatarElement: vi.fn(() => "avatar"),
    getRandomAvatars: vi.fn(() => []),
    handleAvatarClick: vi.fn(),
}));

describe("showPickerModal", () => {
    beforeAll(() => {
        window.$ = $;
        $.openModal = vi.fn();
        $.closeModal = vi.fn();
        $.fn.getIcon = vi.fn((iconName) => $(`<div>${iconName}</div>`));
        $.service = vi.fn((controller, action) => `${controller}/${action}`);
        window.tr = vi.fn((text) => text + " translated");
        window.showMessage = vi.fn();
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    test("renders correctly the avatar picker modal", async () => {
        getRandomAvatars.mockReturnValue([
            { seed: "seed1", svg: "avatar1" },
            { seed: "seed2", svg: "avatar2" },
        ]);

        await showPickerModal();

        expect($.openModal).toHaveBeenCalledWith({
            title: "Choose an avatar translated",
            size: "modal-xl",
            content: expect.any(String),
            buttons: [
                {
                    text: "Select translated <div>arrow-right</div>",
                    onClick: expect.any(Function),
                },
            ],
            open: expect.any(Function),
        });

        expect(getRandomAvatars).toHaveBeenCalledWith(stylesConstant[0].name);

        const content = $.openModal.mock.calls[0][0].content;
        expect(content).toMatchSnapshot();

        $.openModal.mock.calls[0][0].open.call(content[0]);

        expect(handleAvatarClick).toHaveBeenCalledWith(content[0]);
    });

    test("correctly handles the randomize button click", async () => {
        await showPickerModal();

        const openModalCall = $.openModal.mock.calls[0][0];
        const content = $(openModalCall.content);
        openModalCall.open.call(content[0]);

        const expectedRandomAvatars = [
            { seed: "seed3", svg: "avatar3" },
            { seed: "seed4", svg: "avatar4" },
        ];
        getRandomAvatars.mockReturnValue(expectedRandomAvatars);
        getAvatarElement.mockImplementation((avatar) => `<div>${avatar.seed}</div>`);

        const randomizeButton = content.find("#randomize-avatars");
        await randomizeButton.trigger("click");

        expect(handleAvatarClick).toHaveBeenCalledTimes(2);
        expect(handleAvatarClick).toHaveBeenCalledWith(content[0]);

        const avatarPickerAvatars = content.find(".avatar-picker__avatars");
        expect(avatarPickerAvatars.html()).toBe(`<div>${expectedRandomAvatars[0].seed}</div><div>${expectedRandomAvatars[1].seed}</div>`);
    });

    test("correctly handles the style change event", async () => {
        await showPickerModal();

        const openModalCall = $.openModal.mock.calls[0][0];
        const content = $(openModalCall.content);
        openModalCall.open.call(content[0]);

        const expectedRandomAvatars = [
            { seed: "seed5", svg: "avatar5" },
            { seed: "seed6", svg: "avatar6" },
        ];
        getRandomAvatars.mockReturnValue(expectedRandomAvatars);

        const styleSelect = content.find("#avatar-style");
        const newStyle = stylesConstant.find((style) => style.name === "funEmoji");
        styleSelect.val(newStyle.name);
        await styleSelect.trigger("change");

        const activeStyleSource = content.find("#active-style-source");
        expect(activeStyleSource.attr("href")).toBe(newStyle.source);
        expect(activeStyleSource.text()).toBe(newStyle.label);

        const activeStyleAuthor = content.find("#active-style-author");
        expect(activeStyleAuthor.attr("href")).toBe(newStyle.authorUrl);
        expect(activeStyleAuthor.text()).toBe(newStyle.author);

        const activeStyleLicense = content.find("#active-style-license");
        expect(activeStyleLicense.attr("href")).toBe(newStyle.licenseUrl);
        expect(activeStyleLicense.text()).toBe(newStyle.license);

        expect(getRandomAvatars).toHaveBeenCalledWith(newStyle.name);
        expect(content.find(".avatar-picker__avatars").html()).toBe(
            `<div>${expectedRandomAvatars[0].seed}</div><div>${expectedRandomAvatars[1].seed}</div>`
        );
    });

    test("correctly submits the selected avatar", async () => {
        // Mock
        $.BUTTON_LOADER_MARKUP = "loading...";
        window.jqueryTiki = { username: "testUser" };

        $("body")
            .append('<input type="hidden" name="ticket" value="testTicket">')
            .append('<div id="user-picture"></div>')
            .append('<div class="mod-login-avatar"><img src="" alt="Avatar"></div>');

        await showPickerModal();

        const openModalCall = $.openModal.mock.calls[0][0];
        const content = $(openModalCall.content);

        const avatarItem = content.find(".avatar-picker__avatars").children().first();
        avatarItem.data("seed", "selectedSeed");
        avatarItem.addClass("avatar-picker__avatars__item border-success");

        const ajaxPostSpy = vi.spyOn($, "post").mockImplementation(() => ({
            fail: vi.fn((cb) => cb),
        }));

        const submitButtonMock = $('<button type="button" class="btn btn-primary">Select</button>');

        openModalCall.buttons[0].onClick.call(content[0], { target: submitButtonMock[0] });
        expect(submitButtonMock.html()).toBe("loading...");
        expect(submitButtonMock.attr("disabled")).toBe("disabled");

        expect(ajaxPostSpy).toHaveBeenCalledWith(
            "user/upload_avatar",
            {
                isAvatar: true,
                avatarLibName: "dicebear/" + content.find("#avatar-style").val(),
                avatarType: "l",
                avatarName: "selectedSeed",
                user: "testUser",
                confirmForm: "y",
                ticket: "testTicket",
            },
            expect.any(Function)
        );

        // on success
        await ajaxPostSpy.mock.calls[0][2]();

        expect(showMessage).toHaveBeenCalledWith("Avatar updated translated", "success");
        expect($("#user-picture").html()).toBe(avatarItem.html());
        expect($(".mod-login-avatar").find("img").attr("src")).toBe("data:avatar/uri");
        expect(createAvatar).toHaveBeenCalledWith(collections[content.find("#avatar-style").val()], {
            seed: "selectedSeed",
            size: AVATAR_SIZE.small,
            radius: AVATAR_RADIUS,
        });

        expect($.closeModal).toHaveBeenCalled();

        // on error
        ajaxPostSpy.mock.results[0].value.fail.mock.results[0].value();
        expect(showMessage).toHaveBeenCalledWith("Error updating avatar translated", "error");
        expect(submitButtonMock.html()).toBe("Select translated <div>arrow-right</div>");
        expect(submitButtonMock.attr("disabled")).toBeUndefined();
    });

    test("should not submit if no avatar is selected", async () => {
        await showPickerModal();

        const openModalCall = $.openModal.mock.calls[0][0];
        const content = $(openModalCall.content);

        const ajaxPostSpy = vi.spyOn($, "post");

        const submitButtonMock = $('<button type="button" class="btn btn-primary">Select</button>');
        openModalCall.buttons[0].onClick.call(content[0], { target: submitButtonMock[0] });

        expect(submitButtonMock.html()).toBe("Select");
        expect(submitButtonMock.attr("disabled")).toBeUndefined();

        expect(ajaxPostSpy).not.toHaveBeenCalled();
        expect(showMessage).not.toHaveBeenCalled();
    });
});
