import { createAvatar } from "@dicebear/core";
import { AVATAR_RADIUS, AVATAR_SIZE } from "./avatarOptions.constant";

export default function renderAvatars() {
    $(".dicebear-avatar").each(async function () {
        const style = $(this).data("style");
        const collection = await import(`@dicebear/${style}`);
        const size = $(this).data("size") || "small";

        if (!collection) {
            return;
        }

        const avatar = createAvatar(collection, { seed: $(this).data("seed"), size: AVATAR_SIZE[size], radius: AVATAR_RADIUS });
        const image = $("<img>").attr("src", avatar.toDataUri());
        $(this).replaceWith(image);
    });
}
