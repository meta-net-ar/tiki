<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_mermaid_info()
{
    return [
        'name' => tra('MERMAID'),
        'documentation' => 'PluginMermaid',
        'description' => tra('Show Mermaid Diagram in wiki page'),
        'prefs' => ['wikiplugin_mermaid'],
        'body' => tra('Content of the hideable zone (in Wiki syntax)'),
        'filter' => 'wikicontent',
        'format' => 'html',
        'iconname' => 'wizard',
        'introduced' => 3,
        'tags' => [ 'basic' ],
        'params' => [
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'filter' => 'text',
                'description' => tra('Width of the content of your diagram (as a value, you can use for example 100%, 100vw, ...)'),
                'default' => 'auto',
                'since' => '29.0',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'filter' => 'text',
                'description' => tra('Height of the content of your diagram (as a value, you can use for example 100%, 100vw, ...)'),
                'default' => '60vh',
                'since' => '29.0',
            ],
        ]
    ];
}

function wikiplugin_mermaid($data, $params)
{
    require_once("export-tracker_schema.php");

    $width = $params['width'];
    $height = $params['height'];
    $mermaidOutput = handleMermaid($data, $width, $height);

    $output = <<<EOT
        <div class='p-3 border border-secondary'>
            $mermaidOutput
        </div>
    EOT;
    return $output;
}
