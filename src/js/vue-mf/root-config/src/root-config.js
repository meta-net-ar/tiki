import { registerApplication, unregisterApplication, start } from "single-spa";

window.registerApplication = registerApplication;
window.unregisterApplication = unregisterApplication;

/*
Cypht uses the browser's History API to handle navigation; however,
single-spa overwrites the History API by firing popstate events whenever pushState() or replaceState() is called.
This causes Cypht's pages to be rendered twice. The following line (in a hacky way) prevents single-spa from overwriting the History API,
which Tiki does not rely on altogether.
*/
IsInBrowser = false;

start();
