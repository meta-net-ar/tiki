<?php

Feedback::success("_custom/shared/custom.php hello world");

/* Currently any custom PHP code required for your site can be added to _custom/shared/custom.php.
 * (prior to tiki 27 this was _custom/lib/setup/custom.php)
 * This file will be included at the end of tiki-setup.php
 */

/* For instance, you can add new bindings to events here, e.g. a custom function to run when a tracker item is saved */

/*
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

    // note: you can find the full list of events in [lib/setup/events.php](../lib/setup/events.php)
    */
