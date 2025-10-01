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

    // Process each batch of elements
    function processBatch() {
        const batchStart = performance.now();

        // Use eachLimit to process a subset of elements with a concurrency of 1
        eachLimit(
            array,
            1,
            (item, callback) => {
                if (i < array.length && performance.now() - batchStart < bulk) {
                    loop(item, i);
                    i++;
                    callback();
                } else {
                    callback(new Error("Bulk time exceeded"));
                }
            },
            (err) => {
                // If an error or time exceeded, schedule the next batch after a delay
                if (i < array.length) {
                    setTimeout(processBatch, delay);
                } else {
                    end();
                }
            }
        );
    }

    processBatch();
    return this;
};
