<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_semanticsearch_info()
{
    return [
        'name' => tra('Semantic Search'),
        'documentation' => 'PluginSemanticSearch',
        'description' => tra('Create a custom search using the https://github.com/Textualization/php-semantic-search'),
        'prefs' => ['wikiplugin_customsearch', 'feature_search'],
        'format' => 'html',
        'iconname' => 'search',
        'introduced' => 27,
        'tags' => ['advanced'],
        'params' => [
            'resultstpl' => [
                'required' => false,
                'name' => tra('Template file'),
                'description' => tra('Smarty template (.tpl) file where search user interface template is found'),
                'since' => '27',
                'default' => 'wiki-plugins/wikiplugin_semanticsearch_results.tpl',
                ],
            'searchformtpl' => [
                'required' => false,
                'name' => tra('Template file'),
                'description' => tra('Smarty template (.tpl) file where search user interface template is found'),
                'since' => '27',
                'default' => 'wiki-plugins/wikiplugin_semanticsearch_form.tpl',
                ],
            'searchformwiki' => [
                'required' => false,
                'name' => tra('Template wiki page'),
                'description' => tra('Wiki page where the search form is found'),
                'since' => '27',
                'filter' => 'pagename',
                'default' => ''
            ],
            'indexer' => [
                'required' => false,
                'name' => tra('Textualization indexer'),
                'description' => tra('The textualization index class to instanciate.'),
                'filter' => 'string',
                'since' => '27',
                'default' => '\\Textualization\\SemanticSearch\\VectorIndex',
                ],
            'embedder' => [
                'required' => false,
                'name' => tra('Textualization embedder'),
                'description' => tra('The textualization embedder class to instanciate.  It must match the one used during server indexing'),
                'filter' => 'string',
                'since' => '27',
                'default' => '\\Textualization\\SemanticSearch\\SentenceTransphormerXLMEmbedder',
                ],
            'dblocation' => [
                'required' => true,
                'name' => tra('Template file'),
                'description' => tra('The filesystem location of the .db file to search into.'),
                'since' => '27'
                ],
        ],
    ];
}

function wikiplugin_semanticsearch($data, $params)
{
    global $prefs;
    $defaults = [];
    $plugininfo = wikiplugin_semanticsearch_info();
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults["$key"] = $param['default'] ?? null;
    }
    $params = array_merge($defaults, $params);

    if (empty($params['searchformwiki']) && empty($params['searchformtpl'])) {
        $params['searchformtpl'] = 'templates/search_customsearch/default_form.tpl';
    } elseif (! empty($params['searchformwiki']) && ! TikiLib::lib('tiki')->page_exists($params['searchformwiki'])) {
        $link = new WikiParser_OutputLink();
        $link->setIdentifier($params['searchformwiki']);
        return tra('Template page not found') . ' ' . $link->getHtml();
    }
    if (! empty($params['searchformwiki'])) {
        $wikitpl = "tplwiki:" . $params['searchformwiki'];
    } else {
        $wikitpl = $params['searchformtpl'];
    }

    $id = 'default';
    $html = '';

    $location = $params['dblocation'];
    if (! is_file($location)) {
        throw new InvalidArgumentException("$location isn't a file");
    }
    /** This is the configuration object for the SemanticSearch IndexFactory */
    $desc = [];
    $desc["location"] = $location;

    $desc['class'] = $params['indexer'];
    $desc['embedder'] = ['class' => $params['embedder']];
    $desc["max_docs"] = 20;
    $factory = "\Textualization\SemanticSearch\IndexFactory::make";
    if (! is_callable($factory)) {
        throw new Error("Unable to find $factory. You need to install the textualization/semantic-search composer package in your tiki for this plugin to work");
    }
    try {
        $index = call_user_func($factory, $desc);
    } catch (Exception $e) {
        $msg = "Unable to initialise the semantic search index.  Note that the search index requires permission to the index files, and access to ffo in php.ini.  The actual error is: ";
        return $msg . '<br/>' . $e;
    }

    $smarty = new Smarty_Tiki();
    $smarty->assign('id', $id);
    $query = $_REQUEST['query'];
    $smarty->assign('query', $query);
    $smarty->assign('facets', []);


    $searchFormContent = $smarty->fetch($wikitpl);

    $formHtml = '';
    $formHtml .= "<div id='semanticsearch_{$id}_form'>\n";
    $formHtml .= "<form id='semanticsearch_{$id}' class='customsearch_form'>\n";
    $formHtml .= $searchFormContent . "\n";
    $formHtml .= "</form'>\n";
    $formHtml .= "</div'>\n";
    $html .= $formHtml;

    $smartyResults = [];
    if ($query) {
        $results = $index->search($query);
        foreach ($results as $result) {
            //$html .= print_r($result, true);
            $smartyResult = [];
            //This is just so the template will parse
            $smartyResult['object_type'] = 'wiki page' ;
            //This is just so the template will parse
            $smartyResult['object_id'] = 'dummyid';

            $smartyResult['url'] = $result->url;
            $smartyResult['title'] = $result->title;
            //Scores are not usable currently for some reason
            //$smartyResult['score'] = sprintf('%.2f', $result->score);
            $chunkText = $index->fetch_document($result->url, $result->chunk_num)->text;
            //So we remove the title we know the textualization added at the begining of the first chunk
            $chunkTextNoTitle = str_replace($result->title, '', $chunkText);
            $smartyResult['highlight'] = substr($chunkTextNoTitle, 0, strrpos(substr($chunkTextNoTitle, 0, 300), ' '));
            $smartyResults[] = $smartyResult;
        }
    }
    $smarty->assign('results', $smartyResults);
    $html .= "<div id='semanticsearch_{$id}_results'  class='customsearch_results'>\n";
    $html .= $smarty->fetch($params['resultstpl']);
    $html .= "</div>";
    return $html;
}
