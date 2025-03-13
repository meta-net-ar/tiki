/** This is currently only used in the "new" Vue3 modules */
function onDOMElementRemoved(id, rootCallback) {
    // Options for the observer (which mutations to observe)
    const config = { attributes: false, childList: true, subtree: true };

    // Select the node that will be observed for mutations
    const targetNode = $('body')[0];

    if (!targetNode) return;

    // Create an observer instance linked to the callback function
    const observer = new MutationObserver(callback);

    // Start observing the target node for configured mutations
    observer.observe(targetNode, config);

    // Callback function to execute when mutations are observed
    function callback(mutationsList, observer) {
        // Use traditional 'for loops' for IE 11
        for(const mutation of mutationsList) {
            if (mutation.type === 'childList' && mutation.removedNodes.length > 0) {
                mutation.removedNodes.forEach(node => {
                    // If node is element (1)
                    if (node.nodeType === 1) {
                        if (node.id === id || node.querySelector(`#${$.escapeSelector(id)}`)) {
                            rootCallback();
                            // Later, you can stop observing
                            observer.disconnect();
                        }
                    }
                });
            }
        }
    };
}
