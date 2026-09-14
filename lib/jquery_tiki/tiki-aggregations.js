/**
 * Tiki Unified Reporting - client-side glue.
 *
 * Server side (PluginList + the {group}/{metric}/{join} aggregation DSL)
 * computes the bucket tree and emits two raw JSON payloads in the DOM:
 *
 *   <div class="tiki-agg-render"
 *        data-tiki-agg-render="chartjs">
 *     <script type="application/json" class="tiki-agg-spec">
 *       { ...the user's full chart-library options blob... }
 *     </script>
 *     <script type="application/json" class="tiki-agg-data">
 *       { ...raw aggregation tree as emitted by Smarty's |json_encode... }
 *     </script>
 *     <canvas></canvas>
 *   </div>
 *
 * On DOMContentLoaded each container is dispatched to the adapter named
 * in data-tiki-agg-render. Adapters are responsible for converting the
 * aggregation data into whatever shape their library wants and for
 * passing the user's spec through verbatim.
 *
 * Adding a new chart library = registering one adapter (no template /
 * Smarty / PHP changes):
 *
 *   tikiAggregations.registerAdapter('plotly', function (data, spec, container) { ... });
 */
(function (global) {
    'use strict';

    var adapters = {};

    function pick(bucket, path) {
        if (!bucket) { return null; }
        if (path === 'key') { return bucket.key; }
        if (path === 'doc_count') { return bucket.doc_count; }
        return bucket.metrics ? bucket.metrics[path] : null;
    }

    function filterBuckets(buckets, exclude) {
        if (!exclude || exclude === 'all') { return buckets.slice(); }
        return buckets.filter(function (b) {
            var kind = (b.metrics && b.metrics.__bucket_kind__) || 'bucket-normal';
            if (exclude === 'normal')    { return kind === 'bucket-normal'; }
            if (exclude === 'no-others') { return kind !== 'bucket-others'; }
            if (exclude === 'no-total')  { return kind !== 'bucket-total'; }
            return true;
        });
    }

    function readJsonScript(container, cls) {
        var node = container.querySelector('script.' + cls);
        if (!node) { return null; }
        try { return JSON.parse(node.textContent); }
        catch (e) {
            var msg = tr('Could not read aggregation chart data (%0).').replace('%0', cls);
            if (e && e.message) {
                msg += ' ' + e.message;
            }
            feedback(msg, 'error');
            return null;
        }
    }

    var tikiAggregations = {
        registerAdapter: function (name, fn) { adapters[name] = fn; },
        getAdapter: function (name) { return adapters[name]; },
        pick: pick,
        filterBuckets: filterBuckets,

        renderContainer: function (container) {
            if (!container || container.dataset.tikiAggRendered === '1') { return; }
            var rendererName = container.dataset.tikiAggRender || 'chartjs';
            var adapter = adapters[rendererName];
            if (!adapter) {
                feedback(
                    tr('Unknown aggregation chart renderer "%0".').replace('%0', rendererName),
                    'warning'
                );
                return;
            }
            var spec = readJsonScript(container, 'tiki-agg-spec') || {};
            var data = readJsonScript(container, 'tiki-agg-data') || {};
            container.dataset.tikiAggRendered = '1';
            adapter(data, spec, container);
        },

        boot: function (root) {
            var scope = root || document;
            var containers = scope.querySelectorAll('.tiki-agg-render');
            for (var i = 0; i < containers.length; i++) {
                tikiAggregations.renderContainer(containers[i]);
            }
        },
    };

    /* ----------------------------------------------------------------
     * Built-in adapter: Chart.js
     *
     * Folds the bucket data into spec.data.labels and
     * spec.data.datasets[].data, then hands the spec to `new Chart()`.
     * Every other Chart.js option (type, scales, plugins, animation,
     * tooltip callbacks, mixed-chart per-dataset overrides, ...) is
     * passed through verbatim from the user-authored spec.
     *
     * DSL extensions on the spec (consumed and stripped before Chart.js
     * sees them):
     *
     *   labels   bucket field path for x-axis / pie labels (default: "key")
     *   exclude  bucket filter (all / normal / no-others / no-total)
     *   datasets[].metric  bucket metric name read into that dataset's data
     * ---------------------------------------------------------------- */
    tikiAggregations.registerAdapter('chartjs', function (data, spec, container) {
        var allBuckets = (data && data.buckets) || [];
        var buckets = filterBuckets(allBuckets, spec.exclude);
        var metricLabels = (data && data.metric_labels) || {};

        var labelPath = spec.labels || 'key';
        var labels = buckets.map(function (b) { return pick(b, labelPath); });

        var dsSpecs = Array.isArray(spec.datasets) ? spec.datasets : [];
        if (dsSpecs.length === 0 && buckets.length > 0) {
            dsSpecs = Object.keys(buckets[0].metrics || {})
                .filter(function (m) { return m !== '__bucket_kind__' && m !== '__bucket_color__'; })
                .map(function (m) { return { metric: m }; });
        }

        var datasets = dsSpecs.map(function (ds) {
            var metric = ds.metric || ds.name;
            var out = {};
            for (var k in ds) {
                if (k !== 'metric' && k !== 'name') { out[k] = ds[k]; }
            }
            if (out.label === undefined) { out.label = metricLabels[metric] || metric; }
            out.data = buckets.map(function (b) { return pick(b, metric); });
            return out;
        });

        // Per-bucket palette (set server-side by {group palette=...})
        // is auto-applied for single-dataset charts that did not set
        // their own backgroundColor; multi-dataset charts keep their
        // user-authored colors.
        var bucketColors = buckets.map(function (b) { return pick(b, '__bucket_color__'); });
        if (datasets.length === 1
            && bucketColors.some(function (c) { return c; })
            && datasets[0].backgroundColor === undefined) {
            datasets[0].backgroundColor = bucketColors;
        }

        var canvas = container.querySelector('canvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            container.appendChild(canvas);
        }

        // Build the final Chart.js config: take the user's spec, strip
        // our DSL extensions, splice in the computed data.
        var config = {};
        for (var key in spec) {
            if (key !== 'labels' && key !== 'exclude' && key !== 'datasets') {
                config[key] = spec[key];
            }
        }
        if (!config.type) { config.type = 'bar'; }
        config.data = Object.assign({}, spec.data || {}, { labels: labels, datasets: datasets });

        function draw() {
            if (typeof Chart === 'undefined') { return setTimeout(draw, 50); }
            new Chart(canvas, config);
        }
        draw();
    });

    global.tikiAggregations = tikiAggregations;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { tikiAggregations.boot(); });
    } else {
        tikiAggregations.boot();
    }
})(window);
