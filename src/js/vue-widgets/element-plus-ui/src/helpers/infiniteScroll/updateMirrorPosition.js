export default function (mirror, el) {
    $(mirror).css({
        top: $(el).offset().top + $(el).outerHeight() / 4 - 5,
        left: $(el).offset().left + $(el).outerWidth() / 2,
        height: $(el).outerHeight(),
    });
}
