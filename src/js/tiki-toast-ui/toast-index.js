import Editor from "@toast-ui/editor";

function tikiPlugin(eventEmitter) {
    const reTikiPlugin = /\{(\w{2,})[ (]?.*}/i; // regex for tiki plugins (inline)
    const reWikiCollapsibleHeading = /^(#{1,6})(\$?)([\+\-]?)\s(.+)\n(.+)/;

    const thisEditor = eventEmitter.instance;

    const generateId = function () {
        return `tiki-${Math.random().toString(36).substr(2, 10)}`;
    };

    const pluginParse = function (syntax) {};

    let headingIndex = 0;
    const numbering = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0, 6: 0 };

    const toHTMLRenderers = {
        tiki(node, { entering }) {
            const id = generateId();
            let markup = node.literal;
            // remove the spaces at the ends of plugins that make toast fail
            markup = markup.replace(/ }/g, "}");
            const matches = markup.match(reTikiPlugin);
            const headingsMatches = markup.match(reWikiCollapsibleHeading);

            if (matches) {
                const pluginName = matches[1].toLowerCase();
                if (pluginName === "code") {
                    markup = markup.replaceAll(">", "&gt;");
                    markup = markup.replaceAll(/'/g, "&#039;");
                }

                setTimeout(function () {
                    $.getJSON(
                        $.service("plugin", "render"),
                        {
                            markup: markup,
                        },
                        function (data) {
                            const $pluginDiv = $("#" + id);

                            if (pluginName === "code") {
                                data.html = data.html.replaceAll("&amp;gt;", ">");
                                data.html = data.html.replaceAll("&amp;#039;", "'");
                            }
                            // buttons get overriden by toastui-editor.css as little pencils so change to <a>
                            data.html = data.html.replaceAll("<button", "<a");
                            data.html = data.html.replaceAll("</button>", "</a>");

                            // disable the hrefs so the popup form shows wherever you click
                            $pluginDiv.html(data.html).find("a").removeAttr("href");
                            $pluginDiv.find("input[type=submit]").attr("type", "");

                            $pluginDiv.on("click", function (e) {
                                // If we are in wiki_plugin code (and only there), and we clicked in the copy button
                                // skip popping the plugin form and just copy the target text
                                if (pluginName === "code" && e.target.classList.contains("icon_copy_code")) {
                                    $(document).tiki("copy")(
                                        null,
                                        function (element) {
                                            element.clearSelection();
                                            element.trigger.firstChild.innerHTML = tr("Copied to clipboard");
                                        },
                                        function () {
                                            alert(tr("Press Ctrl+C to copy"));
                                        },
                                        ".icon_copy_code"
                                    );
                                    return; // allow the click event to propagate to the code copy handler
                                }

                                // some plugins are intended to be edited from the page view, not while editing
                                if (["signature", "draw", "convene"].indexOf(pluginName) > -1) {
                                    return false;
                                }

                                const plugin = data.plugins[0];
                                const $this = $(this);
                                let index = 0;

                                // calculate the current index
                                $this
                                    .parents(".toastui-editor-contents")
                                    .find(".tiki_plugin.wikiplugin_" + pluginName)
                                    .each(function () {
                                        if ($this.attr("id") === $(this).attr("id")) {
                                            return false;
                                        } else {
                                            index++;
                                        }
                                    });
                                // if Markdown mode, do not show the plugin form
                                if (thisEditor.isMarkdownMode()) {
                                    feedback(tr("Switch to WYSIWYG mode to edit plugins"));
                                    return;
                                }
                                popupPluginForm(
                                    thisEditor.options.domId,
                                    pluginName,
                                    index,
                                    data.pageName,
                                    plugin.arguments,
                                    true,
                                    plugin.body,
                                    false,
                                    "",
                                    markup
                                );
                            });
                        },
                        "json"
                    );
                }, 100);

                return [
                    {
                        type: "openTag",
                        tagName: "div",
                        outerNewLine: false,
                        classNames: [`tui-widget tiki_plugin wikiplugin_${pluginName}`],
                        attributes: {
                            id: id,
                            dataPlugin: markup,
                        },
                    },
                    { type: "html", content: markup },
                    { type: "closeTag", tagName: "div", outerNewLine: false },
                ];
            } else if (headingsMatches) {
                headingIndex++;
                const flipperId = "iddlheading" + headingIndex;

                const level = headingsMatches[1].length;
                const tagName = "h" + level;
                const hText = headingsMatches[4].trimStart();
                const classNames = ["showhide_heading"];
                let numberingText = "";

                let textContent = markup.substr(markup.indexOf("\n"));

                if (headingsMatches[2]) {
                    const markdown = thisEditor.getMarkdown();
                    classNames.push("auto_numbered");
                    const markupPosition = markdown.indexOf(`$$tiki\n${markup}\n$$`);

                    if (thisEditor) {
                        for (let i = 1; i < level; i++) {
                            if (numbering[i] == 0) {
                                numbering[i] = 1;
                            }
                        }
                        numbering[level]++;
                        for (let i = level + 1; i <= 6; i++) {
                            numbering[i] = 0;
                        }
                        for (let i = 1; i <= level; i++) {
                            numberingText += (numberingText ? "." : "") + numbering[i];
                        }
                        console.log(numberingText);
                        numberingText += " ";
                    }
                }

                const tags = [
                    {
                        type: "openTag",
                        tagName: "div",
                    },
                    {
                        type: "openTag",
                        tagName: tagName,
                        attributes: {
                            id: hText.replace(/[^A-Z0-9]/gi, "_"),
                        },
                        classNames: classNames,
                    },
                    { type: "text", content: numberingText + hText },
                ];

                if (headingsMatches[3]) {
                    tags.push(
                        {
                            type: "openTag",
                            tagName: "a",
                            attributes: {
                                id: "flipper" + flipperId,
                                href: "#",
                                onclick: `flipWithSign('${flipperId}');return false;`,
                            },
                            classNames: ["link"],
                        },
                        {
                            type: "text",
                            content: `[${headingsMatches[3] == "+" ? "-" : "+"}]`,
                        },
                        { type: "closeTag", tagName: "a" }
                    );
                }

                tags.push({ type: "closeTag", tagName: tagName });

                if (headingsMatches[3]) {
                    tags.push(
                        {
                            type: "openTag",
                            tagName: "div",
                            classNames: ["showhide_heading"],
                            attributes: {
                                id: flipperId,
                                style: `display:${headingsMatches[3] == "+" ? "block" : "none"}`,
                            },
                        },
                        { type: "openTag", tagName: "p" },
                        { type: "html", content: textContent },
                        { type: "closeTag", tagName: "p" },
                        { type: "closeTag", tagName: "div" }
                    );
                } else {
                    tags.push({ type: "openTag", tagName: "p" }, { type: "html", content: textContent }, { type: "closeTag", tagName: "p" });
                }

                tags.push({ type: "closeTag", tagName: "div" });

                return tags;
            } else {
                return node; // not matched with a plugin nor special heading?
            }
        },
    };

    return { toHTMLRenderers };
}

export default function tikiToastEditor(options) {
    if (!options.domId) {
        console.error("No element id 'domId' option provided to tikiToast init");
        return;
    }

    const reTikiPlugin = /\{(\w{2,}) .*}/, // regex for tiki plugins (inline)
        reFileReference = /\(\((\d+)|file\)\)/, // regex for file reference syntax
        reWikiLink = /\((\w*)\((.*?)\)\)/, // regex for wiki link syntax
        reWikiExternalLink = /(?<![\[\^])\[([^\[\|\]\^]+)(?:\|?[^\[\|\]]*){0,2}\](?!\()/, // regex for external link syntax
        inputDomId = options.domId, // the hidden input to hold a copy of the Markdown
        editorDomId = inputDomId + "_editor"; // div to contain the toast ui editor
    let thisEditor;
    let link_origin = location.origin + (jqueryTiki.sefurl ? "/" : `/tiki-index.php?page=`);

    if (typeof window.tuiEditors !== "object") {
        window.tuiEditors = {};
    }
    let timer = null;
    const execAutoSave = delayedExecutor(500, function () {
        auto_save(inputDomId);
    });

    function isUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (e) {
            return false;
        }
    }
    // open modal box dialog box
    function openLinkDialogBox(id, type_link, label_title, label_text, inputDomId, syntax_text, page) {
        window.registerApplication({
            name: id,
            app: () => importShim("@vue-mf/toolbar-dialogs"),
            activeWhen: (location) => {
                let condition = true;
                return condition;
            },
            customProps: {
                toolbarObject: {
                    label: label_title,
                    labelText: label_text,
                    labelPage: page,
                    name: type_link,
                    domElementId: inputDomId,
                    editor: { isMarkdown: true, isWysiwyg: true },
                    isMarkdown: true,
                    isWysiwyg: true,
                },
                syntax: syntax_text,
            },
        });

        onDOMElementRemoved(`"${id}"`, function () {
            window.unregisterApplication(`"${id}"`);
        });
    }

    let tuiOptions = $.extend(
        true,
        {},
        {
            el: document.querySelector("#" + editorDomId),
            events: {
                change: function () {
                    // update the hidden input with the markdown content
                    document.querySelector("#" + inputDomId).value = thisEditor.getMarkdown();
                    execAutoSave();
                },
                load: function (editor) {
                    let md = editor.getMarkdown();
                    // maybe some more init here?
                    $("#editwiki_editor").resizable();
                },
                keyup: function (editorType, ev) {
                    if (ev.key === "(") {
                        const popup = document.createElement("ul");
                        // ...

                        thisEditor.addWidget(popup, "top");
                    }
                },
                command: function (payload) {
                    const command_list = ["italic", "bold", "code", "strike"];
                    if (command_list.includes(payload)) {
                        let editor_selection = thisEditor.getSelection();
                        let select_text = thisEditor.getSelectedText(editor_selection[0], editor_selection[1]);
                        let len = select_text.replace(/^\s*$/, "").length;
                        if (len === 0) {
                            thisEditor.exec(payload);
                        }
                    }
                },
            },
            plugins: [tikiPlugin],
            widgetRules: [
                {
                    rule: reWikiLink,
                    toDOM(text) {
                        const fileReferenceMatch = text.match(reFileReference);
                        if (fileReferenceMatch) {
                            const element = document.createElement("span");
                            element.classList.add("badge", "rounded-pill", "bg-secondary");
                            element.setAttribute("role", "button");
                            element.dataset.fileRefId = fileReferenceMatch[1];
                            element.innerHTML = `<i class="fa fa-file"></i> ${tr("File")}`;
                            return element;
                        } else {
                            const match = text.match(reWikiLink),
                                anchorElement = document.createElement("a");

                            const parts = match[2].split("|");

                            let page,
                                semantic,
                                anchor = "",
                                label_text = "";

                            semantic = match[1];

                            if (parts.length === 3) {
                                page = parts[0];
                                anchor = parts[1];
                                label_text = parts[2];
                            } else if (parts.length === 2) {
                                page = parts[0];
                                label_text = parts[1];
                            } else {
                                page = match[2];
                                label_text = match[2];
                            }
                            anchorElement.dataset.page = page;
                            anchorElement.dataset.anchor = anchor;
                            anchorElement.dataset.semantic = semantic;
                            anchorElement.classList.add("wiki-link");
                            anchorElement.setAttribute("title", tr("wiki link"));
                            const elementId = "single-spa-application-toolbar-dialogs-" + (Math.random() + 1).toString(36).substring(7);
                            anchorElement.setAttribute("id", `single-spa-application:${elementId}`);
                            anchorElement.onclick = function () {
                                let range = document.createRange();
                                range.selectNode(anchorElement);
                                window.getSelection().removeAllRanges();
                                window.getSelection().addRange(range);
                            };
                            openLinkDialogBox(elementId, "tikilink", tr("Tiki link"), label_text, inputDomId, text, "");
                            return anchorElement;
                        }
                    },
                },
                {
                    rule: reWikiExternalLink,
                    toDOM(text) {
                        let match = text.match(reWikiExternalLink);
                        const anchorElement = document.createElement("a");
                        let extract_link = [],
                            split_link = [];
                        let label_text,
                            anchor,
                            page = "";
                        if (match != null && match.length > 0) {
                            extract_link = match[0].substring(1, match[0].length - 1);
                            split_link = extract_link.split("|");
                            label_text = split_link[0];
                            page = split_link[1] !== undefined ? split_link[1] : label_text;
                        } else {
                            // in case we want to edit an existing link, toastUI transform it to a Markdown link
                            const regex = /\[(.*?)\]\((.*?)\)/;
                            match = text.match(regex);
                            label_text = match[2];
                            page = match[1];
                        }
                        // escape check button to not be interpreted as a link
                        if (match[1].replaceAll(" ", "").length === 0 || match[1].toLowerCase() === "x") {
                            const span = document.createElement("span");
                            span.innerHTML = match[0];
                            return span;
                        }
                        anchorElement.dataset.page = page;
                        anchorElement.dataset.anchor = page;
                        anchorElement.dataset.semantic = page;
                        anchorElement.classList.add("wiki", "external");
                        anchorElement.setAttribute("title", tr("External link"));
                        const elementId = "single-spa-application-toolbar-dialogs-" + (Math.random() + 1).toString(36).substring(7);
                        anchorElement.setAttribute("id", `single-spa-application:${elementId}`);

                        anchorElement.onclick = function () {
                            let range = document.createRange();
                            range.selectNode(anchorElement);
                            window.getSelection().removeAllRanges();
                            window.getSelection().addRange(range);
                        };
                        openLinkDialogBox(elementId, "link", tr("External link"), page, inputDomId, text, label_text);
                        return anchorElement;
                    },
                },
            ],
        },
        options
    );

    // create the editor
    thisEditor = new Editor(tuiOptions);
    window.tuiEditors[inputDomId] = thisEditor;
}
