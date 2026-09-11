import { createAvatar } from "@dicebear/core";
import { AVATAR_RADIUS, AVATAR_SIZE } from "./avatarOptions.constant";

export async function getRandomAvatars(activeStyle) {
    const avatars = [];
    for (let i = 0; i < 24; i++) {
        const seed = Math.random().toString(36).substring(2, 15);

        const collection = await import(`@dicebear/${activeStyle}`);

        const avatar = createAvatar(collection, { seed, size: AVATAR_SIZE.large, radius: AVATAR_RADIUS });
        avatars.push({ seed, svg: avatar.toString(), dataUri: avatar.toDataUri() });
    }
    return avatars;
}

export function getAvatarElement(avatar) {
    return `
        <div class="avatar-picker__avatars__item bg-secondary-subtle border rounded border-3 border-white" data-seed="${avatar.seed}" role="button">
            <img src="${avatar.dataUri}" alt="Avatar ${avatar.seed}" class="avatar-picker__avatars__item__image" />
        </div>
    `;
}

export function handleAvatarClick(container) {
    const avatarItems = $(container).find(".avatar-picker__avatars__item");
    avatarItems.on("click", function () {
        avatarItems.removeClass("border-success");
        avatarItems.addClass("border-white");

        $(this).removeClass("border-white");
        $(this).addClass("border-success");
    });
}
