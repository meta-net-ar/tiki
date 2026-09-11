import { createAvatar } from "@dicebear/core";
import stylesConstant from "./styles.constant";
import { getAvatarElement, getRandomAvatars, handleAvatarClick } from "./showPickerModal.helpers";
import { AVATAR_RADIUS, AVATAR_SIZE } from "./avatarOptions.constant";

export default async function showPickerModal() {
    const activeStyle = stylesConstant[0];
    const avatars = await getRandomAvatars(activeStyle.name);

    $.openModal({
        title: tr("Choose an avatar"),
        size: "modal-xl",
        content: (() => {
            return `
                    <div class="avatar-picker">
                        <div class="avatar-picker__header d-flex gap-3">
                            <div>
                                <label for="avatar-style" class="form-label">${tr(`Change style`)}</label>
                                <select id="avatar-style">
                                    ${stylesConstant.map((style) => `<option value="${style.name}">${style.label}</option>`).join("\n")}
                                </select>
                            </div>
                            <div class="d-flex align-items-end">
                                <button class="btn btn-secondary btn-sm" id="randomize-avatars" data-bs-toggle="tooltip" title="Randomize avatars">${$.fn.getIcon("refresh").prop("outerHTML")}</button>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="${activeStyle.source}" target="_blank" class="fw-bold" id="active-style-source">${activeStyle.label}</a> ${tr("by")} <a href="${activeStyle.authorUrl}" target="_blank" id="active-style-author">${activeStyle.author}</a>, ${tr("licensed under")} <a href="${activeStyle.licenseUrl}" target="_blank" id="active-style-license">${activeStyle.license}</a>
                        </div>
                        <div class="avatar-picker__avatars mt-4 d-flex gap-2 flex-wrap">
                            ${avatars.map((avatar) => getAvatarElement(avatar)).join("\n")}
                        </div>
                    </div>
                `;
        })(),
        buttons: [
            {
                text: tr("Select") + ` ${$.fn.getIcon("arrow-right").prop("outerHTML")}`,
                onClick: function (e) {
                    const selectedAvatar = $(this).find(".avatar-picker__avatars__item.border-success");
                    const style = $(this).find("#avatar-style").val();
                    if (!selectedAvatar.length) {
                        return;
                    }
                    $(e.target).html($.BUTTON_LOADER_MARKUP);
                    $(e.target).attr("disabled", "disabled");
                    $.post(
                        $.service("user", "upload_avatar"),
                        {
                            isAvatar: true,
                            avatarLibName: "dicebear/" + style,
                            avatarType: "l",
                            avatarName: selectedAvatar.data("seed"),
                            user: jqueryTiki.username,
                            confirmForm: "y",
                            ticket: $("input[name=ticket]").val(),
                        },
                        async () => {
                            showMessage(tr("Avatar updated"), "success");

                            $("#user-picture").empty().append(selectedAvatar.html());
                            $("#full-size-picture").remove();

                            const collection = await import(`@dicebear/${style}`);

                            $(".mod-login-avatar")
                                .find("img")
                                .attr(
                                    "src",
                                    createAvatar(collection, {
                                        seed: selectedAvatar.data("seed"),
                                        size: AVATAR_SIZE.small,
                                        radius: AVATAR_RADIUS,
                                    }).toDataUri()
                                );

                            $.closeModal();
                        }
                    ).fail(() => {
                        showMessage(tr("Error updating avatar"), "error");
                        $(e.target).html(tr("Select") + ` ${$.fn.getIcon("arrow-right").prop("outerHTML")}`);
                        $(e.target).removeAttr("disabled");
                    });
                },
            },
        ],
        open: function () {
            handleAvatarClick(this);

            $(this)
                .find("#randomize-avatars")
                .on("click", async () => {
                    const avatars = await getRandomAvatars($(this).find("#avatar-style").val());
                    $(this).find(".avatar-picker__avatars").empty();
                    avatars.forEach((avatar) => {
                        $(this).find(".avatar-picker__avatars").append(getAvatarElement(avatar));
                    });
                    handleAvatarClick(this);
                });

            $(this)
                .find("#avatar-style")
                .on("change", async (e) => {
                    const activeStyle = stylesConstant.find((style) => style.name === $(e.target).val());

                    $(this).find("#active-style-source").attr("href", activeStyle.source).text(activeStyle.label);
                    $(this).find("#active-style-author").attr("href", activeStyle.authorUrl).text(activeStyle.author);
                    $(this).find("#active-style-license").attr("href", activeStyle.licenseUrl).text(activeStyle.license);

                    const avatars = await getRandomAvatars(activeStyle.name);

                    $(this).find(".avatar-picker__avatars").empty();
                    avatars.forEach((avatar) => {
                        $(this).find(".avatar-picker__avatars").append(getAvatarElement(avatar));
                    });
                    handleAvatarClick(this);
                });
        },
    });
}
