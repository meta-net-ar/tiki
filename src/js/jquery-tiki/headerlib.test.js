import { expect, test, beforeEach, afterEach } from "vitest";
import * as headerlib from "./headerlib";
import { JSDOM } from "jsdom";

function initJSDOM(markup, options = {}) {
    const dom = new JSDOM(markup, options);

    global.window = dom.window;
    global.document = dom.window.document;
    global.navigator = dom.window.navigator;
    global.location = dom.window.location;
    global.XMLHttpRequest = dom.window.XMLHttpRequest;

    return dom;
}

beforeEach(async (context) => {
    let html = `<body>
            <div id="content"></div>
            <script id="srcHash1" type="text/javascript">;</script>
            <script id="srcHash2" type="text/javascript">;</script>
            <script type="text/javascript">
            tikiLoadedJavascriptSourceHashes=[
                "srcHash1",
                "srcHash2"
            ];
            </script>
            </body>`;
    initJSDOM(html, { runScripts: "dangerously" });
    let scripts = document.querySelectorAll("script");
    expect(scripts.length).toBe(3);
    context.newScript = `<script id="newScriptHash" type="text/javascript">;</script>`;
});

afterEach(async (context) => {
    initJSDOM();
});

test("Script can be added", ({ newScript }) => {
    let scripts = document.querySelectorAll("script");
    expect(scripts.length).toBe(3);
    let addReturn = headerlib.addScript("newScriptHash", newScript);
    expect(addReturn).toBe(true);
    scripts = document.querySelectorAll("script");
    expect(scripts.length).toBe(4);
});

test("Script with an existing hash isn't added", ({ newScript }) => {
    let addReturn = headerlib.addScript("srcHash1", newScript);
    //console.log("addReturn:", addReturn);
    expect(addReturn).toBe(false);
    let scripts = document.querySelectorAll("script");
    expect(scripts.length).toBe(3);
});
