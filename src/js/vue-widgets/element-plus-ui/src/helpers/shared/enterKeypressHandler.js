export default function (originalElement, elementPlusUi) {
    const $form = originalElement.closest("form");
    if ($form.length) {
        originalElement.val(elementPlusUi.val());
        const blockingInputCount = $form[0].querySelectorAll(
            'input[type="text"], input[type="search"], input[type="url"], input[type="tel"], input[type="email"], input[type="password"], input[type="date"], input[type="month"], input[type="week"], input[type="time"], input[type="datetime-local"], input[type="number"]'
        ).length;
        if (blockingInputCount <= 1) {
            const submitButton = $form.find('button[type="submit"], input[type="submit"]').first();
            if (submitButton.length) {
                submitButton.trigger("click");
            } else {
                $form.trigger("submit");
            }
        }
    }
}
