export default function (context) {
    const id = context.$note.attr("id");
    if (window.languageCheckTrigger) {
        window.languageCheckTrigger(id);
    }
}
