(function (window) {
    /*
     * Tiki / Converse browser storage helper.
     *
     * Single place where localStorage, sessionStorage and IndexedDB access
     * is wrapped in try/catch. Browsers can throw on any of these calls
     * (private browsing modes, storage quota, disabled storage via policy),
     * and the chat UI must keep working even when persistence is
     * unavailable, so every method here fails soft: reads return the given
     * fallback, local/session writes/removes are best-effort and return whether they
     * succeeded.
     *
     * This exists so call sites in conversejs-tiki.js and
     * conversejs-tiki-presentation.js never write a raw try/catch around
     * storage access themselves - if one is ever missed, it throws instead
     * of silently degrading, which this module is meant to prevent.
     *
     * Attached to window (not an ES module export) because this file must
     * load identically whether Tiki includes it as a classic <script> tag
     * (xmpp_conversejs_always_load, see ConverseJS.php) or as a dynamically
     * imported module (registerJsDependencies() in the same file); it must
     * not contain import/export statements.
     */
    // Pass a storage name, never window.localStorage/sessionStorage: acquiring
    // those properties may itself throw and must happen inside the try/catch.
    var TikiConverseStorage = {
        get: function (storage, key) {
            try {
                storage = window[storage];
                return storage ? storage.getItem(key) : null;
            } catch (e) {
                return null;
            }
        },

        set: function (storage, key, value) {
            try {
                storage = window[storage];
                if (!storage) {
                    return false;
                }
                storage.setItem(key, value);
                return true;
            } catch (e) {
                return false;
            }
        },

        remove: function (storage, key) {
            try {
                storage = window[storage];
                if (!storage) return false;
                storage.removeItem(key);
                return true;
            } catch (e) { return false; }
        },

        parseJSON: function (raw, fallback) {
            if (!raw) {
                return fallback;
            }
            try {
                return JSON.parse(raw);
            } catch (e) {
                return fallback;
            }
        },

        getJSON: function (storage, key, fallback) {
            return this.parseJSON(this.get(storage, key), fallback);
        },

        // Cursor maps, activity maps and identities require objects, not merely
        // valid JSON: "null", arrays and scalar values cannot serve as maps.
        getObject: function (storage, key, fallback) {
            var value = this.getJSON(storage, key, fallback);
            return value !== null && typeof value === 'object' && !Array.isArray(value) ? value : fallback;
        },

        setJSON: function (storage, key, value) {
            try {
                return this.set(storage, key, JSON.stringify(value));
            } catch (e) {
                return false;
            }
        },

        // Removes every key in `storage` matching `pattern` (a RegExp).
        clearMatching: function (storage, pattern) {
            try {
                storage = window[storage];
                if (!storage) return false;
                var keys = [];
                for (var i = 0; i < storage.length; i++) {
                    var key = storage.key(i);
                    pattern.lastIndex = 0;
                    if (key && pattern.test(key)) {
                        keys.push(key);
                    }
                }
                keys.forEach(function (key) {
                    storage.removeItem(key);
                });
                return true;
            } catch (e) { return false; }
        },

        // Await deletion requests before reloading. Other tabs may keep databases
        // open, so bound the wait and report incomplete best-effort cleanup.
        clearIndexedDbMatching: function (pattern) {
            var cleanup = Promise.resolve().then(function () {
                if (!window.indexedDB || !window.indexedDB.databases) return false;
                return window.indexedDB.databases().then(function (dbs) {
                    return Promise.all(dbs.filter(function (db) {
                        pattern.lastIndex = 0;
                        return db.name && pattern.test(db.name);
                    }).map(function (db) {
                        return new Promise(function (resolve) {
                            var request = window.indexedDB.deleteDatabase(db.name);
                            request.onsuccess = function () { resolve(true); };
                            request.onerror = function () { resolve(false); };
                        });
                    })).then(function (results) {
                        return results.every(function (success) { return success; });
                    });
                });
            }).catch(function () { return false; });
            return new Promise(function (resolve) {
                var timer = window.setTimeout(function () { resolve(false); }, 3000);
                cleanup.then(function (success) {
                    window.clearTimeout(timer);
                    resolve(success);
                });
            });
        },
    };

    window.TikiConverseStorage = TikiConverseStorage;
})(window);
