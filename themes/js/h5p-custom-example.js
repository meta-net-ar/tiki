/* example customisation for h5p timeline */

(function () {

    if (!H5P || !H5P.externalDispatcher) {
        return; // Cannot track events
    }

    // Uncomment this to output H5P events
    // H5P.externalDispatcher.on('*', function (event) {
    // });

    H5P.externalDispatcher.on('initialized', function (event) {
        // set the timeline position to the nearest to "now"
        // actually too late to do it here for this content type,
        // so need to override the TimelineJS object and modify the options
    });

    // copied from storage/public/h5p/libraries/TimelineJS-1.1/timeline.js
    window.TimelineJS = (function ($) {
        function Timeline(options, major, minor)
        {
            // This non-runnable library does not know it's own major+minor, therefore
            // we have tp provide it from runnable library using me
            var libraryPath = 'TimelineJS-' + major + '.' + minor;

            // Set this global variable to inform TimelineJS where all CSS/JS is placed
            window.embed_path = H5P.getLibraryPath(libraryPath) + '/';

            // from storage/public/h5p/libraries/TimelineJS-1.1/timeline.js
            options.start_at_end = true;

            createStoryJS(options);
        }

        return Timeline;
    })(H5P.jQuery);

}());
