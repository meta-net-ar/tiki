/* (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 *
 *
 * Custom search js helper function, mainly to maintain the search state in the url hash
 *
 * N.B. Only works for a single customsearch instance per page with the id #customsearch_0
 */


(function ($) {

    let $csForm, $formInputs;

    $(document).on("formSearchReady", function () {

        // FIXME currently only works with the default id, but avoid js error here when it's customsearch_42 or something
        customsearch_0 = typeof customsearch_0 === "object" ? customsearch_0 : {};

        $csForm = $('#customsearch_0');
        $(".jscal input[type=text], .jscal input[name=tzoffset], .jscal input[name=tzname]", $csForm).addClass("ignore");

        $formInputs = $("input[type!=Submit]:not(.ignore), select:not(.ignore)", $csForm);
        $formInputs
            .each(function () {    // record defaults for search inputs to save cluttering up the location.hash
                const $this = $(this);
                if ($this.is("input[type=checkbox]") || $this.is("input[type=radio]")) {
                    $this.data("default", $this.prop("checked"));
                } else {
                    $this.data("default", $this.val());
                }
            })
            .on("change", function () {
                customsearch_0.auto();
            })
            .on("keyup", function () {
                customsearch_0.auto();
            });


        $("#sortby", $csForm).on("change", function () {
            customsearch_0.sort_mode = $(this).val();
            // remove the representation of this select in the search
            delete customsearch_0.searchdata.sortby;
            customsearch_0.auto();
            return false;
        });

        $("#max", $csForm).on("change", function () {
            customsearch_0.maxRecords = $(this).val();
            delete customsearch_0.searchdata.max;
            customsearch_0.auto();
        });

        getHash();
    });


    $(document).on("pageSearchReady", function () {
        // results loaded
        const $csResults = $("#customsearch_0_results");

        if (typeof lozad !== "undefined") {
            lozad('.lozad', {
                load: function (el) {
                    el.src = el.dataset.src;
                    el.onload = function () {
                        el.classList.add('lozadFade');
                    };
                }
            }).observe();
        }

        if (jqueryTiki.colorbox) {
            $.applyColorbox();
        }

        if (jqueryTiki.tooltips) {
            $csResults.tiki_popover();
        }

        $('.facets ul').registerFacet();


        // sticky facets
        const $facets = $(".facets"),
            pos = $facets.offset();

        if ($facets.length) {
            const width = $facets.css("width"),
                topOffset = 70,
                footer = $("footer"),
                footPos = footer.offset().top,
                facetHeight = $facets.height();

            $window.on("scroll", function () {
                const windowpos = $window.scrollTop();
                let top = 60;
                if (windowpos > pos.top - topOffset) {
                    if (footPos - windowpos - topOffset < facetHeight) {
                        top = footPos - windowpos - facetHeight - 10;
                    } else {
                        top = 60;
                    }
                    $facets.css({
                        position: "fixed",
                        top: top + "px",
                        width: width
                    });
                    //console.log(footPos - windowpos);
                } else {
                    $facets.css({
                        position: "inherit",
                        top: "auto",
                        width: width
                    });
                }

            });
        }

        // update the url hash with the current returned search results
        setHash();

        return true;
    });

    function setHash()
    {
        let ser = "";

        $formInputs.each(function () {
            const $this = $(this),
                defaultValue = $this.data("default");

            if ($this.is("input[type=checkbox]") || $this.is("input[type=radio]")) {
                if ($this.prop("checked") !== defaultValue) {
                    if ($this.attr("id")) {
                        ser += $this.attr("id") + "=1&";
                    } else {
                        ser += "." + $this.prop("className") + "=1&";
                    }
                }
            } else {
                const currentValue = $this.val();
                if ((typeof currentValue === "string" && currentValue !== defaultValue) || JSON.stringify(currentValue) !== JSON.stringify(defaultValue)) {
                    const val = encodeURIComponent(currentValue).replace("%20", "+");
                    if ($this.attr("id")) {
                        ser += $this.attr("id") + "=" + val + "&";
                    } else {
                        ser += "." + $this.prop("className") + "=" + val + "&";
                    }
                }
            }
        });

        const pagenum = parseInt($('.pagination .active').text());
        if (pagenum > 1) {        // offset
            const max = $("#max", $csForm).val();
            ser += "offset=" + (pagenum - 1) * max;
        }

        const $sortLink = $("th a .icon-sort-up,th a .icon-sort-down", ".customsearch_results").parent();
        if ($sortLink.length) {
            const sort_mode = $sortLink.attr("onclick").match(/\.sort_mode='(.*?)'/);
            if (sort_mode) {
                ser += "sort_mode=" + sort_mode[1];
            }
        }

        window.location.hash = ser.replace(/&$/, "");
    }

    function getHash()
    {
        let e, a, r, d, q, hashKey;
        if (location.hash) {
            // from http://stackoverflow.com/questions/4197591/parsing-url-hash-fragment-identifier-with-javascript - thanks :)
            let hashParams = {};
            a = /\+/g;
            r = /([^&;=]+)=?([^&;]*)/g;
            d = function (s) {
                return decodeURIComponent(s.replace(a, " "));
            };
            q = window.location.hash.substring(1);

            while (e = r.exec(q)) {
                hashParams[d(e[1])] = d(e[2]);
            }

            let triggerIt = false, $el, selector;
            customsearch_0.quiet = true;

            for (hashKey in hashParams) {
                if (hashParams.hasOwnProperty(hashKey)) {
                    if (hashKey.indexOf(".") === 0) {
                        selector = hashKey + ":first";
                    } else {
                        selector = "#" + hashKey;
                    }
                    $el = $(selector, $csForm);
                    let value = hashParams[hashKey];
                    if ($(selector + "[type=checkbox]").length || $(selector + "[type=radio]").length) {
                        triggerIt = true;
                        $el.prop("checked", value !== "").trigger('change');
                    } else {
                        if ($el.length) {
                            triggerIt = true;
                            if ($el.prop("multiple") && value.indexOf(",") > -1) {
                                value = value.split(",");
                            }
                            $el.val(value).trigger('change');
                        } else {
                            if (hashKey === "offset") {
                                triggerIt = true;
                                customsearch_0.offset = value;
                            } else if (hashKey === "sort_mode") {
                                triggerIt = true;
                                // value is what the sort mode will change to if clicked
                                if (value.match(/_asc$/)) {
                                    value = value.replace(/_asc$/, '_desc');
                                } else {
                                    value = value.replace(/_desc$/, '_asc');
                                }
                                customsearch_0.sort_mode = value;
                            }
                        }
                    }
                }
            }
            customsearch_0.quiet = false;
            if (triggerIt) {
                customsearch_0.load();
            }
        } else {
            if (window.location.search) {
                let params = {};
                a = /\+/g;
                r = /([^&;=]+)=?([^&;]*)/g;
                d = function (s) {
                    return decodeURIComponent(s.replace(a, " "));
                };
                q = window.location.search.substring(1);

                while (e = r.exec(q)) {
                    params[d(e[1])] = d(e[2]);
                }

                customsearch_0.offset = 0;

                if (params.q) {    //  && params.q != ""
                    $("#search", $csForm).val(params.q).trigger("change");
                    delete params.q;
                }

                if (params.t) {    //  type from portal pages
                    $("label:contains(" + params.t + ") > input", $csForm).prop("checked", true).trigger("change");
                    delete params.t;
                }

                $.each(params, function (k, v) {
                    const $el = $("[name='" + k + "']");
                    if (k !== "q" && $el.length) {
                        $el.val(v).trigger("change");
                    }
                });

                $("select", $csForm).trigger("change");
                customsearch_0.load();
            }
        }
        $csForm.trigger("change");
    }

}(jQuery));
