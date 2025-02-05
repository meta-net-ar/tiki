/**
 * Wrapper for tiki wikiplugins in Toast UI Editor
 */


/**
 * Process a tiki wiki plugin
 *
 * @returns array of nodes
 */
function tikiPlugin(eventEmitter)
{
    const reTikiPlugin = /\{(\w{2,})[ (]?.*}/i;      // regex for tiki plugins (inline)
    const reWikiCollapsibleHeading = /^(#{1,6})(\$?)([\+\-]?)\s(.+)\n(.+)/;

    const thisEditor = eventEmitter.instance;

    const generateId = function () {
        return `tiki-${Math.random().toString(36).substr(2, 10)}`;
    };

    const pluginParse = function (syntax) {

    };

    let headingIndex = 0;
    const numbering = { 1:0, 2:0, 3:0, 4:0, 5:0, 6:0 };

    const toHTMLRenderers = {
        tiki(node, { entering })
        {
            const id = generateId();
            let markup = node.literal;
            // remove the spaces at the ends of plugins that make toast fail
            markup = markup.replace(/ }/g, '}');
            const matches = markup.match(reTikiPlugin);
            const headingsMatches = markup.match(reWikiCollapsibleHeading);

            if (matches) {
                const pluginName = matches[1].toLowerCase();
                if (pluginName === 'code') {
                    markup = markup.replaceAll('>', "&gt;");
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

                            if (pluginName === 'code') {
                                data.html = data.html.replaceAll('&amp;gt;', '>');
                                data.html = data.html.replaceAll('&amp;#039;', "'");
                            }
                            // buttons get overriden by toastui-editor.css as little pencils so change to <a>
                            data.html = data.html.replaceAll('<button', '<a');
                            data.html = data.html.replaceAll('</button>', '</a>');

                            // disable the hrefs so the popup form shows wherever you click
                            $pluginDiv.html(data.html).find("a").removeAttr("href");
                            $pluginDiv.find("input[type=submit]").attr("type", "");

                            $pluginDiv.on("click", function (e) {
                                // If we are in wiki_plugin code (and only there), and we clicked in the copy button
                                // skip popping the plugin form and just copy the target text
                                if (pluginName === 'code' && e.target.classList.contains('icon_copy_code')) {
                                    $(document).tiki('copy')(null, function(element) {
                                        element.clearSelection();
                                        element.trigger.firstChild.innerHTML = tr('Copied to clipboard');
                                    }, function() {
                                        alert(tr("Press Ctrl+C to copy"));
                                    }, '.icon_copy_code');
                                    return ; // allow the click event to propagate to the code copy handler
                                }

                                // some plugins are intended to be edited from the page view, not while editing
                                if (["signature","draw","convene"].indexOf(pluginName) > -1) {
                                    return false;
                                }

                                const plugin = data.plugins[0];
                                const $this = $(this);
                                let index = 0;

                                // calculate the current index
                                $this.parents(".toastui-editor-contents")
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
                        type: 'openTag',
                        tagName: 'div',
                        outerNewLine: false,
                        classNames: [`tui-widget tiki_plugin wikiplugin_${pluginName}`],
                        attributes: {
                            id: id,
                            dataPlugin: markup,
                        }
                    },
                    {type: 'html', content: markup},
                    {type: 'closeTag', tagName: 'div', outerNewLine: false}
                ];
            } else if (headingsMatches) {
                headingIndex++;
                const flipperId = 'iddlheading' + headingIndex;

                const level = headingsMatches[1].length;
                const tagName = 'h' + level;
                const hText = headingsMatches[4].trimStart();
                const classNames = ['showhide_heading'];
                let numberingText = '';

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
                            numberingText += (numberingText? '.' : '') + numbering[i];
                        }
                        console.log(numberingText);
                        numberingText += ' ';
                    }
                }

                const tags = [
                    {
                        type: 'openTag',
                        tagName: 'div',
                    },
                    {
                        type: 'openTag',
                        tagName: tagName,
                        attributes: {
                            id: hText.replace(/[^A-Z0-9]/ig, "_"),
                        },
                        classNames: classNames
                    },
                    { type: 'text', content: numberingText + hText },
                ];

                if (headingsMatches[3]) {
                    tags.push(
                        {
                            type: 'openTag',
                            tagName: 'a',
                            attributes: {
                                id: 'flipper' + flipperId,
                                href: '#',
                                onclick: `flipWithSign('${flipperId}');return false;`
                            },
                            classNames: ['link'],
                        },
                        {
                            type: 'text',
                            content: `[${headingsMatches[3] == '+'? '-': '+'}]`
                        },
                        { type: 'closeTag', tagName: 'a' }
                    );
                }

                tags.push({ type: 'closeTag', tagName: tagName });

                if (headingsMatches[3]) {
                    tags.push(
                        {
                            type: 'openTag',
                            tagName: 'div',
                            classNames:['showhide_heading'],
                            attributes: {
                                id: flipperId,
                                style: `display:${headingsMatches[3] == '+'? 'block': 'none'}`
                            }
                        },
                        { type: 'openTag', tagName: 'p'},
                        { type: 'html', content: textContent },
                        { type: 'closeTag', tagName: 'p' },
                        { type: 'closeTag', tagName: 'div' }
                    );
                } else {
                    tags.push(
                        { type: 'openTag', tagName: 'p'},
                        { type: 'html', content: textContent },
                        { type: 'closeTag', tagName: 'p' },
                    );
                }

                tags.push({ type: 'closeTag', tagName: 'div' });

                return tags;
            } else {
                return node;    // not matched with a plugin nor special heading?
            }
        },
    };

    return {toHTMLRenderers};
}

