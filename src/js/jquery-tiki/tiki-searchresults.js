/**
 * Handle sticky behavior of the search facets sidebar for a dynamic page scroll
 */

const searchFacetsSidebar = document.querySelector(".search-facets-sidebar");

let stickyStart = 0;
$(window).on("resize", () => {
    // Recalculate the point at which the sidebar becomes sticky before any scrolling has occurred
    stickyStart =
        searchFacetsSidebar.getBoundingClientRect().top +
        window.scrollY -
        searchFacetsSidebar.scrollTop -
        Number(getComputedStyle(searchFacetsSidebar).top.replace("px", ""));
});

$(window).on("scroll", () => {
    if (getComputedStyle(searchFacetsSidebar).position !== "sticky") {
        return;
    }

    const maxScroll = searchFacetsSidebar.scrollHeight - searchFacetsSidebar.clientHeight;
    if (maxScroll <= 0) {
        return;
    }

    const offset = window.scrollY - stickyStart;
    searchFacetsSidebar.scrollTop = Math.max(0, Math.min(offset, maxScroll));
});
