# IMPORTANT NOTE

I am vetoing the addition on any new patches here.  My goal is to get rid of this subsystem for reasons discussed elsewhere.

If you truly thing something needs patching, cannot be monkey-patched, cannot be temporarily or permanently forked or upstreamed, discuss it with me directly - benoitg - 2025-09-09

# Here is an explanation of the patches in this folder:

| Package | Problem solved | Mechanism |
|---------|----------------|--------|
| single-spa | Cypht page handlers are called twice because single-spa fires the `popstate` event when `history.pushState()` is called. | The overwriting of the two methods (`pushState()` and `replaceState()`) in the History API is stopped to prevent the issue.|
| element-plus | 1. Selecting an element from any picker dropdown (select, autocomplete, datepicker) causes the modal to automatically scroll to the top of its body content and focus on its close icon in the top right corner. <br><br> 2. Inability to select an item from the autocomplete and the select dropdowns. | 1. Move focus to the parent element of the active element, instead of passing it to the HTML body element when an option has been picked from the dropdown. <br> <br> 2. The Element Plus Tooltip uses the active DOM element to determine whether the picker dropdown should emit a blur event (which cancels the select event from being emitted). A check is performed on this active element to determine whether it is a child of the popper element. However, because Tiki builds these components as custom DOM elements, the active element always resolves to the element fragment, causing the validation to fail. To address this, we reverse the check to be performed on the element fragment against the popper. |