/**
 * Shared context management utilities for CKEditor Tiki plugins
 * Provides consistent calendar context detection and plugin configuration
 */

(function() {
    'use strict';

    // Prevent duplicate initialization
    if (window.TikiContextManager) {
        return;
    }

    window.TikiContextManager = {
        /**
         * Check if the editor is within a calendar modal context
         * @param {CKEDITOR.editor} editor - The CKEditor instance
         * @returns {boolean} True if in calendar context
         */
        isCalendarContext: function(editor) {
            if (!editor || !editor.element) {
                return false;
            }

            // Use performance utilities if available
            if (window.TikiPerformanceUtils) {
                return window.TikiPerformanceUtils.isCalendarContextOptimized(editor);
            }

            return !!editor.element.$.closest('.calendar-modal');
        },

        /**
         * Check if buttons should be shown for the given editor
         * @param {CKEDITOR.editor} editor - The CKEditor instance
         * @returns {boolean} True if buttons should be shown
         */
        shouldShowButtons: function(editor) {
            return !this.isCalendarContext(editor);
        },

        /**
         * Get the Tiki root path with proper error handling
         * @param {CKEDITOR.editor} editor - The CKEditor instance
         * @returns {string|null} The Tiki root path or null if not configured
         */
        getTikiRoot: function(editor) {
            var tikiRoot = editor.config._TikiRoot;
            if (!tikiRoot) {
                // Only log in development mode
                if (typeof console !== 'undefined' && console.warn && (typeof CKEDITOR === 'undefined' || CKEDITOR.env.dev)) {
                    console.warn('TikiContextManager: _TikiRoot not configured for editor', editor.name);
                }
                return null;
            }
            return tikiRoot;
        },

        /**
         * Validate plugin configuration and log warnings
         * @param {CKEDITOR.editor} editor - The CKEditor instance
         * @param {string} pluginName - Name of the plugin being initialized
         * @returns {boolean} True if configuration is valid
         */
        validateConfiguration: function(editor, pluginName) {
            var tikiRoot = this.getTikiRoot(editor);
            if (!tikiRoot) {
                // Only log in development mode
                if (typeof console !== 'undefined' && console.warn && (typeof CKEDITOR === 'undefined' || CKEDITOR.env.dev)) {
                    console.warn(pluginName + ' plugin: Skipping initialization due to missing _TikiRoot configuration');
                }
                return false;
            }
            return true;
        },

        /**
         * Create a standardized button configuration
         * @param {string} label - Button label
         * @param {string} command - CKEditor command name
         * @param {string} iconPath - Path to icon file
         * @param {CKEDITOR.editor} editor - The CKEditor instance
         * @returns {Object} Button configuration object
         */
        createButtonConfig: function(label, command, iconPath, editor) {
            var self = this;
            return {
                label: label,
                command: command,
                icon: iconPath,
                onRender: function() {
                    var button = this;
                    editor.on('instanceReady', function() {
                        if (self.isCalendarContext(editor)) {
                            // Instead of hiding, disable with tooltip
                            button.disable();
                            button.setTooltip('Links disabled in calendar context');
                        }
                    });
                }
            };
        },

        /**
         * Create a standardized command configuration
         * @param {string} dialogName - Name of the dialog
         * @param {CKEDITOR.editor} editor - The CKEditor instance
         * @returns {Object} Command configuration object
         */
        createCommandConfig: function(dialogName, editor) {
            var self = this;
            return new CKEDITOR.dialogCommand(dialogName, {
                modes: {
                    wysiwyg: function(editor) {
                        return !self.isCalendarContext(editor);
                    },
                    source: 0 // Disable in source mode
                }
            });
        }
    };

    // Export for use in other modules
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = window.TikiContextManager;
    }
})();
