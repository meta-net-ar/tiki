/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license

External Link Dialog for Tiki
*/

CKEDITOR.dialog.add('externallink', function (editor) {
    var lang = {
        dlgTitle: "External Link",
        title: "Link Info",
        url: "Link to this URL",
        urlText: "Show this text",
        urlRel: "Relation",
        insert: "Insert",
        insertTitle: "Insert Link",
        cancel: "Cancel",
        urlRequired: "URL is required"
    };

    return {
        title: lang.dlgTitle,
        minWidth: 350,
        minHeight: 230,
        contents: [
            {
                id: 'info',
                label: lang.title,
                elements: [
                    {
                        type: 'text',
                        id: 'url',
                        label: lang.url,
                        validate: CKEDITOR.dialog.validate.notEmpty(lang.urlRequired)
                    },
                    {
                        type: 'text',
                        id: 'urlText',
                        label: lang.urlText,
                        setup: function(data) {
                            this.setValue(data.urlText || '');
                        }
                    },
                    {
                        type: 'text',
                        id: 'urlRel',
                        label: lang.urlRel,
                        setup: function(data) {
                            this.setValue(data.urlRel || '');
                        }
                    }
                ]
            }
        ],
        buttons: [
            CKEDITOR.dialog.cancelButton,
            {
                type: 'button',
                id: 'insert',
                label: lang.insert,
                title: lang.insertTitle,
                onClick: function() {
                    var dialog = this.getDialog();
                    var data = {};

                    // Get values directly from form elements
                    var url = dialog.getContentElement('info', 'url').getValue();
                    var urlText = dialog.getContentElement('info', 'urlText').getValue();
                    var urlRel = dialog.getContentElement('info', 'urlRel').getValue();

                    // Ensure URL has proper protocol
                    if (url && !/^https?:\/\//i.test(url)) {
                        url = 'http://' + url;
                    }

                    if (!url) {
                        // Use CKEditor's built-in validation instead of alert
                        var urlInput = dialog.getContentElement('info', 'url');
                        if (urlInput) {
                            urlInput.focus();
                        }
                        return;
                    }

                    // Always use Tiki wiki syntax format: [url|displayText|options]
                    var linkHtml = '[' + CKEDITOR.tools.htmlEncode(url);
                    if (urlText && urlText !== url) {
                        linkHtml += '|' + CKEDITOR.tools.htmlEncode(urlText);
                    }
                    if (urlRel) {
                        linkHtml += '|' + CKEDITOR.tools.htmlEncode(urlRel);
                    }
                    linkHtml += ']';

                    // Insert the formatted link
                    editor.insertHtml(linkHtml);
                    dialog.hide();
                }
            }
        ],
        onShow: function() {
            var selection = editor.getSelection();
            var element = selection.getSelectedElement();
            var data = {};

            if (element && element.is('a')) {
                var href = element.getAttribute('href');
                if (href && href.indexOf('tiki-index.php?page=') === -1) {
                    data.url = href;
                    data.urlText = element.getText();
                    data.urlRel = element.getAttribute('rel') || '';
                }
            } else {
                data.urlText = selection.getSelectedText();
            }

            this.setupContent(data);
        }
    };
});
