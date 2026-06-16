# Here is an explanation of the patches in this folder:

| Package | Problem solved | Mechanism |
|---------|----------------|--------|
| single-spa | Cypht page handlers are called twice because single-spa fires the `popstate` event when `history.pushState()` is called. | The overwriting of the two methods (`pushState()` and `replaceState()`) in the History API is stopped to prevent the issue.|