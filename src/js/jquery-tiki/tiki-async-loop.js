import { eachLimit } from "async-es";

// Extend jQuery with eachAsync method
$.fn.eachAsync = function (opts) {
    const {
        loop = () => {}, // Loop function to process each element
        end = () => {}, // End function after processing all elements
        delay = 10, // Delay between each batch (ms)
        bulk = 500, // Max time for each batch (ms)
    } = opts;
    const array = this.toArray(); // Convert jQuery object to an array of DOM elements
    let i = 0; // Index for tracking current item
    let batchStart = performance.now();

    // Process each batch of elements
    function processBatch() {
        // Use eachLimit to process a subset of elements with a concurrency of 1
        eachLimit(
            array,
            1,
            (item, next) => {
                loop.call(item, i, item);
                i++;

                if (bulk > 0 && performance.now() - batchStart > bulk) {
                    setTimeout(() => {
                        batchStart = performance.now();
                        next();
                    }, delay);
                } else {
                    next();
                }
            },
            () => end()
        );
    }

    setTimeout(() => {
        processBatch();
    }, 0);

    return this;
};
