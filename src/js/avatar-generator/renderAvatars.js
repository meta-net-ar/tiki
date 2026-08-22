import { createAvatar } from "@dicebear/core";
import { AVATAR_RADIUS, AVATAR_SIZE } from "./avatarOptions.constant";

export default function renderAvatars() {
    const avatarElements = $(".dicebear-avatar").toArray();

    return Promise.all(
        avatarElements.map(async (element) => {
            const avatarElement = $(element);
            const style = avatarElement.data("style");
            let collection;

            try {
                collection = await import(`@dicebear/${style}`);
            } catch {
                return;
            }

            const size = avatarElement.data("size") || "small";

            if (!collection) {
                return;
            }

            const avatar = createAvatar(collection, { seed: avatarElement.data("seed"), size: AVATAR_SIZE[size], radius: AVATAR_RADIUS });
            const image = $("<img>").attr("src", avatar.toDataUri());
            avatarElement.replaceWith(image);
        })
    );
}
