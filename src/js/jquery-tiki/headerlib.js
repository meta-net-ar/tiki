export function addScript(scriptOriginalHash, scriptHtml, target = document.body) {
    if (!window.tikiLoadedJavascriptSourceHashes.includes(scriptOriginalHash)) {
        console.log(`headerlib.js: adding missing ${scriptOriginalHash}`);
        let wrapper = document.createElement("div");
        wrapper.innerHTML = scriptHtml;
        let script = wrapper.firstChild;
        target.appendChild(script);
        window.tikiLoadedJavascriptSourceHashes.push(scriptOriginalHash);
        return true;
    } else {
        return false;
    }
}
export function processJsonPayload(payload) {
    console.log(payload);
    for (const payloadType in payload) {
        //console.log(payloadType);
        for (const hash in payload[payloadType]) {
            let html = payload[payloadType][hash];
            //console.log(hash, html);
            addScript(hash, html, document.head);
        }
    }
}
