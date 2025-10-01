export default function handleSlider(selector, fieldName) {
    const slider = $(selector);
    const inputHolder = $(`<input type="hidden" name="${fieldName}" value="${slider.attr("value")}">`);
    slider.after(inputHolder);

    slider[0].addEventListener("change", (e) => {
        const value = e.detail[0];
        inputHolder.attr("value", Array.isArray(value) ? value.join(",") : value);
        inputHolder.trigger("change");
    });
}
