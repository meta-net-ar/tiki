(function () {

    const cacheName = 'pages-cache-v1';

    const offlineFeedback = {message: "Tiki will try to handle your requests to send them later.", type: "warning", title: "Tiki is offline"};
    const onlineFeedback = {message: "You may need to sync your data now.", type: "success", title: "Tiki is back online"};
    const syncButton = {enable: false, disable: true}; // disable by default
    let requestCount = 0; // count cached requests

    $.loadCache = function (pages) {
        let urls = pages["wiki"].map(function (page) {
            return 'tiki-index.php?page=' + encodeURI(page);
        });
        urls = urls.concat(pages["urls"]);
        urls = urls.concat(
            pages["trackers"].map(function (page) {
                return 'tiki-view_tracker_item.php?itemId=' + page.itemId;
            })
        );
        urls = urls.concat(
            pages["trackers"].map(function (tracker) {
                return 'tiki-ajax_services.php?controller=tracker&action=update_item&trackerId=' + tracker.id + '&itemId=' + tracker.itemId;
            })
        );
        caches.open(cacheName)
            .then(cache => urls.map(url => cache.match(url).then(z => (!z) ? cache.add(url) : false).catch(x => console.error(x)))); // eslint-disable-line no-console

    };
    /**
     * Send custom feedback in pwa mode
     *
     * @param {string} message - Feedback message
     * @param {string} type - Type of alert: error, warning, success or info (default)
     * @param {string} title - Feedback title
     *
     */
    $.pwaFeedback = function (message, type, title) {
        feedback(tr(message), type, false, tr(title), "", true);
    };
    /**
     * Enabling or disabling sync button
     * @param {boolean} status - true or false
     */
    $.syncButtonControl = function (status) {
        $("#sync-pwa").prop('disabled', status);
    };

    if (!navigator.serviceWorker) {
        console.warn("Service Worker Unavailable"); // eslint-disable-line no-console
        return;
    }
    navigator.serviceWorker.register('./sw.js').then(() => {
        const db = new Dexie("post_cache");

        db.open().then(function() {
            $.updatePWACount(db);
        });

        $("#sync-pwa").on("click touchstart", function (event) {
            if (! navigator.onLine) {
                $.pwaFeedback('You don\'t seem to be online yet.', 'error', 'No connection');
                return;
            }
            const callsArray = [];
            db.table('post_cache').each(function ({key, request}) {
                callsArray.push(new Promise(function (deferrer, reject) {
                        if (request) {
                            $.ajax({
                                async: false,
                                type: request.method,
                                url: request.url,
                                headers: {...request.headers, pwa: true},
                                data: request.body,
                                success: function (ret) {
                                },
                                error: function (ret) {
                                }
                            });
                            deferrer(key);
                        }
                    })
                );
            }).then(function () {
                return Promise.all(callsArray).then(function (keys) {
                    const att = [];
                    keys.forEach(function (k) {
                        att.push(db.table('post_cache').where('key').equals(k).delete().then(() => {
                            $.updatePWACount(db);
                        }));
                    });
                    return Promise.all(att);

                });
            }).then(function () {
                return db.table('trackers').toArray();
            }).then(function(trackers) {
                let data = [];
                trackers.forEach(function(tracker) {
                    data.push({
                        trackerId: tracker.trackerId,
                        items: tracker.items
                    });
                });
                if (data.length > 0) {
                    $.ajax({
                        url: $.serviceUrl({controller: 'tracker_offline', action: 'sync'}),
                        type: 'POST',
                        data: {
                            data: JSON.stringify(data)
                        },
                        success: function (ret) {
                            if (ret.errors.length > 0) {
                                let msg = 'There was an error storing one or more tracker items.';
                                ret.errors.forEach(function (e) {
                                    msg += "<br>\n" + e;
                                });
                                msg += "<br>\nPlease reload the page to see remaining items that still need to be synchronized.";
                                $.pwaFeedback(msg, 'error', 'Error');
                            }
                            let all = [];
                            if (ret.success) {
                                ret.success.forEach(function (row) {
                                    trackers.forEach(function(tracker) {
                                        if (tracker.trackerId == row.trackerId) {
                                            tracker.items = tracker.items.filter(function(item, index) {
                                                return row.items.indexOf(index) == -1;
                                            });
                                            all.push(db.table('trackers').put(tracker));
                                        }
                                    });
                                });
                            }
                            Promise.all(all).then(function() {
                                if (ret.errors.length == 0) {
                                    $.pwaFeedback('Offline tracker data successfully saved.', 'success', 'Success');
                                    location.reload();
                                } else {
                                    $.syncButtonControl(syncButton.enable);
                                    this.textContent = "Sync";
                                }
                            });
                        }
                    });
                } else {
                    location.reload();
                }
            });
            event.preventDefault();
            $.syncButtonControl(syncButton.disable);
            this.textContent = "Syncing...";
        });
        self.addEventListener('load', () => {
            function handleOnlineStatusChange() {
                requestCount = $("#pwa-n-requests").text();
                if (navigator.onLine) {
                    console.warn(onlineFeedback.title); // eslint-disable-line no-console
                    $.pwaFeedback(onlineFeedback.message, onlineFeedback.type, onlineFeedback.title);
                    if (requestCount > 0) {// even online, enable it only if there are data to sync
                        $.syncButtonControl(syncButton.enable);
                    }
                } else {
                    console.warn(offlineFeedback.title); // eslint-disable-line no-console
                    $.pwaFeedback(offlineFeedback.message, offlineFeedback.type, offlineFeedback.title);
                    $.syncButtonControl(syncButton.disable);
                }
            }
            self.addEventListener('online', handleOnlineStatusChange);
            self.addEventListener('offline', handleOnlineStatusChange);
        });

    }).catch((err) => {
        // eslint-disable-next-line no-console
        console.log('registration failed', err);
    });

    $.updatePWACount = async function (db) {//update pwa requests count and check that need to show the warning message
        $.syncButtonControl(syncButton.enable);

        let n = await db.table('post_cache').count();
        let trackers = await db.table('trackers').toArray();
        trackers.forEach(function(tracker) {
            n += tracker.items.length;
        });
        $("#pwa-n-requests").text(n);
        if (n == 0) {//disable Sync button when no request in cache
            $.syncButtonControl(syncButton.disable);
        }

        db.table('messages').get({name: "show-warning"}, function (row) {
            if (row && row.value == true) {
                $.pwaFeedback(offlineFeedback.message, offlineFeedback.type, offlineFeedback.title);
                console.warn(offlineFeedback.title); // eslint-disable-line no-console
                $.syncButtonControl(syncButton.disable);
                db.table('messages').where("name").aboveOrEqual("show-warning").modify({value: false}).then(function () {
                });
            }
        });
    };
})();
