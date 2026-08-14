import { createAvatar } from "@dicebear/core";
import * as collections from "@dicebear/collection";
import { AVATAR_RADIUS, AVATAR_SIZE } from "./avatarOptions.constant";

export default function renderAvatars() {
    $(".dicebear-avatar").each(function () {
        const style = collections[$(this).data("style")];
        const size = $(this).data("size") || "small";
        if (!style) {
            return;
        }
        const avatar = createAvatar(style, { seed: $(this).data("seed"), size: AVATAR_SIZE[size], radius: AVATAR_RADIUS });
        const image = $("<img>").attr("src", avatar.toDataUri());
        $(this).replaceWith(image);
    });
}
