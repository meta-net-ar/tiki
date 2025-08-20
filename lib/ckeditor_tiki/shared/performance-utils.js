/**
 * Performance utilities for CKEditor Tiki plugins
 * Provides optimized DOM queries and event handling
 */

(function() {
    'use strict';

    // Prevent duplicate initialization
    if (window.TikiPerformanceUtils) {
        return;
    }

    window.TikiPerformanceUtils = {
        // Cache for DOM queries
        _cache: new Map(),
        _cacheTimeout: 1000, // 1 second cache timeout

        /**
         * Cached DOM query with timeout
         * @param {string} selector - CSS selector
         * @param {Element} context - Context element (optional)
         * @returns {Element|null} The found element or null
         */
        cachedQuery: function(selector, context) {
            const cacheKey = selector + (context ? context.id : '');
            const cached = this._cache.get(cacheKey);

            if (cached && Date.now() - cached.timestamp < this._cacheTimeout) {
                return cached.element;
            }

            const element = context ? context.querySelector(selector) : document.querySelector(selector);

            this._cache.set(cacheKey, {
                element: element,
                timestamp: Date.now()
            });

            return element;
        },

        /**
         * Clear expired cache entries
         */
        clearExpiredCache: function() {
            const now = Date.now();
            for (const [key, value] of this._cache.entries()) {
                if (now - value.timestamp > this._cacheTimeout) {
                    this._cache.delete(key);
                }
            }
        },

        /**
         * Debounced function execution
         * @param {Function} func - Function to debounce
         * @param {number} wait - Wait time in milliseconds
         * @returns {Function} Debounced function
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Throttled function execution
         * @param {Function} func - Function to throttle
         * @param {number} limit - Time limit in milliseconds
         * @returns {Function} Throttled function
         */
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        /**
         * Optimized event listener with automatic cleanup
         * @param {Element} element - Target element
         * @param {string} event - Event type
         * @param {Function} handler - Event handler
         * @param {Object} options - Event options
         * @returns {Function} Cleanup function
         */
        addEventListenerWithCleanup: function(element, event, handler, options = {}) {
            const wrappedHandler = this.debounce(handler, options.debounce || 0);
            element.addEventListener(event, wrappedHandler, options);

            return () => {
                element.removeEventListener(event, wrappedHandler, options);
            };
        },

        /**
         * Batch DOM operations for better performance
         * @param {Function} operations - Function containing DOM operations
         */
        batchDOMOperations: function(operations) {
            // Use requestAnimationFrame for smooth updates
            requestAnimationFrame(() => {
                operations();
            });
        },

        /**
         * Check if element is visible in viewport
         * @param {Element} element - Element to check
         * @returns {boolean} True if visible
         */
        isElementVisible: function(element) {
            if (!element) return false;

            const rect = element.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        },

        /**
         * Optimized context detection with caching
         * @param {CKEDITOR.editor} editor - The CKEditor instance
         * @returns {boolean} True if in calendar context
         */
        isCalendarContextOptimized: function(editor)
        {
            if (!editor || !editor.element) {
                return false;
            }

            const cacheKey = 'calendar-context-' + editor.name;
            const cached = this._cache.get(cacheKey);

            if (cached && Date.now() - cached.timestamp < this._cacheTimeout) {
                return cached.element;
            }

            const isCalendar = !!editor.element.$.closest('.calendar-modal');

            this._cache.set(cacheKey, {
                element: isCalendar,
                timestamp: Date.now()
            });

            return isCalendar;
        },

        /**
         * Initialize performance monitoring
         */
        init: function()
        {
            // Clear expired cache periodically
            setInterval(() => {
                this.clearExpiredCache();
            }, this._cacheTimeout);
        }
    };

    // Initialize performance utilities
    window.TikiPerformanceUtils.init();

    // Export for use in other modules
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = window.TikiPerformanceUtils;
    }
})();
