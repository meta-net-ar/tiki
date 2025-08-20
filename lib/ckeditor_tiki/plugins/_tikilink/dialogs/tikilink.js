/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license

External #1 for Tiki 6+ TikiLink
*/

CKEDITOR.dialog.add('tikilink', function(editor) {
    var lang = {
        dlgTitle: "Wiki Link",
        title: "Wiki Link",
        linkPage: "Link to page",
        linkText: "Show this text",
        insert: "Insert",
        cancel: "Cancel"
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
                        id: 'tikilinkPage',
                        label: lang.linkPage,
                        validate: CKEDITOR.dialog.validate.notEmpty('Page name is required'),
                        setup: function(data) {
                            if (data.tikilinkPage) {
                                this.setValue(data.tikilinkPage);
                            }

                            this.setupAutocomplete();
                        },

                        setupAutocomplete: function() {
                            var input = this.getInputElement();
                            if (!input || !input.$) {
                                console.warn('TikiLink dialog: Input element not found');
                                return;
                            }

                            var inputElement = input.$;
                            var self = this;

                            if (typeof jqueryTiki !== 'undefined' && jqueryTiki.autocomplete) {
                                try {
                                    this.initializeAutocompleteWithRetry(inputElement, input);
                                } catch (e) {
                                    console.error('TikiLink dialog: Failed to initialize autocomplete:', e);
                                }
                            } else {
                                console.warn('TikiLink dialog: jQuery Tiki autocomplete not available');
                            }
                        },

                        initializeAutocompleteWithRetry: function(inputElement, input) {
                            var self = this;
                            var retryCount = 0;
                            var maxRetries = 3;

                            function attemptInitialization() {
                                try {
                                    var $selectWrapper = $('<div class="el-select"></div>');
                                    $(inputElement).wrap($selectWrapper);

                                    // Initialize autocomplete
                                    $(inputElement).tiki('autocomplete', 'pagename', {
                                        minLength: 1,
                                        delay: 100,
                                        select: function(event, ui) {
                                            input.setValue(ui.item.value);
                                        }
                                    }).addClass('el-input__inner');

                                    // Apply consistent styling
                                    $selectWrapper.css({
                                        'display': 'block',
                                        'position': 'relative',
                                        'width': '100%'
                                    });

                                    // Style the dropdown
                                    $('.ui-autocomplete').addClass('el-select-dropdown').css({
                                        'display': 'block',
                                        'position': 'absolute',
                                        'z-index': 99999,
                                        'background': '#fff',
                                        'border': '1px solid #e4e7ed',
                                        'border-radius': '4px',
                                        'box-shadow': '0 2px 12px 0 rgba(0,0,0,.1)'
                                    });

                                } catch (e) {
                                    retryCount++;
                                    if (retryCount < maxRetries) {
                                        console.warn('TikiLink dialog: Autocomplete initialization failed, retrying...', e);
                                        setTimeout(attemptInitialization, 100 * retryCount);
                                    } else {
                                        console.error('TikiLink dialog: Failed to initialize autocomplete after', maxRetries, 'attempts:', e);
                                    }
                                }
                            }

                            // Start initialization with a small delay to ensure DOM is ready
                            setTimeout(attemptInitialization, 50);
                        }
                    },
                    {
                        type: 'text',
                        id: 'tikilinkText',
                        label: lang.linkText,
                        setup: function(data) {
                            this.setValue(data.tikilinkText || '');
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
                title: lang.insert,
                onClick: function() {
                    var dialog = this.getDialog();

                    // Get values directly from form elements
                    var pageName = dialog.getContentElement('info', 'tikilinkPage').getValue();
                    var linkText = dialog.getContentElement('info', 'tikilinkText').getValue();

                    if (!pageName) {
                        alert('Page name is required');
                        return;
                    }

                    // Always use Tiki wiki syntax format: ((pageName|displayText))
                    var linkHtml = '((' + CKEDITOR.tools.htmlEncode(pageName);
                    if (linkText && linkText !== pageName) {
                        linkHtml += '|' + CKEDITOR.tools.htmlEncode(linkText);
                    }
                    linkHtml += '))';

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

            // Check if we're in a calendar context
            var isCalendar = editor.element && editor.element.$.closest('.calendar-modal');

            if (!isCalendar && element && element.is('a')) {
                var href = element.getAttribute('href');
                if (href && href.indexOf('tiki-index.php?page=') !== -1) {
                    data.tikilinkPage = decodeURIComponent(href.replace('tiki-index.php?page=', ''));
                    data.tikilinkText = element.getText();
                }
            } else if (!isCalendar) {
                data.tikilinkText = selection.getSelectedText();
            }

            this.setupContent(data);
        }
    };
});
