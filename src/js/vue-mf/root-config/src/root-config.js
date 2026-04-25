import { registerApplication, unregisterApplication, start, getMountedApps } from "single-spa";

window.registerApplication = registerApplication;
window.unregisterApplication = unregisterApplication;
window.getMountedApps = getMountedApps;
window.start = start;

start();
