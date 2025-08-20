/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license

TikiLink plugin for Tiki - Enhanced with shared context management
*/

// Cleanup any existing vendor plugin to prevent conflicts
if (typeof CKEDITOR !== 'undefined') {
    if (CKEDITOR.plugins.registered['tikilink']) {
        var existingPath = CKEDITOR.plugins.registered['tikilink'].path;
        if (existingPath && existingPath.indexOf('vendor_bundled/vendor/ckeditor/ckeditor/plugins/tikilink') !== -1) {
            delete CKEDITOR.plugins.registered['tikilink'];
        }
    }
    if (CKEDITOR.plugins.external) {
        delete CKEDITOR.plugins.external['tikilink'];
    }
}

if (typeof CKEDITOR !== 'undefined' && !CKEDITOR.plugins.registered['tikilink']) {
    CKEDITOR.plugins.add('tikilink', {
        requires: ['dialog'],
        init: function(editor) {
            // Use shared context manager for consistent behavior
            if (typeof window.TikiContextManager === 'undefined') {
                console.error('TikiLink plugin: TikiContextManager not loaded');
                return;
            }

            var contextManager = window.TikiContextManager;

            if (!contextManager.validateConfiguration(editor, 'TikiLink')) {
                return;
            }

            var tikiRoot = contextManager.getTikiRoot(editor);
            var pluginPath = tikiRoot + 'lib/ckeditor_tiki/plugins/_tikilink/';
            this.path = pluginPath;

            // Add the dialog command using shared configuration
            editor.addCommand('tikilink', contextManager.createCommandConfig('tikilink', editor));

            // Add toolbar button using shared configuration
            var buttonConfig = contextManager.createButtonConfig(
                'TikiLink',
                'tikilink',
                tikiRoot + 'img/icons/page_link.png',
                editor
            );
            editor.ui.addButton('tikilink', buttonConfig);

            // Add the CSS styles for anchor placeholders
            CKEDITOR.addCss(
                'img.cke_tikilink {' +
                    'background-image: url("' + tikiRoot + 'img/icons/page_link.png");' +
                    'background-position: center center;' +
                    'background-repeat: no-repeat;' +
                    'border: 1px solid #a9a9a9;' +
                    'width: 18px !important;' +
                    'height: 18px !important;' +
                '}\n' +
                'a.cke_tikilink {' +
                    'background-image: url("' + tikiRoot + 'img/icons/page_link.png");' +
                    'background-position: 0 center;' +
                    'background-repeat: no-repeat;' +
                    'border: 1px solid #a9a9a9;' +
                    'padding-left: 18px;' +
                '}'
            );

            // Add the dialog with explicit path
            CKEDITOR.dialog.add('tikilink', pluginPath + 'dialogs/tikilink.js');
        }
    });
}
