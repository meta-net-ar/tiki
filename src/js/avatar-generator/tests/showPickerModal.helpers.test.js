import { describe, test, expect, beforeAll } from "vitest";
import { getAvatarElement, getRandomAvatars, handleAvatarClick } from "../showPickerModal.helpers";
import { createAvatar } from "@dicebear/core";
import * as collections from "@dicebear/collection";
import stylesConstant from "../styles.constant";
import $ from "jquery";
import { AVATAR_RADIUS, AVATAR_SIZE } from "../avatarOptions.constant";

vi.mock("@dicebear/core", () => ({
    createAvatar: vi.fn(() => ({
        toString: () => "avatarString",
    })),
}));

describe("showPickerModal helper functions", () => {
    beforeAll(() => {
        window.$ = $;
    });

    test("getRandomAvatars should return an array of 24 well-formed avatars", () => {
        const activeStyle = stylesConstant[0].name;
        const avatars = getRandomAvatars(activeStyle);

        expect(avatars).toHaveLength(24);
        avatars.forEach((avatar) => {
            expect(avatar.svg).toBe("avatarString");
            expect(avatar.seed).toBeTypeOf("string");
            expect(avatar.seed).toBeTruthy();

            expect(createAvatar).toHaveBeenCalledWith(collections[activeStyle], {
                seed: avatar.seed,
                size: AVATAR_SIZE.large,
                radius: AVATAR_RADIUS,
            });
        });
    });

    test("getAvatarElement should return a correct HTML markup for displaying an avatar", () => {
        const givenAvatar = { seed: "seed1", svg: "<svg>avatar</svg>" };

        const actualElement = $(getAvatarElement(givenAvatar));

        expect(actualElement.data("seed")).toBe(givenAvatar.seed);
        expect(actualElement.html().trim()).toBe(givenAvatar.svg);
        expect(actualElement.hasClass("avatar-picker__avatars__item bg-secondary-subtle border rounded border-3 border-white")).toBe(true);
    });

    test("handleAvatarClick should listen for click events and toggle classes correctly", () => {
        const givenContainer = $(`
            <div class="avatar-picker__avatars">
                <div class="avatar-picker__avatars__item border-white" data-seed="seed1"></div>
                <div class="avatar-picker__avatars__item border-white" data-seed="seed2"></div>
            </div>`);

        handleAvatarClick(givenContainer[0]);

        const firstAvatar = givenContainer.find(".avatar-picker__avatars__item").first();
        const secondAvatar = givenContainer.find(".avatar-picker__avatars__item").last();

        firstAvatar.trigger("click");
        expect(firstAvatar.hasClass("border-success")).toBe(true);
        expect(firstAvatar.hasClass("border-white")).toBe(false);
        expect(secondAvatar.hasClass("border-success")).toBe(false);
        expect(secondAvatar.hasClass("border-white")).toBe(true);

        secondAvatar.trigger("click");
        expect(firstAvatar.hasClass("border-success")).toBe(false);
        expect(firstAvatar.hasClass("border-white")).toBe(true);
        expect(secondAvatar.hasClass("border-success")).toBe(true);
        expect(secondAvatar.hasClass("border-white")).toBe(false);
    });
});
