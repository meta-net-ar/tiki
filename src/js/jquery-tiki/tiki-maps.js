/**
 * Tiki OpenLayers Map Integration
 */

import { defaults as defaultControls } from "ol/control";

(function () {
    const defaultVectorColor = "#3399ff";

    let mapNumber = 0,
        currentProtocol = document.location.protocol,
        errorLayers = [];

    if (currentProtocol !== "http:" && currentProtocol !== "https:") {
        currentProtocol = "https:";
    }

    function getBaseLayers(tiles) {
        const layers = [],
            factories = {
                openstreetmap: function () {
                    return new ol.layer.Tile({
                        source: new ol.source.OSM(),
                        title: "OpenStreetMap",
                    });
                },
                bing_road: function () {
                    return new ol.layer.Tile({
                        source: new ol.source.BingMaps({
                            key: jqueryTiki.bingMapsAPIKey,
                            imagerySet: "Road",
                        }),
                        title: "BingRoad",
                    });
                },
                bing_road_on_demand: function () {
                    return new ol.layer.Tile({
                        source: new ol.source.BingMaps({
                            key: jqueryTiki.bingMapsAPIKey,
                            imagerySet: "RoadOnDemand",
                        }),
                        title: "BingRoadOnDemand",
                    });
                },
                bing_aerial: function () {
                    return new ol.layer.Tile({
                        source: new ol.source.BingMaps({
                            key: jqueryTiki.bingMapsAPIKey,
                            imagerySet: "Aerial",
                        }),
                        title: "BingAerial",
                    });
                },
                bing_aerial_with_labels: function () {
                    return new ol.layer.Tile({
                        source: new ol.source.BingMaps({
                            key: jqueryTiki.bingMapsAPIKey,
                            imagerySet: "AerialWithLabels",
                        }),
                        title: "BingAerialWithLabels",
                    });
                },
                bing_ordnance_survey: function () {
                    return new ol.layer.Tile({
                        source: new ol.source.BingMaps({
                            key: jqueryTiki.bingMapsAPIKey,
                            imagerySet: "ordnanceSurvey",
                        }),
                        title: "BingOrdnanceSurvey",
                    });
                },
                bing_collins_bart: function () {
                    // Doesn't seem to work?
                    return new ol.layer.Tile({
                        source: new ol.source.BingMaps({
                            key: jqueryTiki.bingMapsAPIKey,
                            imagerySet: "collinsBart",
                        }),
                        title: "BingCollinsBart",
                    });
                },
                nextzen: function () {
                    const nextzenRoadStyleCache = {},
                        nextzenStyle = function (feature, resolution) {
                            switch (feature.get("layer")) {
                                case "water":
                                    return new ol.style.Style({
                                        fill: new ol.style.Fill({
                                            color: "#9db9e8",
                                        }),
                                    });
                                case "buildings":
                                    return resolution < 10
                                        ? new ol.style.Style({
                                              fill: new ol.style.Fill({
                                                  color: "#bbb",
                                                  opacity: 0.4,
                                              }),
                                              stroke: new ol.style.Stroke({
                                                  color: "#999",
                                                  width: 1,
                                              }),
                                          })
                                        : null;
                                case "roads": {
                                    const kind = feature.get("kind"),
                                        railway = feature.get("railway"),
                                        sort_key = feature.get("sort_key"),
                                        styleKey = `${kind}/${railway}/${sort_key}`;

                                    let style = nextzenRoadStyleCache[styleKey];
                                    if (!style) {
                                        let color;
                                        let width = 1;
                                        if (railway || kind === "rail") {
                                            color = "#7a5";
                                        } else if (kind === "major_road") {
                                            color = "#aaa";
                                        } else if (kind === "minor_road") {
                                            color = "#ccb";
                                        } else if (kind === "path") {
                                            color = "#ddd";
                                        } else if (kind === "highway") {
                                            color = "#f39";
                                            width = 2;
                                        } else if (kind === "ferry") {
                                            color = "#448cff";
                                            width = 2;
                                        } else if (kind === "aeroway") {
                                            color = "#999";
                                            width = 3;
                                        } else {
                                            color = "#aaa";
                                            // eslint-disable-next-line no-console
                                            console.log("Unknown road kind: " + kind);
                                        }
                                        style = new ol.style.Style({
                                            stroke: new ol.style.Stroke({
                                                color: color,
                                                width: width,
                                            }),
                                            zIndex: sort_key,
                                        });
                                        nextzenRoadStyleCache[styleKey] = style;
                                    }
                                    return style;
                                }
                                default:
                                    return null;
                            }
                        };

                    return new ol.layer.VectorTile({
                        source: new ol.source.VectorTile({
                            attributions: "&copy; OpenStreetMap contributors, Who’s On First, " + "Natural Earth, and openstreetmapdata.com",
                            format: new ol.format.MVT({
                                layertitle: "layer",
                                layers: ["water", "roads", "buildings"],
                            }),
                            maxZoom: 19,
                            url: "https://tile.nextzen.org/tilezen/vector/v1/all/{z}/{x}/{y}.mvt?api_key=" + jqueryTiki.nextzenAPIKey,
                        }),
                        style: function (feature, resolution) {
                            return typeof window.nextzenStyle === "function"
                                ? window.nextzenStyle(feature, resolution)
                                : nextzenStyle(feature, resolution);
                        },
                    });
                },
                blank: function () {
                    // Fake layer to hide all tiles
                    const layer = new ol.layer.Layer({ render(frameState) {} });
                    layer.set("name", tr("Blank"));
                    return layer;
                },
            };

        if (tiles.length === 0) {
            tiles.push("openstreetmap");
        }

        let visible = true;

        $.each(tiles, function (k, name) {
            const getBaseLayer = function (name) {
                const f = factories[name];
                let layer;

                try {
                    if (f) {
                        layer = f();
                    } else if (name.match(/stamen_/)) {
                        const flavor = name.substring(7);

                        layer = new ol.layer.Tile({
                            source: new ol.source.StadiaMaps({
                                layer: `stamen_${flavor.replace("-", "_")}`,
                            }),
                            title: "Stamen" + flavor,
                        });
                    } else if (name.match(/stadia_/)) {
                        const flavor = name.substring(7);

                        layer = new ol.layer.Tile({
                            source: new ol.source.StadiaMaps({
                                layer: `${flavor.replace(/-/g, "_")}`,
                                retina: true,
                            }),
                            title: "Stadia" + flavor,
                        });
                    } else if (name.match(/^http.*\.json.*/)) {
                        layer = new ol.layer.Tile({
                            source: new ol.source.TileJSON({
                                url: name,
                                tileSize: 512,
                                crossOrigin: "anonymous",
                            }),
                            title: name.replace(/^.*?\/(.*?)\//, "$1") + " json",
                        });
                    } else if (name.match(/^http.*\{z}\/\{x}\/\{y}.*/)) {
                        layer = new ol.layer.Tile({
                            source: new ol.source.ImageTile({
                                url: name,
                            }),
                            title: name.replace(/^.*?\/(.*?)\//, "$1") + " xyz",
                        });
                    }
                } catch (e) {
                    feedback(tr(`Layer "${name}" could not be created` + "\n" + e.message), "error", false, tr("Layer creation failed"));
                }
                return layer;
            };

            if (typeof name === "object") {
                let sublayers = [],
                    names = [];

                for (let i = 0; i < name.length; i++) {
                    sublayers.push(getBaseLayer(name[i]));
                    names.push(name[i]);
                }
                layers.push(
                    new ol.layer.Group({
                        title: k,
                        combine: true,
                        visible: visible,
                        type: "base",
                        layers: sublayers,
                    })
                );
            } else {
                const lyr = getBaseLayer(name);
                if (lyr) {
                    lyr.set("visible", visible);
                    lyr.set("type", "base");
                    lyr.set("title", k);
                    layers.push(lyr);
                } else {
                    errorLayers.push(name);
                    // eslint-disable-next-line no-console
                    console.log(tr("Cannot create map layer: " + name));
                }
            }
            visible = false;
        });

        return layers;
    }

    function parseCoordinates(value) {
        const matching = value.match(/^(-?[0-9]*(\.[0-9]+)?),(-?[0-9]*(\.[0-9]+)?)(,(.*))?$/);

        if (matching) {
            const lat = parseFloat(matching[3]),
                lon = parseFloat(matching[1]),
                zoom = matching[6] ? parseInt(matching[6], 10) : 0;

            return { lat: lat, lon: lon, zoom: zoom };
        }

        return null;
    }

    function writeCoordinates(lonlat, map, fixProjection) {
        let coordinates = typeof lonlat.length === "number" ? lonlat : lonlat.getCoordinates();
        const original = coordinates;

        if (fixProjection) {
            lonlat = ol.proj.transform(
                coordinates,
                map.getView().getProjection(), // source — replaces map.getProjectionObject()
                "EPSG:4326" // target
            );

            if (!lonlat) {
                coordinates = original;
            } else {
                coordinates = lonlat;
            }
        }

        return formatLocation(coordinates[1], coordinates[0], parseInt(map.getView().getZoom()));
    }

    function formatLocation(lat, lon, zoom) {
        // Convert commas to decimal points - where those are used
        let strLon = "" + lon;
        strLon = strLon.replace(",", ".");
        let strLat = "" + lat;
        strLat = strLat.replace(",", ".");
        return strLon + "," + strLat + "," + zoom;
    }

    $.fn.createMap = function () {
        this.each(function () {
            let id = $(this).attr("id"),
                container = this,
                desiredControls;
            let selectionInteraction,
                cluster = $(container).data("cluster");

            if (cluster === "") {
                // default cluster size, probably should be a pref?
                cluster = 25;
            }

            // Shared style functions for all map features (accessible from both clustered and non-clustered layers)
            // Get feature color key from coordinates for stable lookup
            const getFeatureColorKey = (feature) => {
                const geom = feature.getGeometry();
                if (geom && geom.getCoordinates) {
                    const coords = geom.getCoordinates();
                    if (Array.isArray(coords)) {
                        return coords.join(",");
                    }
                }
                return null;
            };

            const getFeatureColor = (feature) => {
                // Try feature property first
                const color = feature.get("color");
                if (color) {
                    return color;
                }
                // Try coords lookup from container-level storage (set by colorpicker)
                const key = getFeatureColorKey(feature);
                if (key && container.featureColors && container.featureColors[key]) {
                    return container.featureColors[key];
                }
                return defaultVectorColor;
            };

            const getFeatureStyle = (feature, color, fillColor) => {
                let coloredStyle;

                if (feature.get("features") && feature.get("features").length) {
                    feature = feature.get("features")[0];
                }

                if (!color) {
                    color = getFeatureColor(feature);
                }

                if (!fillColor) {
                    fillColor = feature.get("fillColor");
                }

                if (!fillColor) {
                    // no fillColor provided, use semi-transparent color
                    const rgba = Array.from(ol.color.asArray(color));
                    fillColor = ol.color.asString([rgba[0], rgba[1], rgba[2], rgba[3] / 2]);
                }

                const intent = feature.get("intent");

                if (intent === "vectors") {
                    coloredStyle = new ol.style.Style({
                        fill: new ol.style.Fill({ color: fillColor }),
                        stroke: new ol.style.Stroke({ color: color, width: 2 }),
                    });
                } else if (intent === "marker") {
                    coloredStyle = new ol.style.Style({
                        image: new ol.style.Icon({
                            anchor: [0.5, 0.8],
                            src: feature.get("url"),
                        }),
                    });
                } else {
                    // Create a colored circle style
                    coloredStyle = new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 12,
                            fill: new ol.style.Fill({ color: fillColor }),
                            stroke: new ol.style.Stroke({ color: color, width: 2 }),
                        }),
                    });
                }
                // Don't set the style on the feature but let the layer handle it
                return coloredStyle;
            };

            const getFeatureSelectStyle = (feature, color, fillColor) => {
                let coloredStyle;

                if (feature.get("features")) {
                    feature = feature.get("features")[0];
                }

                if (!color) {
                    color = getFeatureColor(feature);
                }

                if (!fillColor) {
                    fillColor = feature.get("fillColor");
                }

                if (!fillColor) {
                    // no fillColor provided, use semi-transparent color
                    const rgba = Array.from(ol.color.asArray(color));
                    fillColor = ol.color.asString([rgba[0], rgba[1], rgba[2], rgba[3] / 4]);
                }

                const intent = feature.get("intent");

                if (intent === "vectors") {
                    coloredStyle = new ol.style.Style({
                        fill: new ol.style.Fill({ color: fillColor }),
                        stroke: new ol.style.Stroke({ color: color, width: 4 }),
                    });
                } else if (intent === "marker") {
                    coloredStyle = new ol.style.Style({
                        image: new ol.style.Icon({
                            anchor: [0.5, 0.8],
                            src: feature.get("url"),
                            scale: 1.5,
                        }),
                    });
                } else {
                    // Create a colored circle style
                    coloredStyle = new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 12,
                            fill: new ol.style.Fill({ color: fillColor }),
                            stroke: new ol.style.Stroke({ color: color, width: 2 }),
                        }),
                    });
                }
                // Don't set the style on the feature but let the layer handle it
                return coloredStyle;
            };

            container.getFeatureSelectStyle = getFeatureSelectStyle;

            $(container).css("background", "white");

            container.setupLayerEvents = function (vectors) {
                if (!selectionInteraction) {
                    selectionInteraction = new ol.interaction.Select({
                        condition: ol.events.condition.singleClick,
                        //style: null,
                    });

                    map.addInteraction(selectionInteraction);

                    selectionInteraction.on("select", function (event) {
                        if (event.deselected.length) {
                            event.deselected.forEach((feature) => {
                                if (feature.executor) {
                                    feature.executor();
                                }
                            });
                        }
                        if (event.selected.length && container.showPopup) {
                            event.selected.forEach(() => {
                                container.showPopup.apply(null, [event]);
                            });
                        }
                    });
                }

                const interactions = container.map.getInteractions().getArray();

                // e.g. added by wikiplugin_appframe modify_feature
                const modifyInteraction = interactions.find((interaction) => {
                    return interaction instanceof ol.interaction.Modify && interaction.get("active");
                });

                if (modifyInteraction) {
                    modifyInteraction.set("style", (feature) => getFeatureSelectStyle(feature));
                    modifyInteraction.on("modifyend", function (event) {
                        let featuresArray = event.features.item(0).get("features") ?? event.features.getArray();
                        featuresArray.forEach((feature) => {
                            if (feature.executor) {
                                feature.executor();
                            }
                        });
                    });
                }

                // e.g. added by wikiplugin_appframe draw_polygon and draw_path
                const drawInteraction = interactions.find((interaction) => {
                    return interaction instanceof ol.interaction.Draw && interaction.get("active");
                });

                if (drawInteraction) {
                    drawInteraction.on("drawend", function (event) {
                        if (event.feature) {
                            if (!event.feature.get("color")) {
                                event.feature.set("color", defaultVectorColor);
                            }
                            if (!event.feature.get("intent")) {
                                event.feature.set("intent", "vectors");
                            }
                            $(document).trigger("drawend.tiki", [event]);
                        }
                    });
                }
            };

            /**
             * detect if the feature in question is currently clustered
             * @param feature
             * @param layer
             * @returns {boolean}
             */
            container.isClustered = function (feature, layer) {
                let isClustered = false;
                if (cluster) {
                    const clusterGroups = layer.get("source").getFeatures();
                    isClustered = clusterGroups.some((clusterGroup) => {
                        const members = clusterGroup.get("features");
                        return members && members.length > 1 && members.includes(feature);
                    });
                }
                return isClustered;
            };

            const popupStyle = $(container).data("popup-style");
            /**
             * Get or create an ol layer and store it in container.layers[]
             *
             * @param name         if empty, use the "Editable" layer, container.vectors
             * @param shapeLayer   if true, use separate layer for shapes to enable clustering
             * @returns {*}
             */
            container.getLayer = function (name, shapeLayer) {
                let vectors;

                if (name) {
                    if (shapeLayer) {
                        // the js `tr` function doesn't accept arguments, so we can't change the word order here, sorry
                        name = name + " " + tr("Shapes");
                    }
                    if (!container.layers[name]) {
                        // basic feature clustering
                        if (cluster) {
                            // Shared source for both layers (shapes and points)
                            const sharedSource = new ol.source.Vector({ wrapX: false });
                            const styleCache = {};

                            if (!shapeLayer) {
                                const distance = cluster,
                                    clusterSource = new ol.source.Cluster({
                                        distance: distance,
                                        source: sharedSource,
                                        geometryFunction: (feature) => {
                                            if (feature.get("nocluster")) {
                                                return null;
                                            }
                                            const res = container.map.getView().getResolution();
                                            const geom = feature.getGeometry();
                                            if (!geom) return null;
                                            if (geom instanceof ol.geom.Polygon || geom instanceof ol.geom.MultiPolygon) {
                                                // Use the centroid of the polygon's extent
                                                return geom.getInteriorPoint();
                                            } else if (geom instanceof ol.geom.LineString || geom instanceof ol.geom.MultiLineString) {
                                                return new ol.geom.Point(ol.extent.getCenter(geom.getExtent()));
                                            } else {
                                                return geom;
                                            }
                                        },
                                    });

                                let clusterFillColor = $(container).data("clusterfillcolor"),
                                    clusterTextColor = $(container).data("clustertextcolor");

                                if (clusterFillColor) {
                                    clusterFillColor = clusterFillColor.split(",");
                                } else {
                                    clusterFillColor = [86, 134, 200];
                                }
                                if (clusterTextColor) {
                                    clusterTextColor = clusterTextColor.split(",");
                                } else {
                                    clusterTextColor = [255, 255, 255];
                                }
                                let maxFeatureCount;
                                /**
                                 * Calculate size of cluster depending on zoom extent
                                 * based on https://openlayers.org/en/latest/examples/earthquake-clusters.html
                                 * @param resolution
                                 */
                                const calculateClusterInfo = function (resolution) {
                                    maxFeatureCount = 0;
                                    const features = vectors.getSource().getFeatures();
                                    features.forEach((feature) => {
                                        const originalFeatures = feature.get("features"),
                                            extent = ol.extent.createEmpty();

                                        originalFeatures.forEach((f) => {
                                            const getRepresentativePoint = (feature) => {
                                                const geom = feature.getGeometry();
                                                switch (geom.getType()) {
                                                    case "Polygon":
                                                    case "MultiPolygon":
                                                        return geom.getInteriorPoint().getCoordinates();
                                                    case "LineString":
                                                    case "MultiLineString":
                                                        return geom.getCoordinateAt(0.5); // midpoint along the line
                                                    default:
                                                        return geom.getCoordinates(); // Point
                                                }
                                            };

                                            ol.extent.extendCoordinate(extent, getRepresentativePoint(f));
                                        });
                                        // for the style cache
                                        maxFeatureCount = Math.max(maxFeatureCount, originalFeatures.length);

                                        const widthPx = ol.extent.getWidth(extent) / resolution;
                                        const heightPx = ol.extent.getHeight(extent) / resolution;

                                        const radius = Math.sqrt(widthPx * widthPx + heightPx * heightPx) / 2;
                                        //const radius = (0.4 * (ol.extent.getWidth(extent) + ol.extent.getHeight(extent))) / resolution;
                                        feature.set("radius", radius);
                                    });
                                };

                                const invisibleFill = new ol.style.Fill({
                                    color: "rgba(255, 255, 255, 0.01)",
                                });
                                const selectClusterFeaturesStyle = function (feature) {
                                    const styles = [],
                                        features = feature.get("features");

                                    if (!features && feature) {
                                        return getFeatureStyle(feature);
                                    }

                                    if (features.length > 1) {
                                        styles.push(
                                            new ol.style.Style({
                                                image: new ol.style.Circle({
                                                    radius: feature.get("radius"),
                                                    fill: invisibleFill,
                                                }),
                                            })
                                        );
                                    }
                                    features.forEach((f) => styles.push(getFeatureStyle(f)));
                                    return styles;
                                };

                                vectors = container.layers[name] = new ol.layer.Vector({
                                    source: clusterSource,
                                    title: name,
                                    style: function (feature, resolution) {
                                        const features = feature.get("features");
                                        let style;
                                        if (features && features.length > 1) {
                                            calculateClusterInfo(resolution);

                                            const size = features.length;
                                            style = styleCache[size + " " + maxFeatureCount];

                                            if (!style) {
                                                style = new ol.style.Style({
                                                    image: new ol.style.Circle({
                                                        radius: Math.max(feature.get("radius"), 20),
                                                        stroke: new ol.style.Stroke({
                                                            color: clusterTextColor,
                                                        }),
                                                        fill: new ol.style.Fill({
                                                            color: [
                                                                clusterFillColor[0],
                                                                clusterFillColor[1],
                                                                clusterFillColor[2],
                                                                Math.min(0.8, 0.4 + size / maxFeatureCount),
                                                            ],
                                                        }),
                                                    }),
                                                    text: new ol.style.Text({
                                                        text: features.length.toString(),
                                                        font: "14px sans-serif",
                                                        fill: new ol.style.Fill({
                                                            color: clusterTextColor,
                                                        }),
                                                    }),
                                                });
                                                styleCache["c" + size + " " + maxFeatureCount] = style;
                                            }
                                        } else {
                                            // not a cluster group
                                            if (features && features[0]) {
                                                feature = features[0];
                                            }

                                            style = styleCache["f" + feature.ol_uid];

                                            if (!style) {
                                                // Always try to create a style - getFeatureStyle handles custom colors
                                                style = getFeatureStyle(feature);
                                                styleCache["f" + feature.ol_uid] = style;
                                            }
                                        }
                                        return style;
                                    },
                                });

                                // Store the shared source so the shapeLayer branch can find it
                                vectors._sharedSource = sharedSource;

                                if ($(container).data("clusterhover") === "features") {
                                    container.map.addInteraction(
                                        new ol.interaction.Select({
                                            condition: function (evt) {
                                                return evt.type === "pointermove" || evt.type === "singleclick";
                                            },
                                            style: selectClusterFeaturesStyle,
                                            layers: [vectors],
                                        })
                                    );
                                }

                                if (popupStyle) {
                                    const selectionInteraction = new ol.interaction.Select({
                                        style: (feature) => {
                                            return getFeatureStyle(feature);
                                        },
                                        layers: [vectors],
                                    });

                                    container.map.addInteraction(selectionInteraction);

                                    // use select to make popup
                                    selectionInteraction.on("select", container.showPopup);
                                }
                            } else {
                                // make a plain vector layer for Polygons and Lines

                                // Retrieve the shared source from the already-created cluster layer
                                const clusterLayerName = name.replace(" " + tr("Shapes"), ""),
                                    clusterLayer = container.layers[clusterLayerName],
                                    clusterSource = clusterLayer.getSource(),
                                    rawSource = clusterLayer._sharedSource; // ✅ the actual VectorSource with real geometries

                                vectors = container.layers[name] = new ol.layer.Vector({
                                    source: rawSource,
                                    title: name,
                                    style: function (feature) {
                                        if (feature.getGeometry() instanceof ol.geom.Point) {
                                            return null; // Points are handled by the cluster layer, not here
                                        }
                                        // Find if this feature is in a cluster of > 1
                                        if (container.isClustered(feature, clusterLayer)) {
                                            return null; // hide the polygon
                                        }

                                        let style = styleCache["f" + feature.ol_uid];
                                        if (style) {
                                            return style;
                                        } else {
                                            style = getFeatureStyle(feature);
                                            styleCache["f" + feature.ol_uid] = style;
                                            return style;
                                        }
                                    },
                                });
                            }
                        } else {
                            // not clustered
                            vectors = container.layers[name] = new ol.layer.Vector({
                                source: new ol.source.Vector({ wrapX: false }),
                                title: name,
                                style: (feature) => {
                                    return getFeatureStyle(feature);
                                },
                            });
                        }

                        vectors.setZIndex(container.map.getLayers().getLength() * 1000);
                        container.setupLayerEvents(vectors);

                        container.overlays.getLayers().push(vectors);
                    }

                    return container.layers[name];
                }

                if (shapeLayer && cluster) {
                    return container.vector_shapes;
                } else {
                    return container.vectors;
                }
            };

            container.clearLayer = function (name) {
                const vectors = container.getLayer(name).getSource();

                vectors.getFeatures().forEach((f) => {
                    if ((f && f.get("itemId")) || (f && f.get("type") && f.get("object"))) {
                        vectors.removeFeature(f);
                    }
                });
            };

            const setupHeight = function () {
                let height = $(container).height();
                if (0 === height) {
                    height = ($(container).width() / 4.0) * 3.0;
                }

                $(container)
                    .closest(".height-size")
                    .each(function () {
                        height = $(this).data("available-height");
                        $(this).css("padding", 0);
                        $(this).css("margin", 0);
                    });

                $(container).height(height);
            };
            setupHeight();

            $(window).on("resize", setupHeight);

            if (!id) {
                ++mapNumber;
                id = "openlayers" + mapNumber;
                $(this).attr("id", id);
            }

            //setTimeout(function () {
            ol.ImgPath = "lib/openlayers/theme/dark/";

            desiredControls = $(this).data("map-controls");
            if (desiredControls === undefined) {
                desiredControls = "controls,layers,search_location,current_location,streetview,navigation";
            }

            desiredControls = desiredControls.split(",");

            const controls = defaultControls({
                // zoom control is added by default, so remove it if not needed
                zoom: $.inArray("controls", desiredControls) !== -1,
            });

            if ($.inArray("coordinates", desiredControls) !== -1) {
                controls.push(
                    new ol.control.MousePosition({
                        projection: "EPSG:4326",
                        coordinateFormat: function (coordinate) {
                            return ol.coordinate.format(coordinate, "{y}, {x}", 4);
                        },
                    })
                );
            }

            if ($.inArray("scale", desiredControls) !== -1) {
                controls.push(new ol.control.ScaleLine());
            }

            if ($.inArray("levels", desiredControls) !== -1) {
                controls.push(new ol.control.ZoomSlider());
            }

            if (-1 !== $.inArray("layers", desiredControls)) {
                controls.push(new ol.control.LayerSwitcher());
            }

            /* no navbar, pan or layer switcher anymore?
                if (layers.length > 0 && -1 !== $.inArray("navigation", desiredControls)) {
                    defaultMode.controls.push(new ol.control.NavToolbar());
                }
*/

            // Set up initial layers
            container.layers = {};

            const tilesets = {};
            let key = "";
            let pos = -1;
            let ts = $(container).data("tilesets") || jqueryTiki.mapTileSets;
            if (ts) {
                if (typeof ts === "string") {
                    ts = ts.replace(/\s*/g, "").split(",");
                }
                if (typeof ts === "string") {
                    ts = [ts];
                }
            }
            // parse the geo_tilesets pref (or data-tilesets) which can be in the format Label=layer_!~layer_1 etc
            ts.forEach(function (tileSet, i) {
                pos = tileSet.indexOf("=");
                if (pos !== -1) {
                    key = tileSet.substr(0, pos);
                    tileSet = tileSet.substr(pos + 1);
                } else {
                    const m = tileSet.match(/\W/);
                    if (m) {
                        key = tileSet.substr(0, tileSet.indexOf(m[0]));
                    } else {
                        key = tileSet;
                    }
                }
                if (tileSet.indexOf("~") !== -1) {
                    tilesets[key] = tileSet.split("~");
                } else {
                    tilesets[key] = tileSet;
                }
            });

            errorLayers = [];

            const layers = [
                new ol.layer.Group({
                    title: tr("Base Maps"),
                    layers: getBaseLayers(tilesets),
                }),
                new ol.layer.Group({
                    title: tr("Overlays"),
                }),
            ];

            const map = (container.map = new ol.Map({
                target: id,
                controls: controls,
                view: new ol.View({
                    center: [0, 0],
                    zoom: 2,
                }),
                layers: layers,
            }));

            if (errorLayers.length) {
                $("#tikifeedback").showError(tr("Cannot create map layer/s: " + errorLayers.join(", ")));
                errorLayers = [];
            }

            map.getLayerGroup()
                .getLayers()
                .forEach(function (layer) {
                    if (layer.get("title") === tr("Overlays")) {
                        container.overlays = layer;
                    }
                });

            container.vectors = container.getLayer(tr("Editable"));
            if (cluster) {
                container.vector_shapes = container.getLayer(tr("Editable"), true);
            }

            container.uniqueMarkers = {};

            container.resetPosition = function (center) {
                center = center || [0, 0, 3];

                const view = map.getView();
                view.setCenter(ol.proj.fromLonLat([center.lon, center.lat]));
                view.setZoom(center.zoom);
            };

            container.resetPosition();

            container.modeManager = {
                modes: [],
                activeMode: null,
                addMode: function (options) {
                    const mode = $.extend(
                        {
                            name: tr("Default"),
                            icon: null,
                            events: {
                                activate: [],
                                deactivate: [],
                            },
                            controls: [],
                            layers: [],
                        },
                        options
                    );

                    mode.layers.forEach((layer) => {
                        layer.displayInLayerSwitcher = false;
                        layer.setVisibility(false);
                        map.addLayer(layer);
                    });

                    mode.controls.forEach((control) => {
                        control.setActive(false);
                        map.addInteraction(control);
                    });

                    this.modes.push(mode);

                    this.register("activate", mode.name, mode.activate);
                    this.register("deactivate", mode.name, mode.deactivate);

                    if (!this.activeMode) {
                        this.activate(mode);
                    }

                    $(container).trigger("modechanged");

                    return mode;
                },
                switchTo: function (modeName) {
                    const manager = this;
                    this.modes.forEach((mode) => {
                        if (mode.name === modeName) {
                            manager.activate(mode);
                        }
                    });
                },
                register: function (eventName, modeName, callback) {
                    this.modes.forEach(function (mode) {
                        if (mode.name === modeName && callback) {
                            mode.events[eventName].push(callback);
                        }
                    });
                },
                activate: function (mode) {
                    if (this.activeMode) {
                        this.deactivate();
                    }

                    this.activeMode = mode;
                    const interactions = container.map.getInteractions();

                    mode.controls.forEach((control) => {
                        interactions.forEach((interaction) => {
                            if (interaction instanceof ol.interaction.Select && interaction instanceof control.constructor) {
                                // edit modes need to disable the default select interaction
                                interaction.getFeatures().clear();
                                interaction.setActive(false);
                            }
                        });
                        control.setActive(true);
                        container.setupLayerEvents(container.vectors);
                    });
                    mode.layers.forEach((layer) => {
                        layer.setVisibility(true);
                    });
                    mode.events.activate.forEach(function (evt) {
                        evt.apply([], container);
                    });

                    $(container).trigger("modechanged");
                },
                deactivate: function () {
                    if (!this.activeMode) {
                        return;
                    }

                    this.activeMode.controls.forEach((control) => {
                        control.setActive(false);
                    });
                    this.activeMode.layers.forEach((layer) => {
                        layer.setVisibility(false);
                    });
                    this.activeMode.events.deactivate.forEach((evt) => {
                        evt.apply([], container);
                    });

                    this.activeMode = null;
                },
            };

            const defaultMode = {
                controls: [selectionInteraction],
                name: tr("Default"),
            };

            container.modeManager.addMode(defaultMode);

            /* tooltips */

            if ($(container).data("tooltips")) {
                // based on https://gis.stackexchange.com/a/166745/25953
                const $mapBootstrapTooltipDummy = $("<div>")
                    .attr("id", "map-tooltip")
                    .css("position", "absolute")
                    .appendTo($(".ol-viewport", container));

                container.displayFeatureInfo = function (pixel, evt) {
                    $mapBootstrapTooltipDummy.tooltip("hide");
                    let feature, layer;
                    const both = map.forEachFeatureAtPixel(pixel, function (feature, layer) {
                        return [feature, layer];
                    });
                    if (!both) {
                        return;
                    }
                    feature = both[0];
                    layer = both[1];

                    if (!feature) {
                        return;
                    }
                    if (layer && layer instanceof ol.layer.VectorTile) {
                        return;
                    }
                    $mapBootstrapTooltipDummy.css({
                        left: pixel[0] + "px",
                        top: pixel[1] - 15 + "px",
                    });
                    const clusterFeatures = feature.get("features");

                    if (clusterFeatures) {
                        if ($(container).data("clusterhover") === "none") {
                            feature.set("content", "");
                            for (let i = 0; i < clusterFeatures.length; i++) {
                                feature.set("content", feature.get("content") + clusterFeatures[i].get("content") + "<br>");
                            }
                        } else if (clusterFeatures.length === 1) {
                            feature = clusterFeatures[0];
                        } else if (layer) {
                            const f = layer.getSource().getSource().getClosestFeatureToCoordinate(evt.coordinate);
                            if (f) {
                                feature = f;
                            }
                        }
                    }
                    if (feature && feature.get("content")) {
                        $mapBootstrapTooltipDummy
                            .tooltip("dispose")
                            .tooltip({
                                animation: false,
                                trigger: "manual",
                                html: true,
                                title: feature.get("content"),
                                container: "body",
                            })
                            .tooltip("show");
                    }
                };

                map.on("pointermove", function (evt) {
                    if (evt.dragging) {
                        $mapBootstrapTooltipDummy.tooltip("hide");
                        return true;
                    }
                    container.displayFeatureInfo(map.getEventPixel(evt.originalEvent), evt);
                    return true;
                });
            }

            if (popupStyle) {
                // popover needs to use a different element from tooltips now
                const $mapBootstrapPopoverDummy =
                    popupStyle !== "dialog"
                        ? $("<div>").attr("id", "map-popover").css("position", "absolute").appendTo($(".ol-viewport", container))
                        : null;

                container.showPopup = function (e) {
                    let pixel, feature;
                    const features = e.target.getFeatures();

                    if (features.getLength()) {
                        feature = features.item(0);
                        const clusterFeatures = feature.get("features");
                        if (clusterFeatures) {
                            if (clusterFeatures.length === 1) {
                                feature = clusterFeatures[0];
                            } else {
                                selectionInteraction.getFeatures().clear();
                                const extent = ol.extent.createEmpty();
                                for (let i = 0; i < clusterFeatures.length; ++i) {
                                    ol.extent.extend(extent, clusterFeatures[i].getGeometry().getExtent());
                                }
                                container.map.getView().fit(extent, {
                                    duration: 2000,
                                    padding: [10, 5, 10, 5],
                                });
                                return;
                            }
                        } else {
                            feature = features.item(0);
                        }
                    } else {
                        return;
                    }

                    pixel = map.getEventPixel(e.mapBrowserEvent.originalEvent);

                    let type = feature.get("type"),
                        object = feature.get("object");

                    if (!type && !object) {
                        type = "trackeritem";
                        object = feature.get("itemId");
                    }

                    switch (popupStyle) {
                        case "dialog": {
                            const $modal = $(".footer-modal:not(.show)").first().addClass("modal-lg").modal({});

                            $modal
                                .one("show.bs.modal", function (event) {
                                    $(".modal-title", $modal).empty();
                                    $(".modal-body", $modal).empty();
                                })
                                .one("shown.bs.modal", function (event) {
                                    if (type && object) {
                                        $(container).loadInfoboxPopup({
                                            type: type,
                                            object: object,
                                            feature: feature,
                                            event: event,
                                            element: this,
                                            callback: function (event, $html) {
                                                const title = $html.find("h1").remove().text() || feature.get("content");

                                                $html.find("a.service-dialog").on("click", function (event) {
                                                    const $link = $(this);
                                                    $.closeModal({
                                                        done: function () {
                                                            setTimeout(function () {
                                                                $.openModal({
                                                                    size: "modal-lg",
                                                                    remote: $link.attr("href"),
                                                                });
                                                            }, 0);
                                                        },
                                                    });
                                                    return false;
                                                });

                                                $(".modal-title", $modal).text(title);
                                                $(".modal-body", $modal).append($html);
                                            },
                                        });
                                    }
                                })
                                .one("hidden.bs.modal", function () {
                                    selectionInteraction.getFeatures().clear();
                                })
                                .modal("show");

                            break;
                        }
                        case "popup":
                        default:
                            $mapBootstrapPopoverDummy
                                .tooltip("dispose")
                                .css({
                                    left: pixel[0] + "px",
                                    top: pixel[1] + "px",
                                })
                                .popover("dispose")
                                .popover({
                                    trigger: "manual",
                                    html: true,
                                    content: tr("Loading..."),
                                    container: "body",
                                    placement: "auto",
                                })
                                .popover("show")
                                .one("shown.bs.popover", function (event) {
                                    const $popover = $(".popover.show").first();
                                    let $h3 = $popover.find("h3").text("");
                                    if ($h3.length === 0) {
                                        $h3 = $("<h3>").addClass("popover-header");
                                        $popover.find(".popover-arrow").after($h3.text(""));
                                    }
                                    $popover.find(".popover-body").empty();

                                    if (type && object) {
                                        $(container).loadInfoboxPopup({
                                            type: type,
                                            object: object,
                                            feature: feature,
                                            event: event,
                                            element: this,
                                            callback: function (event, $html) {
                                                const title = $html.find("h1").remove().text() || feature.get("content");
                                                $("h3", $popover).text(title);
                                                $(".popover-body", $popover).empty().html($html.html());
                                            },
                                        });
                                    } else {
                                        const $el = $(feature.get("content"));
                                        // just a marker, info it in the link
                                        $popover.find("h3").text($el.text());
                                        $popover.find(".popover-body").empty().text($el.attr("title"));
                                    }
                                    let timeout = 0;
                                    $popover.on("mouseleave", function () {
                                        timeout = setTimeout(function () {
                                            $popover.find("h3").text("");
                                            $popover.find(".popover-body").empty();
                                            $mapBootstrapPopoverDummy.popover("dispose");
                                            selectionInteraction.getFeatures().clear();
                                        }, 1000);
                                    });
                                    $popover.on("mouseenter", function () {
                                        if (timeout) {
                                            clearTimeout(timeout);
                                            timeout = 0;
                                        }
                                    });
                                });
                            break;
                    }
                };
            }

            if (layers.length > 0 && -1 !== $.inArray("overview", desiredControls)) {
                const overview = new ol.control.OverviewMap({ minRatio: 128, maxRatio: 256, maximized: true });
                overview.desiredZoom = function () {
                    return Math.min(Math.max(1, map.getZoom() - 6), 3);
                };
                overview.isSuitableOverview = function () {
                    return this.ovmap.getZoom() === overview.desiredZoom() && this.ovmap.getExtent().contains(map.getExtent());
                };
                overview.updateOverview = function () {
                    overview.ovmap.setCenter(map.getCenter());
                    overview.ovmap.zoomTo(overview.desiredZoom());
                    this.updateRectToMap();
                };

                map.addControl(overview);
            }

            container.markerIcons = {
                loadedMarker: {},
                actionQueue: {},
                loadingMarker: [],
                loadMarker: function (name, src) {
                    this.loadingMarker.push(name);
                    this.actionQueue[name] = [];

                    const img = new Image(),
                        me = this;
                    img.onload = function () {
                        const width = this.width,
                            height = this.height;
                        let action;
                        me.loadedMarker[name] = {
                            intent: "marker",
                            url: src,
                            width: width,
                            height: height,
                            offsetx: width / 2,
                            offsety: height,
                        };

                        while ((action = me.actionQueue[name].pop())) {
                            action();
                        }
                    };
                    $(img).on("error", function () {
                        // eslint-disable-next-line no-console
                        console.log("Map error loading marker image " + src);
                        const index = container.markerIcons.loadingMarker.indexOf(src);
                        let action;
                        if (index > -1) {
                            container.markerIcons.loadingMarker.splice(index, 1);
                        }
                        while ((action = me.actionQueue[name].pop())) {
                            action();
                        }
                    });
                    img.src = src;
                },
                createMarker: function (name, lonlat, callback) {
                    if (this.loadedMarker[name]) {
                        this._createMarker(name, lonlat, callback);
                        return;
                    }

                    if (-1 === $.inArray(name, this.loadingMarker)) {
                        this.loadMarker(name, name);
                    }

                    const me = this;
                    this.actionQueue[name].push(function () {
                        me._createMarker(name, lonlat, callback);
                    });
                },
                _createMarker: function (name, lonlat, callback) {
                    if (lonlat) {
                        const properties = $.extend(this.loadedMarker[name] || this.loadedMarker.default, {
                            geometry: lonlat,
                        });

                        const marker = new ol.Feature(properties);
                        callback(marker);
                    }
                },
            };

            container.markerIcons.loadMarker("default", "lib/openlayers/img/marker.svg");
            container.markerIcons.loadMarker("selection", "lib/openlayers/img/marker-gold.svg");

            if (navigator.geolocation && navigator.geolocation.getCurrentPosition) {
                container.toMyLocation = $("<a class='btn btn-sm btn-info'>")
                    .attr("href", "")
                    .on("click", function () {
                        navigator.geolocation.getCurrentPosition(function (position) {
                            const view = map.getView();
                            view.setCenter(ol.proj.fromLonLat([position.coords.longitude, position.coords.latitude]));
                            view.setZoom(view.getZoomForResolution(position.coords.accuracy));

                            $(container).addMapMarker({
                                lat: position.coords.latitude,
                                lon: position.coords.longitude,
                                unique: "selection",
                            });
                        });
                        return false;
                    })
                    .text(tr("To My Location"));

                if (-1 !== $.inArray("current_location", desiredControls)) {
                    $(container).after(container.toMyLocation);
                }
            }

            container.searchLocation = $("<a class='btn btn-sm btn-info'>")
                .attr("href", "")
                .on("click", function () {
                    const address = prompt(tr("What address are you looking for?"), "");

                    $(container).trigger("search", [{ address: address }]);
                    return false;
                })
                .text(tr("Search Location"));

            if (-1 !== $.inArray("search_location", desiredControls)) {
                $(container).after(container.searchLocation);
            }

            let field = $(container).data("target-field"),
                central = null,
                useMarker = true;

            if (field) {
                field = $($(container).closest("form")[0][field]);

                $(container).setupMapSelection({
                    field: field,
                });
                const value = field.val();
                central = parseCoordinates(value);

                if (central) {
                    // cope with zoom levels greater than what OSM layer[0] can cope with
                    let geLayer;
                    if (central.zoom > 19) {
                        geLayer = map.getLayersByName("Google Satellite");
                    } else if (central.zoom > 18) {
                        geLayer = map.getLayersByName("Google Streets");
                    }
                    if (geLayer) {
                        container.layer = geLayer[0];
                        map.setBaseLayer(container.layer);
                        map.baseLayer.setVisibility(true);
                    }
                }
            }

            if ($(container).data("marker-filter")) {
                const filter = $(container).data("marker-filter");
                $(filter).each(function () {
                    const lat = $(this).data("geo-lat"),
                        lon = $(this).data("geo-lon"),
                        zoom = $(this).data("geo-zoom"),
                        extent = $(this).data("geo-extent"),
                        icon = $(this).data("icon-src"),
                        object = $(this).data("object"),
                        type = $(this).data("type"),
                        content = $(this).clone().data({}).wrap("<span/>").parent().html();

                    if (!extent) {
                        if ($(this).hasClass("primary") || this.href === document.location.href) {
                            central = { lat: lat, lon: lon, zoom: zoom ? zoom : 0 };
                        } else {
                            $(container).addMapMarker({
                                type: type,
                                object: object,
                                lon: lon,
                                lat: lat,
                                content: content,
                                icon: icon ? icon : null,
                            });
                        }
                    } else if ($(this).is("img")) {
                        const graphic = new ol.layer.Image(
                            $(this).attr("alt"),
                            $(this).attr("src"),
                            ol.Bounds.fromString(extent),
                            new ol.Size($(this).width(), $(this).height())
                        );

                        graphic.isBaseLayer = false;
                        graphic.alwaysInRange = true;
                        container.map.addLayer(graphic);
                    }
                });
            }

            const provided = $(container).data("geo-center");

            if (provided && !central) {
                central = parseCoordinates(provided);
                useMarker = false;
            }

            if (central) {
                container.resetPosition(central);

                if (useMarker) {
                    $(container).addMapMarker({
                        lon: central.lon,
                        lat: central.lat,
                        unique: "selection",
                    });
                }
            }

            if (jqueryTiki.googleStreetView) {
                container.streetview = {
                    buttons: [],
                };

                if (jqueryTiki.googleStreetViewOverlay) {
                    container.streetview.overlay = new ol.layer.XYZ(
                        "StreetView Overlay",
                        currentProtocol + "//mts1.google.com/vt?hl=en-US&lyrs=svv|cb_client:apiv3&style=40,18&x=${x}&y=${y}&z=${z}",
                        { sphericalMercator: true, displayInLayerSwitcher: false }
                    );
                    container.map.addLayer(container.streetview.overlay);

                    container.map.events.on({
                        move: function () {
                            if (container.streetview.overlay.visibility) {
                                container.streetview.overlay.redraw();
                            }
                        },
                    });
                }

                const StreetViewHandler = ol.Class(ol.control, {
                    defaultHandlerOptions: {
                        single: true,
                        double: false,
                        pixelTolerance: 0,
                        stopSingle: false,
                        stopDouble: false,
                    },
                    initialize: function (options) {
                        this.handlerOptions = ol.Util.extend({}, this.defaultHandlerOptions);
                        ol.control.prototype.initialize.apply(this, arguments);
                        this.handler = new ol.Handler.Click(
                            this,
                            {
                                click: this.trigger,
                            },
                            this.handlerOptions
                        );
                    },
                    trigger: function (e) {
                        const width = 600,
                            height = 500,
                            lonlat = map.getLonLatFromViewPortPx(e.xy).transform(map.getProjectionObject(), new ol.proj.Projection("EPSG:4326"));

                        const canvas = $("<div/>")[0];
                        $(canvas).appendTo("body");
                        $.openModal({
                            title: tr("Panorama"),
                            content: canvas,
                            buttons: container.streetview.getButtons(canvas),
                        });

                        canvas.getImageUrl = function () {
                            const pov = canvas.panorama.getPov(),
                                pos = canvas.panorama.getPosition();

                            return (
                                `${currentProtocol}//maps.googleapis.com/maps/api/streetview?size=${width}x${height}&location=${encodeURIComponent(pos.toUrlValue())}` +
                                `&heading=${encodeURIComponent(pov.heading)}&pitch=${encodeURIComponent(pov.pitch)}&sensor=false`
                            );
                        };

                        canvas.getPosition = function () {
                            const pos = canvas.panorama.getPosition();

                            return formatLocation(pos.lat(), pos.lng(), 12);
                        };

                        canvas.panorama = new google.maps.StreetViewPanorama(canvas, {
                            position: new google.maps.LatLng(lonlat.lat, lonlat.lon),
                            zoomControl: false,
                            scrollwheel: false,
                            disableDoubleClickZoom: true,
                        });
                        const timeout = setTimeout(function () {
                            alert(
                                tr(
                                    "StreetView is not available at this specific point on the map. Zoom in as needed and make sure to click on a blue line."
                                )
                            );
                            $.closeModal();
                        }, 5000);
                        google.maps.event.addListener(canvas.panorama, "pano_changed", function () {
                            if (!canvas.panorama.getPano()) {
                                alert(
                                    tr(
                                        "StreetView is not available at this specific point on the map. Zoom in as needed and make sure to click on a blue line."
                                    )
                                );
                                $.closeModal();
                            }
                            clearTimeout(timeout);
                        });
                    },
                });

                container.modeManager.addMode({
                    title: "StreetView",
                    controls: [new StreetViewHandler(), new ol.control.NavToolbar()],
                    activate: function () {
                        if (container.streetview.overlay) {
                            container.streetview.overlay.setVisibility(true);
                        }
                    },
                    deactivate: function () {
                        if (container.streetview.overlay) {
                            container.streetview.overlay.setVisibility(false);
                        }
                    },
                });

                container.streetview.addButton = function (label, callback) {
                    container.streetview.buttons.unshift({
                        label: label,
                        callback: callback,
                    });
                };

                container.streetview.getButtons = function (canvas) {
                    const buttons = [];
                    $.each(container.streetview.buttons, function (k, b) {
                        buttons.push({
                            text: b.label,
                            onClick: b.callback.bind(null, canvas),
                        });
                    });

                    return buttons;
                };

                container.streetViewToggle = $("<a/>")
                    .css("display", "block")
                    .attr("href", "")
                    .on("click", function () {
                        if (container.modeManager.activeMode && container.modeManager.activeMode.name === "StreetView") {
                            container.modeManager.switchTo("Default");
                            $(this).text(tr("Enable StreetView"));
                        } else {
                            container.modeManager.switchTo("StreetView");
                            $(this).text(tr("Disable StreetView"));
                        }
                        return false;
                    })
                    .text(tr("Enable StreetView"));

                if (-1 !== $.inArray("streetview", desiredControls)) {
                    $(container).after(container.streetViewToggle);
                }
            }

            // start with the search boxes outside of the map container
            let searchboxes = $(container)
                .closest(".tab, #appframe, #tiki-center")
                .find("form.search-box")
                .filter(function () {
                    return $(this).closest(".map-container").length === 0;
                });

            // then add the ones inside
            searchboxes = searchboxes.add($("form.search-box", container));

            searchboxes
                .off("submit")
                .on("submit", function () {
                    $(container).trigger("start.map.search");
                    const form = this;
                    $.post(
                        "tiki-searchindex.php?filter~geo_located=y",
                        $(this).serialize(),
                        function (data) {
                            if (!data.result) {
                                return;
                            }
                            if (!form.autoLayers) {
                                form.autoLayers = [];
                            }

                            form.autoLayers.forEach((name) => {
                                container.clearLayer(name);
                            });

                            /**
                             * check if an object with the same id is already in the layer
                             * @param layer
                             * @param item
                             * @returns boolean
                             */
                            function checkFeatureExists(layer, item) {
                                const layerFeatures = layer.getSource().getFeatures();

                                const there = layerFeatures.find((feature) => {
                                    const subFeatures = feature.get("features");
                                    if (subFeatures) {
                                        return subFeatures.find((subFeature) => {
                                            return subFeature.get("object") === item.object_id;
                                        });
                                    } else {
                                        return feature.get("object") === item.object_id;
                                    }
                                });
                                return !!there;
                            }

                            data.result.forEach((item) => {
                                let layerName = $(form).data("result-layer"),
                                    suffix = $(form).data("result-suffix");

                                if (layerName && item[layerName]) {
                                    layerName = item[layerName] + ": ";
                                } else if (!layerName) {
                                    // this will use the "Editable" layer, container.vectors
                                    layerName = "";
                                }

                                if (suffix && item[suffix]) {
                                    layerName = layerName + item[suffix];
                                }

                                if (-1 === $.inArray(layerName, form.autoLayers)) {
                                    form.autoLayers.push(layerName);
                                }
                                let layer = container.getLayer(layerName);

                                let icon = "";
                                try {
                                    $(item.link).each(function () {
                                        // if the object has an img with it (tracker status for instance) then we need to find the <a>
                                        if ($(this).is("a")) {
                                            // and just using $(i.link).find("a") doesn't work for some reason
                                            icon = $(this).data("icon-src");
                                        }
                                    });
                                } catch (e) {}

                                if (item.geo_location) {
                                    if (checkFeatureExists(layer, item)) {
                                        return;
                                    }

                                    $(container).addMapMarker({
                                        coordinates: item.geo_location,
                                        content: item.title,
                                        type: item.object_type,
                                        object: item.object_id,
                                        icon: icon ? icon : null,
                                        layer: layerName,
                                        dataSource: item,
                                        form: form,
                                    });
                                } else if (item.geo_feature) {
                                    layer = container.getLayer(layerName, true);
                                    if (checkFeatureExists(layer, item)) {
                                        return;
                                    }

                                    const wkt = new ol.format.WKT(),
                                        format = new ol.format.GeoJSON();
                                    let features;

                                    try {
                                        features = format.readFeatures(item.geo_feature);
                                    } catch (e) {
                                        features = null;
                                    }

                                    if (!features) {
                                        // Corrupted feature - display plain marker
                                        $(container).addMapMarker({
                                            coordinates: $(container).getMapCenter(),
                                            content: item.title,
                                            type: item.object_type,
                                            object: item.object_id,
                                            icon: null,
                                            layer: layerName,
                                            dataSource: item,
                                        });
                                        return;
                                    }

                                    $.each(features, function (k, feature) {
                                        let initial;
                                        feature.set("itemId", item.object_id);
                                        feature.set("content", item.title);
                                        if ($(container).data("clusterexcludefield")) {
                                            feature.set("nocluster", item[`tracker_field_${$(container).data("clusterexcludefield")}`] === "y");
                                        }
                                        if ($(container).data("clusterincludefield")) {
                                            const includeValue = item[`tracker_field_${$(container).data("clusterincludefield")}`];
                                            feature.set("nocluster", includeValue === "n" || includeValue === null);
                                        }
                                        if (!feature.get("color")) {
                                            feature.set("color", defaultVectorColor);
                                        }
                                        if (!feature.get("intent")) {
                                            feature.set("intent", "vectors");
                                        }
                                        if (!feature.get("popup_config")) {
                                            feature.set("popup_config", $(form).data("popup-config"));
                                        }
                                        if (!feature.get("popup_fields")) {
                                            feature.set("popup_fields", $(form).data("popup-fields"));
                                        }
                                        if (!feature.get("popup_tpl")) {
                                            feature.set("popup_tpl", $(form).data("popup-tpl"));
                                        }

                                        initial = wkt.writeFeature(feature) + feature.get("color");

                                        feature.executor = delayedExecutor(5000, function () {
                                            const fields = {},
                                                current = wkt.writeFeature(feature) + feature.get("color");

                                            fields[item.geo_feature_field] = format.writeFeature(feature);

                                            if (current === initial || (layer !== container.vectors && layer !== container.vector_shapes)) {
                                                return;
                                            }

                                            $.post(
                                                $.service("tracker", "update_item"),
                                                {
                                                    trackerId: item.tracker_id,
                                                    itemId: item.object_id,
                                                    fields: fields,
                                                },
                                                function () {
                                                    initial = current;
                                                },
                                                "json"
                                            ).fail(function () {
                                                $(container).trigger("changed");
                                            });
                                        });
                                    });

                                    let layerSource = layer.getSource();
                                    if (typeof layerSource.getSource === "function") {
                                        layerSource = layerSource.getSource();
                                    }
                                    layerSource.addFeatures(features);

                                    $.each(features, function (k, feature) {
                                        $(container).trigger("add", [item, feature]);
                                    });
                                } else if (item.geo_file) {
                                    // load a file containing geometry, set using tracker Files indexGeometry option
                                    layer = container.getLayer(layerName, true);
                                    if (checkFeatureExists(layer, item)) {
                                        return;
                                    }

                                    let format;
                                    const files = item.geo_file.split(",");

                                    if (item.geo_file_format === "geojson") {
                                        format = new ol.format.GeoJSON();
                                    } else {
                                        if (item.geo_file_format === "gpx") {
                                            format = new ol.format.GPX();
                                        }
                                    }
                                    files.forEach(function (file) {
                                        $.get(file, function (data) {
                                            let features;
                                            try {
                                                features = format.readFeatures(data, {
                                                    dataProjection: "EPSG:4326",
                                                    featureProjection: container.map.getView().getProjection(),
                                                });
                                            } catch (e) {
                                                // Corrupted feature - display plain marker
                                                $(container).addMapMarker({
                                                    coordinates: $(container).getMapCenter(),
                                                    content: item.title,
                                                    type: item.object_type,
                                                    object: item.object_id,
                                                    icon: null,
                                                    layer: layerName,
                                                    dataSource: item,
                                                });
                                                return;
                                            }
                                            features.forEach((feature) => {
                                                feature.set("itemId", item.object_id);
                                                feature.set("content", item.title);
                                                if (!feature.get("color")) {
                                                    // TODO find a better default, from the searchlayer maybe?
                                                    feature.set("color", "#ffa500");
                                                }
                                                if (!feature.get("intent")) {
                                                    feature.set("intent", "vectors");
                                                }
                                                if (!feature.get("popup_config")) {
                                                    feature.set("popup_config", $(form).data("popup-config"));
                                                }
                                                if (!feature.get("popup_fields")) {
                                                    feature.set("popup_fields", $(form).data("popup-fields"));
                                                }
                                                if (!feature.get("popup_tpl")) {
                                                    feature.set("popup_tpl", $(form).data("popup-tpl"));
                                                }
                                            });
                                            let layerSource = layer.getSource();
                                            if (typeof layerSource.getSource === "function") {
                                                layerSource = layerSource.getSource();
                                            }
                                            layerSource.addFeatures(features);

                                            features.forEach((feature) => {
                                                $(container).trigger("add", [item, feature]);
                                            });
                                        });
                                    });
                                }
                            });
                        },
                        "json"
                    ).complete(function () {
                        $(container).trigger("complete.map.search");
                    });
                    return false;
                })
                .each(function () {
                    if ($(this).hasClass("onload")) {
                        const fm = this,
                            layerLoadDelay = parseInt($(fm).data("load-delay"), 10);

                        if (layerLoadDelay) {
                            setTimeout(function () {
                                $(fm).trigger("submit");
                            }, layerLoadDelay * 1000);
                        } else {
                            $(fm).trigger("submit");
                        }
                    }

                    let skip = false;
                    const $form = $(this),
                        refresh = parseInt($(this).data("result-refresh") ?? 0) * 1000;

                    if (refresh) {
                        let interval;
                        interval = setInterval(function () {
                            if (skip) {
                                skip = false;
                            } else {
                                $form.trigger("submit");
                            }
                        }, refresh);

                        $(container).on("unregister", function () {
                            clearInterval(interval);
                        });
                    }

                    $(container).on("changed", function () {
                        $form.trigger("submit");
                        skip = true;
                    });
                });
            $(container).on("search", function (e, data) {
                function markLocation(lat, lon, bounds) {
                    const lonlat = ol.proj.fromLonLat([lon, lat]),
                        toViewport = function () {
                            if (bounds) {
                                map.getView().zoomToExtent(bounds);
                            } else {
                                map.getView().setCenter(lonlat);
                                map.zoomToScale(500 * ol.INCHES_PER_UNIT.m);
                            }
                        };

                    $(container).addMapMarker({
                        lat: lat,
                        lon: lon,
                        unique: "selection",
                        click: toViewport,
                    });

                    if (typeof zoomToFoundLocation != "undefined") {
                        // Center map to the new location and zoom
                        let zoomFactor = -1;
                        switch (zoomToFoundLocation) {
                            default:
                            case "street":
                                zoomFactor = 1; // these are now metres per pixel
                                break;
                            case "town":
                                zoomFactor = 10;
                                break;
                            case "region":
                                zoomFactor = 100;
                                break;
                            case "country":
                                zoomFactor = 500;
                                break;
                            case "continent":
                                zoomFactor = 20000;
                                break;
                            case "world":
                                zoomFactor = -1;
                                break;
                        }
                        const view = map.getView();
                        if (zoomFactor < 0) {
                            view.setZoom(2); // whole world, more or less
                        } else {
                            view.setCenter(lonlat);
                            view.setZoom(view.getZoomForResolution(zoomFactor * ol.proj.Units.METERS_PER_UNIT.m));
                        }
                    } else {
                        if (!container.map.getExtent().containsLonLat(lonlat)) {
                            // Show marker on world map
                            toViewport();
                        }
                    }
                }

                function markGoogleLocation(result) {
                    const loc = result.geometry.location,
                        sw = result.geometry.viewport.getSouthWest(),
                        ne = result.geometry.viewport.getNorthEast(),
                        osw = ol.proj.fromLonLat([sw.lng(), sw.lat()]),
                        one = ol.proj.fromLonLat([ne.lng(), ne.lat()]),
                        left = osw[0],
                        bottom = osw[1],
                        right = one[0],
                        top = one[1];

                    markLocation(loc.lat(), loc.lng(), [left, bottom, right, top]);
                }

                function getBounds(bounds) {
                    const osw = new ol.LonLat(bounds.left, bounds.bottom).transform(map.getProjectionObject(), new ol.proj.Projection("EPSG:4326")),
                        one = new ol.LonLat(bounds.right, bounds.top).transform(map.getProjectionObject(), new ol.proj.Projection("EPSG:4326"));

                    return new google.maps.LatLngBounds(new google.maps.LatLng(osw.lat, osw.lon), new google.maps.LatLng(one.lat, one.lon));
                }

                if (data.address) {
                    if (window.google && google.maps && google.maps.Geocoder) {
                        const geocoder = new google.maps.Geocoder(),
                            loc = $(container).getMapCenter().split(",");

                        geocoder.geocode(
                            {
                                //bounds: getBounds(map.getExtent()),
                                address: data.address,
                            },
                            function (results, status) {
                                const $list = $("<ul/>");

                                if (status === google.maps.GeocoderStatus.OK) {
                                    if (results.length === 1) {
                                        markGoogleLocation(results[0]);
                                        return;
                                    } else if (results.length > 0) {
                                        $.each(results, function (k, result) {
                                            const $link = $("<a href='#'/>");
                                            $link.text(result.formatted_address);
                                            $link.on("click", function () {
                                                markGoogleLocation(result);
                                                return false;
                                            });
                                            $("<li/>").append($link).appendTo($list);
                                        });
                                    }
                                }

                                $.openModal({
                                    title: data.address,
                                    content: $list,
                                });
                            }
                        );
                    } else {
                        $.getJSON("tiki-ajax_services.php", { geocode: data.address }, function (data) {
                            if (data && data.status === "OK") {
                                markLocation(data.lat, data.lon, 500);
                            } else {
                                let msg;
                                if (data && data.error) {
                                    msg = data.status + ": " + data.error;
                                } else {
                                    msg = tr("Location service unnavailable");
                                }
                                $(container).parent().showError(msg);
                            }
                        });
                    }
                }
            });

            // Initialize colorpicker functionality
            function initializeColorpicker() {
                const $colorpickerContainer = $(container).find(".map-colorpicker-container");
                if (!$colorpickerContainer.length) return;

                const colors = $colorpickerContainer.data("colorpicker-colors") || [],
                    title = $colorpickerContainer.data("colorpicker-title") || tr("Color Picker"),
                    modalId = "mapColorpickerModal-" + id;

                // Create Bootstrap 5 Modal for colorpicker
                const swatchesHtml = colors
                    .map(function (color) {
                        return `<button type="button" class="btn color-swatch p-0 m-1" style="background-color:${color};
                                    width:32px; height:32px; border:2px solid #ccc; border-radius:4px;" data-color="${color}" title="${color}">
                            </button>`;
                    })
                    .join("");

                const modalHtml = `
<div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="${modalId}Label">${title}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="colorPickerInput-${id}" class="form-label">${tr("Select Color")}</label>
                    <input type="color" class="form-control form-control-color w-100" id="colorPickerInput-${id}" value=${defaultVectorColor}>
                </div>
                <div class="color-swatches d-flex flex-wrap">${swatchesHtml}</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${tr("Close")}</button>
                <button type="button" class="btn btn-primary" id="applyColor-${id}">${tr("Apply")}</button>
            </div>
        </div> 
    </div>
</div>`;

                $(document.body).append(modalHtml);

                const $modal = $("#" + modalId),
                    $colorInput = $("#colorPickerInput-" + id),
                    $applyBtn = $("#applyColor-" + id);
                let selectedFeature = null;

                // Store colorpicker config on container
                container.colorpicker = {
                    colors: colors,
                    modal: $modal,
                    show: function (feature) {
                        selectedFeature = feature;
                        const currentColor = feature.get("color") || defaultVectorColor;
                        $colorInput.val(currentColor);
                        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
                    },
                };

                // Handle color swatch clicks
                $modal.on("click", ".color-swatch", function () {
                    const color = $(this).data("color");
                    $colorInput.val(color);
                    // Highlight selected swatch
                    $modal.find(".color-swatch").css("border-color", "#ccc");
                    $(this).css("border-color", "#000");
                });

                // Handle apply button
                $applyBtn.on("click", function () {
                    if (selectedFeature) {
                        container.map.getInteractions().forEach(function (interaction) {
                            if (interaction instanceof ol.interaction.Select) {
                                interaction.getFeatures().clear();
                            }
                        });
                        applyColorToFeature(selectedFeature, $colorInput.val());
                    }
                    bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
                });

                // Function to apply color to a feature's style
                function applyColorToFeature(feature, color) {
                    // Initialize container-level color storage if not exists
                    if (!container.featureColors) {
                        container.featureColors = {};
                    }

                    // Store color on feature property
                    feature.set("color", color);

                    // Also store by coordinates for persistence across cluster recalculations
                    const geom = feature.getGeometry();
                    let key = null;
                    if (geom && geom.getCoordinates) {
                        const coords = geom.getCoordinates();
                        if (Array.isArray(coords)) {
                            key = coords.join(",");
                            container.featureColors[key] = color;
                        }
                    }
                    let coloredStyle = getFeatureStyle(feature, color);

                    if (feature.executor) {
                        feature.executor();
                    }

                    // Also try to find this feature in all sources and update it there
                    const layers = container.overlays.getLayers().getArray();

                    layers.forEach(function (layer) {
                        if (layer.getSource && layer.getSource()) {
                            const source = layer.getSource();

                            // Check if this is a cluster source
                            if (source.getSource) {
                                const innerSource = source.getSource();
                                // Find features at same coordinates
                                if (key) {
                                    innerSource.getFeatures().forEach(function (f) {
                                        const fGeom = f.getGeometry();
                                        if (fGeom && fGeom.getCoordinates) {
                                            const fCoords = fGeom.getCoordinates();
                                            if (Array.isArray(fCoords) && fCoords.join(",") === key) {
                                                f.set("color", color);
                                                f.setStyle(coloredStyle);
                                            }
                                        }
                                    });
                                }
                                // Refresh the cluster
                                source.refresh();
                            }

                            source.changed();
                        }
                        layer.changed();
                    });

                    // Force a full map render
                    map.renderSync();
                }

                // Function to re-apply all stored colors to features
                function reapplyStoredColors() {
                    if (!container.featureColors || Object.keys(container.featureColors).length === 0) {
                        return;
                    }

                    const layers = container.overlays.getLayers().getArray();
                    layers.forEach(function (layer) {
                        if (layer.getSource && layer.getSource()) {
                            const source = layer.getSource();

                            // For cluster sources, check the inner source
                            const featureSource = source.getSource ? source.getSource() : source;

                            featureSource.getFeatures().forEach(function (feature) {
                                const geom = feature.getGeometry();
                                if (geom && geom.getCoordinates) {
                                    const coords = geom.getCoordinates();
                                    if (Array.isArray(coords)) {
                                        const key = coords.join(",");
                                        if (container.featureColors[key]) {
                                            getFeatureStyle(feature, container.featureColors[key]);
                                        }
                                    }
                                }
                            });
                        }
                    });
                }

                // Hook into feature selection to show colorpicker
                $(container).on("modechanged", function () {
                    if (container.modeManager.activeMode.name === tr("Select/Modify")) {
                        // Add colorpicker trigger on feature selection when Shift is held
                        map.on("click", function (evt) {
                            if (evt.originalEvent.shiftKey) {
                                map.forEachFeatureAtPixel(evt.pixel, function (feature, layer) {
                                    if (feature && container.colorpicker) {
                                        // Get the actual feature (not cluster)
                                        let targetFeature = feature;
                                        const clusterFeatures = feature.get("features");
                                        if (clusterFeatures && clusterFeatures.length === 1) {
                                            targetFeature = clusterFeatures[0];
                                        }
                                        container.colorpicker.show(targetFeature);
                                        return true; // Stop iteration
                                    }
                                });
                            } else {
                                // On non-shift clicks, re-apply stored colors after a short delay
                                setTimeout(function () {
                                    reapplyStoredColors();
                                }, 50);
                            }
                        });

                        // Also re-apply colors when map is moved or rendered
                        map.on("moveend", function () {
                            reapplyStoredColors();
                        });
                    }
                });
            }

            initializeColorpicker();

            /****** $.fn.creatMap() all done, tell everyone! ******/
            setTimeout(function () {
                $(container).trigger("initialized");
            }, 1000);
        });

        return this;
    };

    $.fn.addMapMarker = function (options) {
        this.each(function () {
            const container = this;
            let lonlat,
                iconModel = "default";

            if (options.unique) {
                iconModel = options.unique;
            }

            if (options.icon) {
                iconModel = options.icon;
            }

            if (options.coordinates) {
                const parts = options.coordinates.split(",");
                if (parts.length >= 2) {
                    options.lon = parts[0];
                    options.lat = parts[1];
                }
            }

            if (options.lat && options.lon) {
                lonlat = new ol.geom.Point(ol.proj.fromLonLat([parseFloat(options.lon), parseFloat(options.lat)]));
            }

            container.markerIcons.createMarker(iconModel, lonlat, function (feature) {
                if (options.type && options.object) {
                    feature.set("type", options.type);
                    feature.set("object", options.object);
                }
                if (!feature.get("popup_config") && options.form) {
                    feature.set("popup_config", $(options.form).data("popup-config"));
                }
                if (!feature.get("popup_fields") && options.form) {
                    feature.set("popup_fields", $(options.form).data("popup-fields"));
                }
                if (!feature.get("popup_tpl") && options.form) {
                    feature.set("popup_tpl", $(options.form).data("popup-tpl"));
                }

                feature.setStyle(function (feature) {
                    if (container.isClustered(feature, markerLayer)) {
                        return null; // hide the marker
                    }
                    return new ol.style.Style({
                        image: new ol.style.Icon({
                            anchor: [0.5, 0.8],
                            src: feature.get("url"),
                        }),
                    });
                });

                const markerLayer = container.getLayer(options.layer);
                let initial = writeCoordinates(lonlat.clone(), container.map, true);

                let markerLayerSource = markerLayer.getSource();

                if (typeof markerLayerSource.getSource === "function") {
                    markerLayerSource = markerLayerSource.getSource(); // Cluster layer source
                }

                if (options.unique) {
                    if (container.uniqueMarkers[options.unique]) {
                        markerLayerSource.removeFeature(container.uniqueMarkers[options.unique]);
                        delete container.uniqueMarkers[options.unique];
                    }
                }
                markerLayerSource.addFeatures([feature]);

                if (options.unique) {
                    container.uniqueMarkers[options.unique] = feature;
                    $(container).trigger(options.unique + "Change", options);
                }

                if (options.type === "trackeritem" && options.object && markerLayer === container.vectors) {
                    feature.executor = delayedExecutor(5000, function () {
                        const current = writeCoordinates(feature.getGeometry().getCoordinates(), container.map, true);

                        if (current === initial) {
                            return;
                        }

                        $.post(
                            $.service("tracker", "set_location"),
                            {
                                itemId: options.object,
                                location: current,
                            },
                            function () {
                                initial = current;
                            },
                            "json"
                        ).fail(function () {
                            $(container).trigger("changed");
                        });
                    });
                }

                if (options.content) {
                    feature.set("content", options.content);
                }

                if (options.click) {
                    feature.clickHandler = options.click;
                }

                if (options.dataSource) {
                    $(container).trigger("add", [options.dataSource, feature]);
                }
            });
        });

        return this;
    };

    $.fn.setupMapSelection = function (options) {
        let control;

        this.each(function () {
            const container = this,
                field = options.field,
                map = container.map;

            if (!field.attr("disabled")) {
                $(container).on("selectionChange", function (e, lonlat) {
                    if (lonlat) {
                        if (lonlat.lat && lonlat.lon) {
                            lonlat = new ol.geom.Point([lonlat.lon, lonlat.lat]);
                            field.val(writeCoordinates(lonlat, map)).trigger("change");
                        }
                    } else {
                        field.val("").trigger("change");
                    }
                });
            }

            control = new ol.interaction.Pointer({
                handleUpEvent: (event) => {
                    const container = this,
                        map = container.map;
                    let coords;

                    if (event.coordinates) {
                        coords = event.coordinates;
                    } else {
                        coords = map.getCoordinateFromPixel([event.originalEvent.layerX, event.originalEvent.layerY]);
                    }
                    const lonlat = ol.proj.transform(
                        coords,
                        map.getView().getProjection(), // source — replaces map.getProjectionObject()
                        "EPSG:4326" // target
                    );

                    $(container).addMapMarker({
                        lat: lonlat[1],
                        lon: lonlat[0],
                        unique: "selection",
                    });
                    $(container).trigger("selectionChange", {
                        lat: lonlat[1],
                        lon: lonlat[0],
                    });

                    if (options.click) {
                        options.click();
                    }
                    return false;
                },
                handleDownEvent: (event) => {
                    return true; // return true to "handle" it and trigger handleUpEvent
                },
                stopDown() {
                    return false; // ...but still let DragPan see the down event
                },
            });

            container.map.getView().on("propertychange", function (e) {
                if (e.key === "resolution" && !isNaN(e.oldValue)) {
                    const coords = field.val().split(",");
                    let lon = 0,
                        lat = 0;

                    if (coords.length > 1) {
                        lon = coords[0];
                        lat = coords[1];
                    }
                    field.val(formatLocation(lat, lon, parseInt(map.getView().getZoom()))).trigger("change");
                }
            });

            container.map.addInteraction(control);
        });

        // this only returns one "control" (interaction) even though it can supposedly bind to multiple maps
        // doesn't seem right...
        return control;
    };

    $.fn.removeMapSelection = function () {
        this.each(function () {
            const container = this;

            if (container.uniqueMarkers["selection"]) {
                let source = container.vectors.getSource();
                if (typeof source.getSource !== "undefined") {
                    source = source.getSource();
                }
                source.removeFeatures([container.uniqueMarkers["selection"]]);
            }

            $(container).trigger("selectionChange", [{}]);
        });

        return this;
    };

    $.fn.getMapCenter = function () {
        let val;

        this.each(function () {
            const coordinates = this.map.getView().getCenter();
            val = ol.proj.transform(coordinates, this.map.getView().getProjection(), "EPSG:4326");
        });

        return val.join(",");
    };

    $.fn.loadInfoboxPopup = function (options) {
        if (options.type && options.object && $.inArray(options.type, jqueryTiki.infoboxTypes) !== -1) {
            this.each(function () {
                $.get(
                    $.service("object", "infobox", {
                        type: options.type,
                        object: options.object,
                        popupFields: options.feature.get("popup_fields"),
                        popupTpl: options.feature.get("popup_tpl"),
                    }),
                    function (data) {
                        const content = $("<body>").append(data);

                        content.find(".svgImage").css("text-align", "center").css("margin", "auto");

                        if (jqueryTiki.colorbox) {
                            applyGlightbox();
                        }

                        if (options.callback) {
                            options.callback.call(options.element, options.event, content);
                        }
                    },
                    "html"
                );
            });

            return true;
        } else {
            return false;
        }
    };

    $(document).on("tiki.modal.redraw", ".modal.fade", function () {
        $(".map-container:not(.done)")
            .addClass("done")
            .visible(function () {
                $(this).createMap();
            });
    });
})();
