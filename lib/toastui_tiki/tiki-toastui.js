/**
 * Tiki support js for toast ui and markdown wiki syntax
 */

function tikiToastEditor(options)
{
    if (!options.domId) {
        console.error("No element id 'domId' option provided to tikiToast init");
        return;
    }

    const reTikiPlugin = /\{(\w{2,}) .*}/,      // regex for tiki plugins (inline)
        reWikiLink = /\((\w*)\((.*?)\)\)/,           // regex for wiki link syntax
        reWikiExternalLink = /(?<![\[\^])\[([^\[\|\]\^]+)(?:\|?[^\[\|\]]*){0,2}\](?!\()/,
        Editor = toastui.Editor,                // global toastui Editor factory
        inputDomId = options.domId,             // the hidden input to hold a copy of the markdown
        editorDomId = inputDomId + "_editor";   // div to contain the toast ui editor
    let thisEditor;
    let link_origin = location.origin + ( jqueryTiki.sefurl ? '/' : `/tiki-index.php?page=` );

    if (typeof window.tuiEditors !== 'object') {
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
    function openLinkDialogBox(id, type_link, label_title, label_text, inputDomId, syntax_text, page)
    {
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
                    editor : {isMarkdown: true, isWysiwyg: true},
                    isMarkdown : true,
                    isWysiwyg : true
                },
                syntax: syntax_text,
            },
        });

        onDOMElementRemoved(`"${id}"`, function () {
            window.unregisterApplication(`"${id}"`);
        });
    }

    let tuiOptions = $.extend(true, {}, {
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
            },
            keyup: function (editorType, ev) {
                if (ev.key === '(') {
                    const popup = document.createElement('ul');
                    // ...

                    thisEditor.addWidget(popup, 'top');
                }
            },
        },
        plugins: [tikiPlugin],
        widgetRules: [{
            rule: reWikiLink,
            toDOM(text)
            {
                const match = text.match(reWikiLink),
                    anchorElement = document.createElement('a');

                const parts = match[2].split("|");

                let page, semantic, anchor = "", label_text = "";

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
                anchorElement.setAttribute('title', tr('wiki link'));
                const elementId = 'single-spa-application-toolbar-dialogs-' + (Math.random() + 1).toString(36).substring(7);
                anchorElement.setAttribute('id', `single-spa-application:${elementId}`);
                anchorElement.onclick = function() {
                    let range = document.createRange();
                    range.selectNode(anchorElement);
                    window.getSelection().removeAllRanges();
                    window.getSelection().addRange(range);
                };
                openLinkDialogBox(elementId,'tikilink', tr('Tiki link'), label_text, inputDomId, text,'');
                return anchorElement;
            }
        }, {
            rule: reWikiExternalLink,
            toDOM(text)
            {
                let match = text.match(reWikiExternalLink);
                const anchorElement = document.createElement('a');
                let extract_link = [], split_link=[];
                let label_text, anchor, page = '';
                if (match != null && match.length > 0) {
                    extract_link = match[0].substring(1, match[0].length - 1);
                    split_link = extract_link.split('|');
                    label_text = split_link[0];
                    page = split_link[1] !== undefined ? split_link[1] : label_text;
                } else {
                    // in case we want to edit an existing link, toastUI transform it to a Markdown link
                    const regex = /\[(.*?)\]\((.*?)\)/;
                    match = text.match(regex);
                    label_text = match[2];
                    page = match[1];
                }
                anchorElement.dataset.page = page;
                anchorElement.dataset.anchor = page;
                anchorElement.dataset.semantic = page;
                anchorElement.classList.add('wiki', 'external');
                anchorElement.setAttribute('title', tr('External link'));
                const elementId = 'single-spa-application-toolbar-dialogs-' + (Math.random() + 1).toString(36).substring(7);
                anchorElement.setAttribute('id', `single-spa-application:${elementId}`);

                anchorElement.onclick = function() {
                    let range = document.createRange();
                    range.selectNode(anchorElement);
                    window.getSelection().removeAllRanges();
                    window.getSelection().addRange(range);
                };
                openLinkDialogBox(elementId,'link', tr('External link'), page, inputDomId, text, label_text);
                return anchorElement;
            }
        }
    ],
    }, options);

// create the editor
    thisEditor = new Editor(tuiOptions);
    window.tuiEditors[inputDomId] = thisEditor;

}