export default function handleTransferList(transferId, fieldName) {
    const transfer = document.getElementById(transferId);
    let select = transfer.parentNode.querySelector(`select[name="${fieldName}"]`);
    if (!select) {
        select = document.createElement("select");
        select.name = fieldName;
        select.multiple = true;
        select.setAttribute("aria-hidden", "true");

        select.setAttribute("element-plus-ref", transferId);

        const defaultValues = transfer.getAttribute("default-value");
        if (defaultValues) {
            const values = JSON.parse(defaultValues);
            values.forEach((v) => {
                const option = document.createElement("option");
                option.value = v;
                option.selected = true;
                select.appendChild(option);
            });
        }
        // Enclose it in a hidden div so its related error messages are hidden too
        const div = document.createElement("div");
        div.style.display = "none";
        div.appendChild(select);
        transfer.parentNode.insertAdjacentElement("beforeend", div);
    }
    transfer.addEventListener("transfer-change", function (event) {
        const value = event.detail[0].value;
        select.innerHTML = "";
        value.forEach((v) => {
            const option = document.createElement("option");
            option.value = v;
            option.selected = true;
            select.appendChild(option);
        });
        $(select).trigger("change");
    });
}
