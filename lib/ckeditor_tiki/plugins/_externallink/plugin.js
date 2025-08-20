/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license

External Link plugin for Tiki - Enhanced with shared context management
*/

// Cleanup any existing vendor plugin to prevent conflicts
if (typeof CKEDITOR !== 'undefined') {
    if (CKEDITOR.plugins.registered['externallink']) {
        var existingPath = CKEDITOR.plugins.registered['externallink'].path;
        if (existingPath && existingPath.indexOf('vendor_bundled/vendor/ckeditor/ckeditor/plugins/externallink') !== -1) {
            delete CKEDITOR.plugins.registered['externallink'];
        }
    }
    if (CKEDITOR.plugins.external) {
        delete CKEDITOR.plugins.external['externallink'];
    }
}

// Prevent duplicate plugin registration
if (typeof CKEDITOR !== 'undefined' && !CKEDITOR.plugins.registered['externallink']) {
    CKEDITOR.plugins.add('externallink', {
        requires: ['dialog'],
        init: function(editor) {
            // Use shared context manager for consistent behavior
            if (typeof window.TikiContextManager === 'undefined') {
                // Only log in development mode
                if (typeof console !== 'undefined' && console.error && (typeof CKEDITOR === 'undefined' || CKEDITOR.env.dev)) {
                    console.error('ExternalLink plugin: TikiContextManager not loaded');
                }
                return;
            }

            var contextManager = window.TikiContextManager;

            // Validate configuration with proper error handling
            if (!contextManager.validateConfiguration(editor, 'ExternalLink')) {
                return;
            }

            var tikiRoot = contextManager.getTikiRoot(editor);
            var pluginPath = tikiRoot + 'lib/ckeditor_tiki/plugins/_externallink/';
            this.path = pluginPath;

            // Add the dialog command using shared configuration
            editor.addCommand('externallink', contextManager.createCommandConfig('externallink', editor));

            // Add toolbar button using shared configuration
            var buttonConfig = contextManager.createButtonConfig(
                'External Link',
                'externallink',
                tikiRoot + 'img/icons/world_link.png',
                editor
            );
            editor.ui.addButton('externallink', buttonConfig);

            // Add the dialog with explicit path
            CKEDITOR.dialog.add('externallink', pluginPath + 'dialogs/externallink.js');
        }
    });
}
