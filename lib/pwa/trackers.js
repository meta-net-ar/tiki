(function () {
    const db = new Dexie("post_cache");
    let cache_tracker_data = function() {
        $.ajax({
            url: $.serviceUrl({controller: 'tracker_offline', action: 'cache'}),
            success: function (ret) {
                let availableTrackers = ret.trackers.map((t) => t.trackerId);
                let results = [];
                for (let info of ret.trackers) {
                    let fields = info.fields;
                    delete info.fields;
                    let entry = {
                        trackerId: info.trackerId,
                        name: info.name,
                        options: info,
                        fields: fields,
                        items: [],
                        existing_items: info.existing_items || []
                    };
                    results.push(db.table('trackers').put(entry));
                }
                results.push(db.table('trackers').where('trackerId').noneOf(availableTrackers).delete());
                for (let key of Object.keys(ret.user_prefs)) {
                    results.push(db.table('user_prefs').put({
                        key: key,
                        value: ret.user_prefs[key]
                    }));
                }
                Promise.all(results).then(function() {
                    initialize_vue_components();
                });
            },
            error: function (ret) {
            }
        });
    };

    let initialize_vue_components = async function() {
        let trackerData = await db.table('trackers').toArray();
        let userPrefs = {};
        await db.table('user_prefs').each(function(item) {
            userPrefs[item.key] = item.value;
        });
        // TODO: that's too much to load all the items from all post_cache, we will later do a preload of certain items only
        // for now, we only allow adding new items and editing them in offline mode until we sync
        // let trackerItems = [];
        // await getTrackerOfflineItems().then(function (data) {
        //     trackerItems = data;
        // });
        window.registerApplication({
            name: "@vue-mf/tiki-offline",
            app: () => importShim("@vue-mf/tiki-offline"),
            activeWhen: (location) => {
                let condition = true;
                return condition;
            },
            customProps: {
                trackerData: trackerData,
                userPrefs: userPrefs,
                dataSync: dataSync
            },
        });
        onDOMElementRemoved("single-spa-application:@vue-mf/tiki-offline", function () {
            window.unregisterApplication("single-spa-application:@vue-mf/tiki-offline");
        });
    };

    let getTrackerOfflineItems = async function () {
        // Filter requests from dexie?
        let requests = [];
        await db.table("post_cache").toArray(function (data){
            requests.push(data);
        });
        let trackerItems = [];
        requests.forEach((items) => {
            // Only load the requests for tracker items, skipping those for wikikipages and other
            items.forEach((item) => {
                let url = item.request.url, body = item.request.body;
                if (url.includes('tiki-ajax_services') && body && !body.includes('editor_id=editwiki'))
                {
                    let trackerItem = parseItemObject(item.request.body);
                    trackerItems.push(trackerItem);
                }
            });
        });
        return trackerItems;
    };

    let parseItemObject = function (itemObject) {
        const params = {};
        itemObject.split('&').forEach(function(pair) {
            let keyValue = pair.split('=');
            params[keyValue[0]] = decodeURIComponent(keyValue[1] || '');
        });
        return params;
    };

    let dataSync = function(trackers) {
        let results = [];
        trackers.forEach(tracker => {
            results.push(db.table('trackers').put(JSON.parse(JSON.stringify(tracker))));
        });
        Promise.all(results).then(function() {
            $.updatePWACount(db);
            if (navigator.onLine) {
                $("#sync-pwa").trigger('click');
            }
        });
    };

    db.open().then(function() {
        if (navigator.onLine) {
            $.updatePWACount(db).then(function() {
                let cnt = $("#pwa-n-requests").text();
                if (parseInt(cnt) > 0) {
                    $.pwaFeedback('You seem to be online but have pending requests to be synchronized with the server. Please click the Sync button at the bottom right section of the page to store your data permanently.', 'warning', 'Important');
                    initialize_vue_components();
                } else {
                    cache_tracker_data();
                }
            });
        } else {
            initialize_vue_components();
        }
    });
})();
