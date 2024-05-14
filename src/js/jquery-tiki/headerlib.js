export function addScript(scriptOriginalHash, scriptHtml) {
    if (!window.tikiLoadedJavascriptSourceHashes.includes(scriptOriginalHash)) {
        let wrapper = document.createElement("div");
        wrapper.innerHTML = scriptHtml;
        let script = wrapper.firstChild;
        document.body.appendChild(script);
        return true;
    } else {
        return false;
    }
}
