<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Formatter_ArrayBuilder
{
    public function getData($string)
    {
        $matches = WikiParser_PluginMatcher::match($string);
        $parser = new WikiParser_PluginArgumentParser();

        $data = [];

        foreach ($matches as $m) {
            $name = $m->getName();
            $arguments = $m->getArguments();
            $entry = $parser->parse($arguments);

            // Body-bearing chunks ({name args}body{/name}) expose the
            // raw body text under the conventional "_body" key. The
            // unified-reporting templates (chartjs_full and friends)
            // rely on this so report authors can drop a full Chart.js
            // (or other library) options blob inside
            $body = $m->getBody();
            if (is_string($body) && $body !== '') {
                $entry['_body'] = $body;
            }

            if (isset($data[$name])) {
                if (! is_int(key($data[$name]))) {
                    $data[$name] = [$data[$name]];
                }

                $data[$name][] = $entry;
            } else {
                $data[$name] = $entry;
            }
        }

        return $data;
    }
}
