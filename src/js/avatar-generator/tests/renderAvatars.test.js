import { afterEach, describe, expect, test } from "vitest";
import renderAvatars from "../renderAvatars";
import stylesConstant from "../styles.constant";
import $ from "jquery";
import { createAvatar } from "@dicebear/core";
import * as collections from "@dicebear/collection";
import { AVATAR_RADIUS, AVATAR_SIZE } from "../avatarOptions.constant";

vi.mock("@dicebear/core", () => ({
    createAvatar: vi.fn(() => ({
        toDataUri: () => "data:avatar/uri",
    })),
}));

describe("renderAvatars", () => {
    beforeAll(() => {
        window.$ = $;
    });

    afterEach(() => {
        vi.clearAllMocks();
        $("body").empty();
    });

    test("should replace all .dicebear-avatar elements with corresponding dicebear avatars", () => {
        $("body").append(`
            <div class="avatar-container">
                <div class="dicebear-avatar" data-style="${stylesConstant[0].name}" data-seed="seed1"></div>
                <div class="dicebear-avatar" data-style="${stylesConstant[1].name}" data-seed="seed2" data-size="large"></div>
            </div>
        `);

        renderAvatars();

        expect($(".avatar-container").find(".dicebear-avatar").length).toBe(0);
        const images = $(".avatar-container").find("img");
        expect(images.length).toBe(2);
        images.each((index, img) => {
            const style = stylesConstant[index].name;
            const seed = `seed${index + 1}`;
            expect($(img).attr("src")).toBe("data:avatar/uri");

            expect(createAvatar).toHaveBeenCalledWith(collections[style], {
                seed: seed,
                size: index === 0 ? AVATAR_SIZE.small : AVATAR_SIZE.large,
                radius: AVATAR_RADIUS,
            });
        });
    });

    test("should not replace .dicebear-avatar elements with corresponding dicebear avatars, when the style is invalid", () => {
        $("body").append(`
            <div class="avatar-container">
                <div class="dicebear-avatar" data-style="invalid-style" data-seed="seed1"></div>
            </div>
        `);

        renderAvatars();

        expect($(".avatar-container").find(".dicebear-avatar").length).toBe(1);
        expect($(".avatar-container").find("img").length).toBe(0);
        expect(createAvatar).not.toHaveBeenCalled();
    });
});
