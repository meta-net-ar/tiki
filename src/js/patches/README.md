# IMPORTANT NOTE

I am vetoing the addition on any new patches here.  My goal is to get rid of this subsystem for reasons discussed elsewhere.

If you truly thing something needs patching, cannot be monkey-patched, cannot be temporarily or permanently forked or upstreamed, discuss it with me directly - benoitg - 2025-09-09

# Here is an explanation of the patches in this folder:

| Package | Problem solved | Mechanism |
|---------|----------------|--------|
| single-spa | Cypht page handlers are called twice because single-spa fires the `popstate` event when `history.pushState()` is called. | The overwriting of the two methods (`pushState()` and `replaceState()`) in the History API is stopped to prevent the issue.|
