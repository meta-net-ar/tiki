export default function () {
    if (process.env.NODE_ENV === "test") {
        const { resolve } = require("path");
        return resolve(__dirname, "../../../../../../");
    } else {
        return window.location.pathname.split("/").slice(0, -1).join("/");
    }
}
