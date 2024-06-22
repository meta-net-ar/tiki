Tiki Custom Files
-----------------
Part of the planned file and directory revamp, this directory will eventually contain all custom files specific to your Tiki install.

i.e. Custom themes, JavaScript, plugins and configuration files.

custom.php
----------
Currently any custom PHP code required for your site can now be added to _custom/lib/setup/custom.php.  It will be included ath the end of tiki-setup.php

For instance, you can add new bindings to events here, e.g. a custom function to run when a tracker item is saved

    // first define your custom event handler function

    function itemWasSaved($args) {
        // perform post item save actions here such as:
        if ($args['trackerId'] === '42') {    // only for tracker #42
            $status = $args['values']['status'];
            $oldStatus = $args['old_values']['status'];
            $itemId = $args['object'];
            // ... etc
        }
    }

    // then bind your custom function to an event
    TikiLib::lib('events')->bind('tiki.trackeritem.save', 'itemWasSaved');

    // note: you can find the full list of events in lib/setup/events.php

N.B. this directory and all files within it should NOT be web writable.

See (and contribute to) https://dev.tiki.org/File-and-directory-structure-revamp for more information.

