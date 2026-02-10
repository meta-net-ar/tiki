<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/****
 * Initially just a collection of the functions dotted around tiki-editpage.php for v4.0
 * Started in the edit_fixup experimental branch - jonnybradley Aug 2009
 *
 */

use Tiki\Lib\Diff\DiffUtils;
use Tiki\Lib\Diff\TextDiff;
use Tiki\Lib\Diff\Op\Add;
use Tiki\Lib\Diff\Op\Change;
use Tiki\Lib\Diff\Op\Copy;
use Tiki\WikiParser\Markdown\Converter\DefinitionListConverter;
use Tiki\WikiParser\Markdown\Converter\StrikeConverter;

class EditLib
{
    /**
     * @var int
     */
    public $oldsourceVersion;
    private $tracesOn = false;

    // Fields for translation related methods.
    public $sourcePageName = null;
    public $targetPageName = null;
    public $oldSourceVersion = null;
    public $newSourceVersion = null;

    // Fields for handling links to external wiki pages
    private $external_wikis = null;


    // general

    public function make_sure_page_to_be_created_is_not_an_alias($page, $page_info)
    {
        $access = TikiLib::lib('access');
        $tikilib = TikiLib::lib('tiki');
        $wikilib = TikiLib::lib('wiki');
        $semanticlib = TikiLib::lib('semantic');

        $aliases = $semanticlib->getAliasContaining($page, true);
        if (! $page_info && count($aliases) > 0) {
            $error_title = tra("Cannot create aliased page");
            $error_msg = tra("You attempted to create the following page:") . " " .
                         "<b>$page</b>.\n<p>\n";
            $error_msg .= tra("That page is an alias for the following pages") . ": ";
            foreach ($aliases as $an_alias) {
                $error_msg .= '<a href="' . $wikilib->editpage_url($an_alias['fromPage'], false) . '">' . $an_alias['fromPage'] . '</a>, ';
            }
            $error_msg .= "\n<p>\n";
            $error_msg .= tra("If you want to create the page, you must first edit each of the pages above to remove the alias link they may contain. This link should look something like this");
            $error_msg .= ": <b>(alias($page))</b>";

            $access->display_error($page, $error_title, "", true, $error_msg);
        }
    }

    public function user_needs_to_specify_language_of_page_to_be_created($page, $page_info, $new_page_inherited_attributes = null)
    {
        global $prefs;
        if (isset($_REQUEST['need_lang']) && $_REQUEST['need_lang'] == 'n') {
            return false;
        }
        if ($prefs['feature_multilingual'] == 'n') {
            return false;
        }
        if ($page_info && isset($page_info['lang']) && $page_info['lang'] != '') {
            return false;
        }
        if (isset($_REQUEST['lang']) && $_REQUEST['lang'] != '') {
            return false;
        }
        if (
            $new_page_inherited_attributes != null &&
            isset($new_page_inherited_attributes['lang']) &&
            $new_page_inherited_attributes['lang'] != ''
        ) {
            return false;
        }

        return true;
    }

    /**
     * Assigns custom tip message to Smarty template variables for display in remarksbox alert
     *
     * @param string $customTip The tip content to display in the alert box
     * @param string $customTipTitle Optional title for the alert box (defaults to 'Tip')
     * @return void
     */
    public function assignCustomTipToSmarty(string $customTip, string $customTipTitle = ''): void
    {
        if (trim($customTip) !== '') {
            $smarty = TikiLib::lib('smarty');
            $smarty->assign('customTip', $customTip);

            if (trim($customTipTitle) !== '') {
                $smarty->assign('customTipTitle', $customTipTitle);
            } else {
                $smarty->assign('customTipTitle', tr('Tip'));
            }
        }
    }

    // translation functions

    public function isTranslationMode()
    {
        return $this->isUpdateTranslationMode() || $this->isNewTranslationMode();
    }

    public function isNewTranslationMode()
    {
        global $prefs;

        if ($prefs['feature_multilingual'] != 'y') {
            return false;
        }
        if (
            isset($_REQUEST['translationOf'])
            && ! empty($_REQUEST['translationOf'])
        ) {
            return true;
        }
        if (
            isset($_REQUEST['is_new_translation'])
            && $_REQUEST['is_new_translation'] == 'y'
        ) {
            return true;
        }
        return false;
    }

    public function isUpdateTranslationMode()
    {
        return isset($_REQUEST['source_page'])
            && isset($_REQUEST['oldver'])
            && (! isset($_REQUEST['is_new_translation']) || $_REQUEST['is_new_translation'] == 'n')
            && isset($_REQUEST['newver']);
    }

    public function prepareTranslationData()
    {
        $this->setTranslationSourceAndTargetPageNames();
        $this->setTranslationSourceAndTargetVersions();
    }

    private function setTranslationSourceAndTargetPageNames()
    {
        $smarty = TikiLib::lib('smarty');

        if (! $this->isTranslationMode()) {
            return;
        }

        $this->targetPageName = null;
        if (isset($_REQUEST['target_page'])) {
            $this->targetPageName = $_REQUEST['target_page'];
        } elseif (isset($_REQUEST['page'])) {
            $this->targetPageName = $_REQUEST['page'];
        }
        $smarty->assign('target_page', $this->targetPageName);

        $this->sourcePageName = null;
        if (isset($_REQUEST['translationOf']) && $_REQUEST['translationOf']) {
            $this->sourcePageName = $_REQUEST['translationOf'];
        } elseif (isset($_REQUEST['source_page'])) {
            $this->sourcePageName = $_REQUEST['source_page'];
        }
        $smarty->assign('source_page', $this->sourcePageName);

        if ($this->isNewTranslationMode()) {
            $smarty->assign('translationIsNew', 'y');
        } else {
            $smarty->assign('translationIsNew', 'n');
        }
    }

    private function setTranslationSourceAndTargetVersions()
    {
        global $_REQUEST, $tikilib;
        // Note: -1 means a "virtual" empty version.
        $this->oldSourceVersion = $_REQUEST['oldver'] ?? -1;
        // Note: version number of 0 means the most recent version.
        $this->newSourceVersion = $_REQUEST['newver'] ?? 0;
    }

    public function aTranslationWasSavedAs($complete_or_partial)
    {
        if (
            ! $this->isTranslationMode() ||
            ! isset($_REQUEST['save'])
        ) {
            return false;
        }

        // We are saving a translation. Is it partial or complete?
        if ($complete_or_partial == 'complete' && isset($_REQUEST['partial_save'])) {
            return false;
        } elseif ($complete_or_partial == 'partial' && ! isset($_REQUEST['partial_save'])) {
            return false;
        }
        return true;
    }


    /**
     * Convert rgb() color definiton to hex color definiton
     *
     * @param unknown_type $col
     * @return The hex representation
     */
    public function parseColor(&$col)
    {

        if (preg_match("/^rgb\( *(\d+) *, *(\d+) *, *(\d+) *\)$/", $col, $parts)) {
            $hex = str_pad(dechex($parts[1]), 2, '0', STR_PAD_LEFT)
                 . str_pad(dechex($parts[2]), 2, '0', STR_PAD_LEFT)
                 . str_pad(dechex($parts[3]), 2, '0', STR_PAD_LEFT);
            $hex = '#' . TikiLib::strtoupper($hex);
        } else {
            $hex = $col;
        }

        return $hex;
    }


    /**
     * Utility for walk_and_parse to process links
     *
     * @param array $args the attributes of the link
     * @param array $text the link text
     * @param string $src output string
     * @param array $p ['stack'] = closing strings stack
     */
    private function parseLinkTag(&$args, &$text, &$src, &$p)
    {

        global $prefs;

        $link = '';
        $link_open = '';
        $link_close = '';

        /*
         * parse the link classes
         */
        $cl_wiki = false;
        $cl_wiki_page = false; // Wiki page
        $cl_ext_page = false; // external Wiki page
        $cl_external = false; // external web page
        $cl_semantic = ''; // semantic link
        if ($prefs['feature_semantic'] === 'y') {
            $semantic_tokens = TikiLib::lib('semantic')->getAllTokens();
        } else {
            $semantic_tokens = [];
        }

        $ext_wiki_name = '';

        if (isset($args['class']) && isset($args['href'])) {
            $matches = [];
            preg_match_all('/([^ ]+)/', $args['class']['value'], $matches);
            $classes = $matches[0];

            for ($i = 0, $count_classes = count($classes); $i < $count_classes; $i++) {
                $cl = $classes[$i];

                switch ($cl) {
                    case 'wiki':
                        $cl_wiki = true;
                        break;
                    case 'wiki_page':
                        $cl_wiki_page = true;
                        break;
                    case 'ext_page':
                        $cl_ext_page = true;
                        break;
                    case 'external':
                        $cl_external = true;
                        break;
                    default:
                        // if the preceding class was 'ext_page', then we have the name of the external Wiki
                        if ($i > 0 && $classes[$i - 1] == 'ext_page') {
                            $ext_wiki_name = $cl;
                        }
                        if (in_array($cl, $semantic_tokens)) {
                            $cl_semantic = $cl;
                        }
                }
            }
        }


        /*
         * extract the target and the anchor from the href
         */
        if (isset($args['href'])) {
            $href = urldecode($args['href']['value']);
            // replace pipe chars as that's the delimiter in a wiki/external link
            $href = str_replace('|', '%7C', $href);
            $matches = explode('#', $href);
            if (count($matches) == 2) {
                $target = $matches[0];
                $anchor = '#' . $matches[1];
            } else {
                $target = $href;
                $anchor = '';
            }
        } else {
            $target = '';
            $anchor = '';
        }


        /*
         * treat invalid external Wikis as web links
         */
        if ($cl_ext_page) {
            // retrieve the definitions from the database
            if ($this->external_wikis == null) {
                global $tikilib;
                $query = 'SELECT `name`, `extwiki` FROM `tiki_extwiki`';
                $this->external_wikis = $tikilib->fetchMap($query);
            }

            // name must be set and defined
            if (! $ext_wiki_name || ! isset($this->external_wikis[$ext_wiki_name])) {
                $cl_ext_page = false;
                $cl_wiki_page = false;
            }
        };


        /*
         * construct the links according to the defined classes
         */
        if ($cl_wiki_page) {
            /*
             * link to wiki page -> (( ))
             */
            $link_open = "($cl_semantic(";
            $link_close = '))';

            // remove the html part of the target
            $target = preg_replace('/tiki\-index\.php\?page\=/', '', $target);

            // construct the link
            $link = $target;
            if ($anchor) {
                $link .= '|' . $anchor;
            }
        } elseif ($cl_ext_page) {
            /*
             * link to external Wiki page ((:))
             */
            $link_open = '((';
            $link_close = '))';

            // remove the extwiki definition from the target
            $def = preg_replace('/\$page/', '', $this->external_wikis[$ext_wiki_name]);
            $def = preg_quote($def, '/');
            $target = preg_replace('/^' . $def . '/', '', $target);

            // construct the link
            $link = $ext_wiki_name . ':' . $target;
            if ($anchor) {
                $link .= '|' . $anchor;
            }
        } elseif ($cl_wiki && ! $cl_external && ! $target && strlen($anchor) > 0  && substr($anchor, 0, 1) == '#') {
            /*
             * inpage links [#]
             */
            $link_open = '[';
            $link_close = ']';

            // construct the link
            $link = $target = $anchor;
            $anchor = '';
        } elseif ($cl_wiki && ! $cl_external) {
            /*
             * other tiki resources []
             * -> articles, ...
             */
            $link_open = '[';
            $link_close = ']';

            // construct the link
            $link = $target;
        } elseif (! $cl_wiki && ! $cl_external && ! $text && isset($args['id']) && isset($args['id']['value'])) {
            /*
             * anchor
             */
             $link_open = '{ANAME()}';
             $link_close = '{ANAME}';
             $link = $args['id']['value'];
        } else {
            /*
             * other links []
             */
            $link_open = '[';
            $link_close = ']';


            /*
             * parse the rel attribute
             */
            $box = '';

            if (isset($args['class']) && isset($args['rel'])) {
                $matches = [];
                preg_match_all('/([^ ]+)/', $args['rel']['value'], $matches);
                $rels = $matches[0];

                for ($i = 0, $count_rels = count($rels); $i < $count_rels; $i++) {
                    $r = $rels[$i];

                    if (preg_match('/^box/', $r)) {
                        $box = $r;
                    }
                }
            }

            // construct the link
            $link = $target;
            if ($anchor) {
                $link .= $anchor;
            }
            // the box must be appended to the text
            if ($box) {
                $text .= '|' . $box;
            }
        } // convert links



        /*
         * flush the constructed link
         */
        if ($link_open && $link_close) {
            $p['wiki_lbr']++; // force wiki line break mode

            // does the link text match the target?
            if ($target == trim($text)) {
                $text = '';  // make sure walk_and_parse() does not append any text.
            } else {
                $link .= '|'; // the text will be appended by walk_and_parse()
            }

            // process the tag and update the output
            $this->processWikiTag('a', $src, $p, $link_open, $link_close, true);
            $src .= $link;
        }
    }


    /**
     * Utility for walk_and_parse to process p and div tags
     *
     * @param bool $isPar True if we process a <p>, false if a <div>
     * @param array $args the attributes of the tag
     * @param string $src output string
     * @param array $p ['stack'] = closing strings stack
     */
    private function parseParDivTag($isPar, &$args, &$src, &$p)
    {

        global $prefs;

        if (isset($args['style']) || isset($args['align'])) {
            $tag_name = $isPar ? 'p' : 'div'; // key for the $p[stack]
            $type = $isPar ? 'type="p", ' : ''; // used for {DIV()}

            $style = [];
            $this->parseStyleAttribute($args['style']['value'], $style);


            /*
             * convert 'align' to 'style' definitions
            */
            if (isset($args['align'])) {
                $style['text-align'] = $args['align']['value'];
            }


            /*
             * process all the defined styles
             */
            foreach (array_keys($style) as $format) {
                switch ($format) {
                    case 'text-align':
                        if ($style[$format] == 'left') {
                            $src .= "{DIV({$type}align=\"left\")}";
                            $p['stack'][] = ['tag' => $tag_name, 'string' => '{DIV}'];
                        } elseif ($style[$format] == 'center') {
                            $markup = ($prefs['feature_use_three_colon_centertag'] == 'y') ? ':::' : '::';
                            $this->processWikiTag($tag_name, $src, $p, $markup, $markup, false);
                        } elseif ($style[$format] == 'right') {
                            $src .= "{DIV({$type}align=\"right\")}";
                            $p['stack'][] = ['tag' => $tag_name, 'string' => '{DIV}'];
                        } elseif ($style[$format] == 'justify') {
                            $src .= "{DIV({$type}align=\"justify\")}";
                            $p['stack'][] = ['tag' => $tag_name, 'string' => '{DIV}'];
                        }
                        break;
                } // switch format
            } // foreach style
        }
    }


    /**
     * Utility for walk_and_parse to process span arguments
     *
     * @param array $args the attributes of the span
     * @param string $src output string
     * @param array $p ['stack'] = closing strings stack
     */
    private function parseSpanTag(&$args, &$src, &$p)
    {

        if (isset($args['style'])) {
            $style = [];
            $this->parseStyleAttribute($args['style']['value'], $style);

            $this->processWikiTag('span', $src, $p, '', '', true); // prepare the stacks, later we will append


            /*
             * The colors need to be handeled separatly; two style definitions become
             * one single wiki markup.
             */
            $fcol = '';
            $bcol = '';

            if (isset($style['color'])) {
                $fcol = $this->parseColor($style['color']);
                unset($style['color']);
            }
            if (isset($style['background-color'])) { // background: color-def have been converted to background-color
                $bcol = $this->parseColor($style['background-color']);
                unset($style['background-color']);
            }

            if ($fcol || $bcol) {
                $col  = "~~";
                $col .= ($fcol ? $fcol : ' ');
                $col .= ($bcol ? ',' . $bcol : '');
                $col .= ':';

                $this->processWikiTag('span', $src, $p, $col, '~~', true, true);
            }


            /*
             * Process the remaining format definitions
             */
            foreach (array_keys($style) as $format) {
                switch ($format) {
                    case 'font-weight':
                        if ($style[$format] == 'bold') {
                            $this->processWikiTag('span', $src, $p, '__', '__', true, true);
                        }
                        break;
                    case 'font-style':
                        if ($style[$format] == 'italic') {
                            $this->processWikiTag('span', $src, $p, '\'\'', '\'\'', true, true);
                        }
                        // no break
                    case 'text-decoration':
                        if ($style[$format] == 'line-through') {
                            $this->processWikiTag('span', $src, $p, '--', '--', true, true);
                        } elseif ($style[$format] == 'underline') {
                            $this->processWikiTag('span', $src, $p, '===', '===', true, true);
                        }
                        // no break
                } // switch format
            } // foreach style
        } // style
    }


    /**
     * Parse a html style definition into an array
     *
     * This method tries to expand the shorthand definitions, such as 'background:',
     * to the correspoinding key/value paris. If a definition is unknown, it is kept.
     *
     * @param string $style The value of the style attribute
     * @param array $parsed key/value pairs
     */
    public function parseStyleAttribute(&$style, &$parsed)
    {

        $matches = [];
        preg_match_all('/ *([^ :]+) *: *([^;]+) *;?/', $style ?? '', $matches);

        for ($i = 0, $count_matches = count($matches[0]); $i < $count_matches; $i++) {
            $key = $matches[1][$i];
            $value = trim($matches[2][$i]);

            /*
             * shortand list 'background:'
             * - set 'background-color'
             */
            if ($key == 'background') {
                $unprocessed = '';
                $shorthand = [];
                $this->parseStyleList($value, $shorthand);

                foreach ($shorthand as $s) {
                    switch ($s) {
                        case preg_match('/^#(\w{3,6})$/', $s) > 0:
                            $parsed['background-color'] = $s;
                            break;
                        case preg_match('/^rgb\(.*\)$/', $s) > 0:
                            $parsed['background-color'] = $s;
                            break;
                        default:
                            $unprocessed .= ' ' . $s;
                    }
                } // foreach shorthand

                // keep unprocessed list entries
                $value = trim($unprocessed);
            } // background:

            // save the result
            if ($value) {
                $parsed[$key] = $value;
            }
        } // style definitions
    }


    /**
     * Parse a space separated list of html styles
     *
     * Example: "rgb( 1, 2, 3) url(background.gif)"
     *
     * @param string $list List of styles
     * @param array $parsed The parsed list
     */
    public function parseStyleList(&$list, &$parsed)
    {

        $matches = [];
        preg_match_all('/(?:[[:graph:]]+\([^\)]*\))|(?:[^ ]+)/', $list, $matches);
        $parsed = $matches[0];
    }


    /**
     * Utility of walk_and_parse to process a wiki tag
     *
     * Wiki tags need a special treatment: In html, a tag may contain
     * several line breaks. In Wiki however, line breaks are often not allowed
     * and sometimes additional line breaks are required.
     *
     * This method saves Wiki tags an line break information in separate stacks.
     * These stackes are used in walk_and_parse to:
     * - Output the required markup before and after the linebreaks (<br />).
     * - To ensure that linebreaks are, if required, inserted at the correct place (\n).
     *
     * @param string $tag The name of the html tag
     * @param string $src Th Output string
     * @param array  $p   ['stack'] = closing strings stack,
     * @param string $begin The wiki markup that begins the tag
     * @param string $end The wiki markup that ends the tag
     * @param bool $is_inline True if the tag is inline, false if the tag must span exacly one line.
     * @param bool $append True = append to the topmost element on the stack, false = create a new element on the stack
     */
    private function processWikiTag($tag, &$src, &$p, $begin, $end, $is_inline, $append = false)
    {

        // append=false, create new entries on the stack
        if (! $append) {
            $p['stack'][] = ['tag' => $tag, 'string' => '', 'wikitags' => 0 ];
            $p['wikistack'][] = [ 'begin' => [], 'end' => [] ];
        };

        // get the entry points on the stacks
        $keys = array_keys($p['wikistack']);
        $key = end($keys);
        $wiki = &$p['wikistack'][$key];

        $keys = array_keys($p['stack']);
        $key = end($keys);
        $stack = &$p['stack'][$key];
        $string = &$stack['string'];

        // append to the stacks
        $wiki['begin'][] = $begin;
        $wiki['end'][] = $end;
        $string = $end . $string;
        $stack['wikitags']++;

        // update the output string
        if (! $is_inline) {
            $this->startNewLine($src);
        }
        $src .= $begin;
    }


    public function saveCompleteTranslation()
    {
        $multilinguallib = TikiLib::lib('multilingual');
        $tikilib = TikiLib::lib('tiki');

        $sourceInfo = $tikilib->get_page_info($this->sourcePageName);
        $targetInfo = $tikilib->get_page_info($this->targetPageName);

        $multilinguallib->propagateTranslationBits(
            'wiki page',
            $sourceInfo['page_id'],
            $targetInfo['page_id'],
            $sourceInfo['version'],
            $targetInfo['version']
        );
        $multilinguallib->deleteTranslationInProgressFlags($targetInfo['page_id'], $sourceInfo['lang']);
    }

    public function savePartialTranslation()
    {
        $tikilib = TikiLib::lib('tiki');
        $multilinguallib = TikiLib::lib('multilingual');

        $sourceInfo = $tikilib->get_page_info($this->sourcePageName);
        $targetInfo = $tikilib->get_page_info($this->targetPageName);

        $multilinguallib->addTranslationInProgressFlags($targetInfo['page_id'], $sourceInfo['lang']);
    }

    /**
     * Function to take html from the wysiwyg editor and parse back to wiki markup
     * Used by "switch editor" and when saving in wysiwyg_htmltowiki mode
     * When saving in mixed "html" mode the "unparsing" is done in JavaScript client-side
     *
     * @param $inData string    editor content
     * @return string           wiki markup
     */

    public function parseToWiki($inData)
    {

        global $prefs;

        $parsed = $this->partialParseWysiwygToWiki($inData);    // remove cke type plugin wrappers

        $parsed = html_entity_decode($parsed, ENT_QUOTES, 'UTF-8');
        $parsed = preg_replace('/\t/', '', $parsed); // remove all tabs inserted by the CKE

        $parsed = preg_replace_callback('/<pre class=["\']tiki_plugin["\']>(.*?)<\/pre>/ims', [$this, 'parseToWikiPlugin'], $parsed);   // rempve plugin wrappers

        $parsed = $this->parse_html($parsed);
        $parsed = preg_replace('/\{img\(? src=.*?img\/smiles\/icon_([\w\-]*?)\..*\}/im', '(:$1:)', $parsed);    // "unfix" smilies
        $parsed = preg_replace('/&nbsp;/m', ' ', $parsed);                                              // spaces
        $parsed = preg_replace('/!(?:\d\.)+/', '!#', $parsed); // numbered headings
        if ($prefs['feature_use_three_colon_centertag'] == 'y') { // numbered and centerd headings
            $parsed = preg_replace('/!:::(?:\d\.)+ *(.*):::/', '!#:::\1:::', $parsed);
        } else {
            $parsed = preg_replace('/!::(?:\d\.)+ *(.*)::/', '!#::\1::', $parsed);
        }

        // remove empty center tags
        if ($prefs['feature_use_three_colon_centertag'] == 'y') { // numbered and centerd headings
            $parsed = preg_replace('/::: *:::\n/', '', $parsed);
        } else {
            $parsed = preg_replace('/:: *::\n/', '', $parsed);
        }

        // Put back htmlentities as normal char
        $parsed = htmlspecialchars_decode($parsed, ENT_QUOTES);
        return $parsed;
    }

    public function parseToWikiPlugin($matches)
    {
        if (count($matches) > 1) {
            return nl2br($matches[1]);
        }
    }

    /**
     * Render html to send to the wysiwyg editor, including parsing plugins for wysiwyg editing
     * From both wiki page source (for wysiwyg_htmltowiki) and "html" modes
     *
     * @param $inData string    page data, can be wiki or mixed html/wiki
     * @param bool $fromWiki    set if converting from wiki page using "switch editor"
     * @param bool $isHtml      true if $inData is HTML, false if wiki
     * @return string           html to send to wysiwyg editor
     */

    public function parseToWysiwyg($inData, $fromWiki = false, $isHtml = false, $options = [])
    {
        global $tikiroot, $prefs;

        $allowImageLazyLoad = $prefs['allowImageLazyLoad'];
        $prefs['allowImageLazyLoad'] = 'n';

        // Parsing page data for wysiwyg editor
        $inData = $this->partialParseWysiwygToWiki($inData);    // remove any wysiwyg plugins so they don't get double parsed
        $parsed = preg_replace('/(!!*)[\+\-]/m', '$1', $inData);        // remove show/hide headings
        $parsed = preg_replace('/&#039;/', '\'', $parsed);          // catch single quotes at html entities

        if (preg_match('/^\|\|.*\|\|$/', $parsed)) {    // new tables get newlines converted to <br> then to %%%
            $parsed = str_replace('<br>', "\n", $parsed);
        }


        $parsed = TikiLib::lib('parser')->parse_data(
            $parsed,
            array_merge([
                'absolute_links' => true,
                'noheaderinc' => true,
                'suppress_icons' => true,
                'wysiwyg' => true,
                'is_html' => ($isHtml && ! $fromWiki),
                'process_wiki_paragraphs' => (! $isHtml || $fromWiki),
                'process_double_brackets' => 'y'
            ], $options)
        );

        if ($fromWiki) {
            $parsed = preg_replace('/^\s*<p>&nbsp;[\s]*<\/p>\s*/iu', '', $parsed);                      // remove added empty <p>
        }
        $parsed = preg_replace('/<span class=\"img\">(.*?)<\/span>/im', '$1', $parsed);                 // remove spans round img's
        // Workaround Wysiwyg Image Plugin Editor in IE7 erases image on insert http://dev.tiki.org/item3615
        $parsed2 = preg_replace('/(<span class=\"tiki_plugin\".*?plugin=\"img\".*?><\/span>)<\/p>/is', '$1<span>&nbsp;</span></p>', $parsed);
        if ($parsed2 !== null) {
            $parsed = $parsed2;
        }
        // Fix IE7 wysiwyg editor always adding absolute path
        if (isset($_SERVER['HTTP_HOST'])) {
            $search = '/(<a[^>]+href=\")https?\:\/\/' . preg_quote($_SERVER['HTTP_HOST'] . $tikiroot, '/') . '([^>]+_cke_saved_href)/i';
        } else {
            $search = '/(<a[^>]+href=\")https?\:\/\/' . preg_quote($_SERVER['SERVER_NAME'] . $tikiroot, '/') . '([^>]+_cke_saved_href)/i';
        }
        $parsed = preg_replace($search, '$1$2', $parsed);

        if (! $isHtml) {
            // Fix for plugin being the last item in a page making it impossible to add new lines (new text ends up inside the plugin)
            $parsed = preg_replace('/<!-- end tiki_plugin --><\/(span|div)>(<\/p>)?$/', '<!-- end tiki_plugin --></$1>&nbsp;$2', $parsed);
            // also if first
            $parsed = preg_replace('/^<(div|span) class="tiki_plugin"/', '&nbsp;<$1 class="tiki_plugin"', $parsed);
        }

        $prefs['allowImageLazyLoad'] = $allowImageLazyLoad;

        return $parsed;
    }

    /**
     * Converts wysiwyg plugins into wiki.
     * Also processes headings by removing surrounding <p>
     * Also used by ajax preview in Services_Edit_Controller
     *
     * @param string $inData    page data - mostly html but can have a bit of wiki in it
     * @return string           html with wiki plugins
     */

    public static function partialParseWysiwygToWiki($inData)
    {
        if (empty($inData)) {
            return '';
        }
        // de-protect ck_protected comments
        $ret = preg_replace('/<!--{cke_protected}{C}%3C!%2D%2D%20end%20tiki_plugin%20%2D%2D%3E-->/i', '<!-- end tiki_plugin -->', $inData);
        if (! $ret) {
            $ret = $inData;
            trigger_error(tr('Parse To Wiki %0: preg_replace error #%1', 1, preg_last_error()));
        }
        // remove the wysiwyg plugin elements leaving the syntax only remaining
        $ret2 = preg_replace('/<(?:div|span)[^>]*syntax="(.*)".*end tiki_plugin --><\/(?:div|span)>/Umis', "$1", $ret);
        // preg_replace blows up here with a PREG_BACKTRACK_LIMIT_ERROR on pages with "corrupted" plugins
        if (! $ret2) {
            trigger_error(tr('Parse To Wiki %0: preg_replace error #%1', 2, preg_last_error()));
        } else {
            $ret = $ret2;
        }

        // take away the <p> that f/ck introduces around wiki heading ! to have maketoc/edit section working
        $ret2 = preg_replace('/<p>\s*!(.*)<\/p>/Umis', "!$1\n", $ret);
        if (! $ret2) {
            trigger_error(tr('Parse To Wiki %0: preg_replace error #%1', 3, preg_last_error()));
        } else {
            $ret = $ret2;
        }

        // strip the last empty <p> tag generated somewhere (ckeditor 3.6, Tiki 10)
        $ret2 = preg_replace('/\s*<p>\s*<\/p>\s*$/Umis', "$1\n", $ret);
        if (! $ret2) {
            trigger_error(tr('Parse To Wiki %0: preg_replace error #%1', 4, preg_last_error()));
        } else {
            $ret = $ret2;
        }

        // convert tikicomment tags back to ~tc~tiki comments~/tc~
        $ret2 = preg_replace('/<tikicomment>(.*)<\/tikicomment>/Umis', '~tc~$1~/tc~', $ret);
        if (! $ret2) {
            trigger_error(tr('Parse To Wiki %0: preg_replace error #%1', 5, preg_last_error()));
        } else {
            $ret = $ret2;
        }

        return $ret;
    }

    // parse HTML functions

    /**
     * \brief Parsed HTML tree walker (used by HTML sucker)
     *
     * This is initial implementation (stupid... w/o any intellegence (almost :))
     * It is rapidly designed version... just for test: 'can this feature be useful'.
     * Later it should be replaced by well designed one :) don't bash me now :)
     *
     * \param &$c array -- parsed HTML
     * \param &$src string -- output string
     * \param &$p array -- ['stack'] = closing strings stack,
                           ['listack'] = stack of list types currently opened
                           ['first_td'] = flag: 'is <tr> was just before this <td>'
                           ['first_tr'] = flag: 'is <table> was just before this <tr>'
     */
    public function walk_and_parse(&$c, &$src, &$p, $head_url)
    {
        global $prefs;
        // If no string
        if (! $c) {
            return;
        }
        $parserlib = TikiLib::lib('parser');

        $styledTags = [];

        for ($i = 0; $i <= $c['contentpos']; $i++) {
            $node = $c[$i];

            // file reference content
            if (! empty($node['pars']['data-file-ref-id'])) {
                // skip the following text node as we only need the id
                $i++;
                $src .= '((' . $node['pars']['data-file-ref-id']['value'] . '|file))';
            }

            // If content type 'text' output it to destination...
            if ($node['type'] == 'text') {
                if (! ctype_space($node['data'])) {
                    $add = $node['data'];
                    $noparsed = [];
                    $parserlib->plugins_remove($add, $noparsed);
                    $add = str_replace(["\r","\n"], '', $add);
                    $add = str_replace('&nbsp;', ' ', $add);
                    $add = str_replace('[', '[[', $add);            // escape square brackets to prevent accidental wiki links
                    $parserlib->plugins_replace($add, $noparsed, true);
                    $src .= $add;
                } else {
                    $src .= str_replace(["\n", "\r"], '', $node['data']);   // keep the spaces
                }
            } elseif ($node['type'] == 'comment') {
                $src .= preg_replace('/<!--/', "\n~hc~", preg_replace('/-->/', "~/hc~\n", $node['data']));
            } elseif ($node['type'] == 'tag') {
                $sameStyledTag = array_filter($styledTags, function ($tag) use ($node) {
                    return $tag['name'] === $node['data']['name'];
                });

                if ($node['data']['type'] == 'open') {
                    // Open tag type
                    $style = $node['pars']['style']['value'] ?? '';
                    $styleAttrs = array_filter(explode(';', $style), function ($attr) {
                        return ! empty(trim($attr));
                    });
                    // When Tiki syntax can't handle the current style attributes
                    if (count($styleAttrs)) {
                        $cssAttrs = array_map(function ($attr) {
                            return trim(explode(':', $attr)[0]);
                        }, $styleAttrs);
                        $tikiStyleAttrs = [
                            'text-align', 'font-weight', 'font-style', 'text-decoration',
                            'color', 'background-color', 'background'
                        ];

                        $givenCssAttrsHandled = array_intersect($tikiStyleAttrs, $cssAttrs);
                        $cssAttrsNotHandled = array_diff($cssAttrs, $tikiStyleAttrs);
                        if (count($givenCssAttrsHandled) === count($cssAttrs) && count($cssAttrsNotHandled) === 0) {
                            $style = '';
                        }
                    }
                    if ($style && ! in_array($node['data']['name'], ['img', 'a'])) {
                        $tagName = $node['data']['name'];
                        $src .= '{DIV(type="' . $tagName . '" style="' . $style . '")}' . "\n";
                        if (empty($sameStyledTag)) {
                            $styledTags[] = [
                                'name' => $node['data']['name'],
                                'sameInnerUnStyledCount' => 0,
                            ];
                        }
                        continue;
                    } else {
                        if (count($sameStyledTag)) {
                            $styledTags[0]['sameInnerUnStyledCount']++;
                        }
                    }

                    // deal with plugins - could be either span of div so process before the switch statement
                    if (isset($node['pars']['plugin']) && isset($node['pars']['syntax'])) { // handling for tiki plugins
                        $src .= html_entity_decode($node['pars']['syntax']['value']);
                        $more_spans = 1;
                        $elem_type = $node['data']['name'];
                        $other_elements = 0;
                        $j = $i + 1;
                        while ($j < $c['contentpos']) { // loop through contents of this span and discard everything
                            if ($c[$j]['data']['name'] == $elem_type && $c[$j]['data']['type'] == 'close') {
                                $more_spans--;
                                if ($more_spans === 0) {
                                    break;
                                }
                            } elseif ($c[$j]['data']['name'] == 'br' && $more_spans === 1 && $other_elements === 0) {
                            } elseif ($c[$j]['data']['name'] == $elem_type && $c[$j]['data']['type'] == 'open') {
                                $more_spans++;
                            } elseif ($c[$j]['data']['type'] == 'open' && $c[$j]['data']['name'] != 'br' && $c[$j]['data']['name'] != 'img' && $c[$j]['data']['name'] != 'input') {
                                $other_elements++;
                            } elseif ($c[$j]['data']['type'] == 'close') {
                                $other_elements--;
                            }
                            $j++;
                        }
                        $i = $j;    // skip everything that was inside this span
                    }

                    $isPar = false; // assuming "div" when calling parseParDivTag()

                    switch ($node['data']['name']) {
                        // Tags we don't want at all.
                        case 'meta':
                        case 'link':
                            $node['content'] = '';
                            break;
                        case 'script':
                            $node['content'] = '';
                            if (! isset($node['pars']['src'])) {
                                $i++;
                                while ($node['type'] === 'text' || ($node['data']['name'] !== 'script' && $node['data']['type'] !== 'close' && $i <= $c['contentpos'])) {
                                    $i++;    // skip contents of script tag
                                }
                            }
                            break;
                        case 'style':
                            $node['content'] = '';
                            $i++;
                            while ($node['data']['name'] !== 'style' && $node['data']['type'] !== 'close' && $i <= $c['contentpos']) {
                                $i++;    // skip contents of script tag
                            }
                            break;

                        // others we do want
                        case 'br':
                            if ($p['wiki_lbr']) { // "%%%" or "\n" ?
                                $src .= ' %%% ';
                            } else {
                                // close all wiki tags
                                foreach (array_reverse($p['wikistack']) as $wiki_arr) {
                                    foreach (array_reverse($wiki_arr['end']) as $end) {
                                        $src .= $end;
                                    }
                                }
                                $src .= "\n";

                                // for lists, we must prepend '+' to keep the indentation
                                if ($p['listack']) {
                                    $src .= str_repeat('+', count($p['listack']));
                                }

                                // reopen all wikitags
                                foreach ($p['wikistack'] as $wiki_arr) {
                                    foreach ($wiki_arr['begin'] as $begin) {
                                        $src .= $begin;
                                    }
                                }
                            }
                            break;
                        case 'hr':
                            $src .= $this->startNewLine($src) . '---';
                            break;
                        case 'title':
                            $src .= "\n!";
                            $p['stack'][] = ['tag' => 'title', 'string' => "\n"];
                            break;
                        case 'p':
                            $isPar = true;
                            if ($src && $prefs['feature_wiki_paragraph_formatting'] !== 'y') {
                                $src .= "\n";
                            }
                            // no break
                        case 'div': // Wiki parsing creates divs for center
                            if (isset($node['pars'])) {
                                $this->parseParDivTag($isPar, $node['pars'], $src, $p);
                            } elseif (! empty($p['table'])) {
                                $src .= '%%%';
                            } else {    // normal para or div
                                $src .= $this->startNewLine($src);
                                $p['stack'][] = ['tag' => $node['data']['name'], 'string' => "\n\n"];
                            }
                            break;
                        case 'span':
                            if (isset($node['pars'])) {
                                $this->parseSpanTag($node['pars'], $src, $p);
                            }
                            break;
                        case 'b':
                            $this->processWikiTag('b', $src, $p, '__', '__', true);
                            break;
                        case 'i':
                            $this->processWikiTag('i', $src, $p, '\'\'', '\'\'', true);
                            break;
                        case 'em':
                            $this->processWikiTag('em', $src, $p, '\'\'', '\'\'', true);
                            break;
                        case 'strong':
                            $this->processWikiTag('strong', $src, $p, '__', '__', true);
                            break;
                        case 'u':
                            $this->processWikiTag('u', $src, $p, '===', '===', true);
                            break;
                        case 'strike':
                            $this->processWikiTag('strike', $src, $p, '--', '--', true);
                            break;
                        case 'del':
                            $this->processWikiTag('del', $src, $p, '--', '--', true);
                            break;
                        case 'center':
                            if ($prefs['feature_use_three_colon_centertag'] == 'y') {
                                $src .= ':::';
                                $p['stack'][] = ['tag' => 'center', 'string' => ':::'];
                            } else {
                                $src .= '::';
                                $p['stack'][] = ['tag' => 'center', 'string' => '::'];
                            }
                            break;
                        case 'code':
                            $src .= '-+';
                            $p['stack'][] = ['tag' => 'code', 'string' => '+-'];
                            break;
                        case 'dd':
                            $src .= ':';
                            $p['stack'][] = ['tag' => 'dd', 'string' => "\n"];
                            break;
                        case 'dt':
                            $src .= ';';
                            $p['stack'][] = ['tag' => 'dt', 'string' => ''];
                            break;

                        case 'h1':
                        case 'h2':
                        case 'h3':
                        case 'h4':
                        case 'h5':
                        case 'h6':
                            $p['wiki_lbr']++; // force wiki line break mode
                            $hlevel = (int) $node['data']['name'][1];
                            if (isset($node['pars']['style']['value']) && strpos($node['pars']['style']['value'], 'text-align: center;') !== false) {
                                if ($prefs['feature_use_three_colon_centertag'] == 'y') {
                                    $src .= $this->startNewLine($src) . str_repeat('!', $hlevel) . ':::';
                                    $p['stack'][] = ['tag' => $node['data']['name'], 'string' => ":::\n"];
                                } else {
                                    $src .= $this->startNewLine($src) . str_repeat('!', $hlevel) . '::';
                                    $p['stack'][] = ['tag' => $node['data']['name'], 'string' => "::\n"];
                                }
                            } else {    // normal para or div
                                $src .= $this->startNewLine($src) . str_repeat('!', $hlevel);
                                $p['stack'][] = ['tag' => $node['data']['name'], 'string' => "\n"];
                            }
                            break;
                        case 'pre':
                            $src .= "~pre~\n";
                            $p['stack'][] = ['tag' => 'pre', 'string' => "~/pre~\n"];
                            break;
                        case 'sub':
                            $src .= '{SUB()}';
                            $p['stack'][] = ['tag' => 'sub', 'string' => '{SUB}'];
                            break;
                        case 'sup':
                            $src .= '{SUP()}';
                            $p['stack'][] = ['tag' => 'sup', 'string' => '{SUP}'];
                            break;
                        case 'tt':
                            $src .= '{DIV(type="tt")}';
                            $p['stack'][] = ['tag' => 'tt', 'string' => '{DIV}'];
                            break;
                        case 's':
                            $src .= $this->processWikiTag('s', $src, $p, '--', '--', true);
                            break;
                        // Table parser
                        case 'table':
                            $src .= $this->startNewLine($src) . '||';
                            $p['stack'][] = ['tag' => 'table', 'string' => '||'];
                            $p['first_tr'] = true;
                            $p['table'] = true;
                            break;
                        case 'tr':
                            if (! $p['first_tr']) {
                                $this->startNewLine($src);
                            }
                            $p['first_tr'] = false;
                            $p['first_td'] = true;
                            $p['wiki_lbr']++; // force wiki line break mode
                            break;
                        case 'td':
                            if ($p['first_td']) {
                                $src .= '';
                            } else {
                                $src .= '|';
                            }
                            $p['first_td'] = false;
                            break;
                        // Lists parser
                        case 'ul':
                            $p['listack'][] = '*';
                            break;
                        case 'ol':
                            $p['listack'][] = '#';
                            break;
                        case 'li':
                            // Generate wiki list item according to current list depth.
                            $src .= $this->startNewLine($src) . str_repeat(end($p['listack']), count($p['listack']));
                            break;
                        case 'font':
                            // If color attribute present in <font> tag
                            if (isset($node['pars']['color']['value'])) {
                                $src .= '~~' . $node['pars']['color']['value'] . ':';
                                $p['stack'][] = ['tag' => 'font', 'string' => '~~'];
                            }
                            break;
                        case 'img':
                            // If src attribute present in <img> tag
                            if (isset($node['pars']['src']['value'])) {
                                // Note what it produce (img) not {img}! Will fix this below...
                                if (str_contains($node['pars']['src']['value'], 'http:')) {
                                    $src .= '{img src="' . $node['pars']['src']['value'] . '"}';
                                } else {
                                    $src .= '{img src="' . $head_url . $node['pars']['src']['value'] . '"}';
                                }
                            }
                            break;
                        case 'a':
                            if (isset($node['pars'])) {
                                // get the link text
                                $text = '';
                                if ($i < count($c)) {
                                    $next_token = &$c[$i + 1];
                                    if (isset($next_token['type']) && $next_token['type'] == 'text' && isset($next_token['data'])) {
                                        $text = &$next_token['data'];
                                    }
                                }
                                // parse the link
                                $this->parseLinkTag($node['pars'], $text, $src, $p);
                            }
                            break;
                    }   // end switch on tag name
                } else {
                    if (count($sameStyledTag)) {
                        if ($styledTags[0]['sameInnerUnStyledCount']) {
                            $styledTags[0]['sameInnerUnStyledCount']--;
                        } else {
                            $src .= '{DIV}';
                        }
                    }
                    // This is close tag type. Is that smth we r waiting for?
                    switch ($node['data']['name']) {
                        case 'ul':
                            if (end($p['listack']) == '*') {
                                array_pop($p['listack']);
                            }
                            if (empty($p['listack'])) {
                                $src .= "\n";
                            }
                            break;
                        case 'ol':
                            if (end($p['listack']) == '#') {
                                array_pop($p['listack']);
                            }
                            if (empty($p['listack'])) {
                                $src .= "\n";
                            }
                            break;
                        default:
                            $e = end($p['stack']);
                            if (isset($e['tag']) && $node['data']['name'] == $e['tag']) {
                                $src .= $e['string'];
                                array_pop($p['stack']);
                            }
                            if ($node['data']['name'] === 'table') {
                                $p['table'] = false;
                            }
                            break;
                    }

                    // update the wiki stack
                    if (isset($e['wikitags']) && $e['wikitags']) {
                        for ($i_wiki = 0; $i_wiki < $e['wikitags']; $i_wiki++) {
                            array_pop($p['wikistack']);
                        }
                    }

                    // can we leave wiki line break mode ?
                    switch ($node['data']['name']) {
                        case 'a':
                        case 'h1':
                        case 'h2':
                        case 'h3':
                        case 'h4':
                        case 'h5':
                        case 'h6':
                        case 'tr':
                            $p['wiki_lbr']--;
                            break;
                    }
                }
            }
            // Recursive call on tags with content...
            if (! empty($node['content'])) {
                if (! str_ends_with($src, ' ')) {
                    $src .= ' ';
                }
                $this->walk_and_parse($node['content'], $src, $p, $head_url);
            }
        }
        if (str_ends_with($src, "\n\n")) {   // seem to always get too many line ends
            $src = substr($src, 0, -2);
        }
    }

    public function startNewLine(&$str)
    {
        if (strlen($str) && ! str_ends_with($str, "\n")) {
            $str .= "\n";
        }
    }

    /**
     * wrapper around zaufi's HTML sucker code just to use the html to wiki bit
     *
     * @param string $inHtml -- HTML in
     * @return null|string
     * @throws Exception
     */


    public function parse_html(&$inHtml)
    {
        require_once(__DIR__ . '/../../src/php/external_lib_sources/htmlparser/htmlparser.php');
        // Read compiled (serialized) grammar
        $grammarfile = __DIR__ . '/../../src/php/external_lib_sources/htmlparser/htmlgrammar.cmp';
        if (! $fp = @fopen($grammarfile, 'r')) {
            Feedback::errorAndDie(tra("Can't parse HTML data - no grammar file"), \Laminas\Http\Response::STATUS_CODE_409);
        }
        $grammar = unserialize(fread($fp, filesize($grammarfile)));
        fclose($fp);

        // process a few html editor artifacts
        $inHtml = str_replace('<p></p>', '', $inHtml);  // empty p tags are invisible

        // Fix nested quotes in HTML attributes to prevent parser confusion
        // The legacy HTML parser has issues with nested quotes in attributes like:
        // style="font-family: -apple-system, &quot;system-ui&quot;, &quot;Segoe UI&quot;""
        // This preprocessing step converts HTML entities to characters and normalizes nested quotes
        $inHtml = $this->fixNestedQuotesInAttributes($inHtml);

        // create parser object, insert html code and parse it
        $htmlparser = new HtmlParser($inHtml, $grammar, '', 0);
        $htmlparser->Parse();
        // Should I try to convert HTML to wiki?
        $out_data = '';
        /*
         * ['stack'] = array
         * Speacial keys introduced to convert to Wiki
         * - ['wikitags']     = the number of 'wikistack' entries produced by the html tag
         *
         * ['wikistack'] = array(), is used to save the wiki markup for the linebreak handling (1 array = 1 html tag)
         * Each array entry contains the following keys:
         * - ['begin']        = array() of begin markups (1 style definition = 1 array entry)
         * - ['end']          = array() of end markups
         *
         * wiki_lbr  = true if we must use '%%%' for linebreaks instead of '\n'
         */
        $p = ['stack' => [], 'listack' => [], 'wikistack' => [],
            'wiki_lbr' => 0, 'first_td' => false, 'first_tr' => false];
        $this->walk_and_parse($htmlparser->content, $out_data, $p, '');
        // Is some tags still opened? (It can be if HTML not valid, but this is not reason
        // to produce invalid wiki :)
        while (count($p['stack'])) {
            $e = end($p['stack']);
            $out_data .= $e['string'];
            array_pop($p['stack']);
        }
        // Unclosed lists r ignored... wiki have no special start/end lists syntax....
        // OK. Things remains to do:
        // 1) fix linked images
        $out_data = preg_replace(',\[(.*)\|\(img src=(.*)\)\],mU', '{img src=$2 link=$1}', $out_data);
        // 2) fix remains images (not in links)
        $out_data = preg_replace(',\(img src=(.*)\),mU', '{img src=$1}', $out_data);
        // 3) remove empty lines
        $out_data = preg_replace(",[\n]+,mU", "\n", $out_data);
        // 4) remove nbsp's
        $out_data = preg_replace(",&#160;,mU", " ", $out_data);

        return $out_data;
    }


    public function get_new_page_attributes_from_parent_pages($page, $page_info)
    {
        $tikilib = TikiLib::lib('tiki');
        $wikilib = TikiLib::lib('wiki');

        $new_page_attrs = [];
        $parent_pages = $wikilib->get_parent_pages($page);
        $parent_pages_info = [];
        foreach ($parent_pages as $a_parent_page_name) {
            $this_parent_page_info = $tikilib->get_page_info($a_parent_page_name);
            $parent_pages_info[] = $this_parent_page_info;
        }
        $new_page_attrs = $this->get_newpage_language_from_parent_page($page, $page_info, $parent_pages_info, $new_page_attrs);
        // Note: in the future, may add some methods below to guess things like
        //       categories, workspaces, etc...

        return $new_page_attrs;
    }

    public function get_newpage_language_from_parent_page($page, $page_info, $parent_pages_info, $new_page_attrs)
    {
        if (! isset($page_info['lang'])) {
            $lang = null;
            foreach ($parent_pages_info as $this_parent_page_info) {
                if (isset($this_parent_page_info['lang'])) {
                    if ($lang != null and $lang != $this_parent_page_info['lang']) {
                        // If more than one parent pages and they have different languages
                        // then we can't guess which  is the right one.
                        $lang = null;
                        break;
                    } else {
                        $lang = $this_parent_page_info['lang'];
                    }
                }
            }
            if ($lang != null) {
                $new_page_attrs['lang'] = $lang;
            }
        }
        return $new_page_attrs;
    }

    /**
     * Bound to tiki.save to find and notifiy users of any mentions
     *
     * @param array $args
     *
     * @throws Exception
     */
    final public function process_mentions(array $arguments): void
    {
        global $prefs;

        // Notify users/usergroups
        if ($prefs['feature_notify_users_mention'] === 'y' && $prefs['feature_tag_users'] === 'y') {
            if ($arguments['type'] === 'wiki page') {
                // Wiki page comment
                if (isset($arguments['content'])) {
                    $oldData = '';
                    $newData = $arguments['content'] ?? '';
                } else {
                    $oldData = $arguments['old_data'] ?? '';
                    $newData = $arguments['data'] ?? '';
                }
            } elseif ($arguments['type'] === 'trackeritem' && isset($arguments['values_by_permname'])) {
                $oldData = implode("\n", array_values(array_diff($arguments['old_values_by_permname'], $arguments['values_by_permname'])));
                $newData = implode("\n", array_values(array_diff($arguments['values_by_permname'], $arguments['old_values_by_permname'])));
            } elseif (isset($arguments['content'])) {   //  'forum post', 'blog post', comments
                $oldData = '';
                $newData = $arguments['content'] ?? '';
            } else {
                $oldData = '';
                $newData = '';
            }

            if ($oldData || $newData) {
                $oldData = explode("\n", $oldData);
                $newData = explode("\n", $newData);
                $sections = [];
                $textDiff = new TextDiff($oldData, $newData, true);
                if (! $textDiff->isEmpty()) {
                    foreach ($textDiff->edits as $edit) {
                        if (is_a($edit, Add::class)) { // new content
                            $sections[] = DiffUtils::findMentions($edit->final, 'new');
                        } elseif (is_a($edit, Change::class)) { // change or new content
                            $sections[] = DiffUtils::findMentionsOnChange($edit);
                        } elseif (is_a($edit, Copy::class)) { // no diffs on content
                            $sections[] = DiffUtils::findMentions($edit->final, 'old');
                        }
                    }
                }
                $count = 1;
                $tikiLibUser = TikiLib::lib('user');
                $smarty = TikiLib::lib('smarty');

                $objectUrl = smarty_function_object_link(
                    ['type' => $arguments['type'],  'id' => $arguments['object']],
                    $smarty->getEmptyInternalTemplate()
                );

                // strip out html to get the plain url and title?
                if (preg_match('/<a.* href=["\'](.*?)["\'].*>(.*?)<\/a>/', $objectUrl, $matches)) {
                    $objectUrl = $matches[1];
                    $title = html_entity_decode($matches[2], ENT_QUOTES);
                }

                $mentions = [];
                // group all mentions by user
                foreach ($sections as $sec) {
                    foreach ($sec as $key => $possibleUser) {
                        $is_user = false;
                        $mentionedUser = substr($possibleUser['mention'], strpos($possibleUser['mention'], "@") + 1);
                        if ($mentionedUser) {
                            // check if the user exists
                            $userInfo = $tikiLibUser->get_user_info($mentionedUser);
                            if (is_array($userInfo) && $userInfo['userId'] > 0) {
                                $is_user = true;
                            }
                        }

                        if ($is_user) {
                            // check if it's a new mention and not a self-mentioned user, add to new array
                            if ($possibleUser['state'] === 'new' && $arguments['user'] !== $mentionedUser) {
                                // for each mention of user, indicate its exact position
                                $mentions[$mentionedUser][] = $count;
                            }
                            // increment the number of mentions, if it is a new mention and if the user exists, or if it is an old mention
                            $count++;
                        }
                    }
                }

                // send notifications grouping all mentions by user, instead of sending one notification for each mention
                foreach ($mentions as $mentionedUser => $mention) {
                    $mentionedBy = $tikiLibUser->clean_user($arguments['user']);    // gets realName
                    $userInfo = $tikiLibUser->get_user_info($mentionedUser);
                    $listUrls = [];
                    foreach ($mention as $position) {
                        $listUrls[] = $objectUrl . '#mentioned-' . $mentionedUser . '-section-' . $position;
                    }
                    $emailData = [
                        'siteName' => TikiLib::lib('tiki')->get_preference('browsertitle'),
                        'mentionedBy' => $mentionedBy,
                        'listUrls' => $listUrls,
                        'type' => $arguments['type'],
                        'title' => $title ?? $arguments['object'],
                    ];

                    Tiki\Notifications\Email::sendMentionNotification(
                        'mention_notification_subject.tpl',
                        'mention_notification.tpl',
                        $emailData,
                        [$userInfo]
                    );
                }
            }
        }
    }

    /**
     * Convert between Tiki wiki syntax and Markdown any string of content
     *
     * @param string $data content to convert
     * @param string $target_syntax markdown or tiki
     * @return string converted content
     * @throws Exception
     */
    public function convertWikiSyntax($data, $target_syntax, $page_name = null)
    {
        global $page_regex, $prefs, $page, $tikilib;
        $parserlib = TikiLib::lib('parser');

        if ($target_syntax != 'markdown' && $target_syntax != 'tiki') {
            throw new Exception(tr('Failed converting content: unrecognized target syntax passed: %0', $target_syntax));
        }

        $old_pref = $prefs['wiki_heading_links'];
        $prefs['wiki_heading_links'] = 'n';
        $hashed = [];

        if ($target_syntax == 'markdown') {
            // handle some Tiki syntax to Wiki plugins that are not supported in Markdown
            $data = preg_replace('/-=(.+?)=-/', "{DIV(class=titlebar)}$1{DIV}", $data);
            $data = $this->addNewLineToDivsOutsideTables($data);
            $data = preg_replace_callback('/^(!+)([+\-#]+)(.*)$/m', function ($matches) {
                $level = strlen($matches[1]);
                if ($level < 1 || $level > 6) {
                    return $matches[0];
                }
                return "{tikiheading level=$level options=$matches[2]}" . trim($matches[3]) . "{/tikiheading}";
            }, $data);
            $data = preg_replace('/~~(.+?):(.+?)~~/', '{HTML()}<span style="color:$1">$2</span>{HTML}', $data);
            $data = preg_replace('/-\+(.+?)\+-/', '`$1`', $data);
            $data = preg_replace_callback('/\{VERSIONS\(.*\)\}(.+)\{VERSIONS\}/s', function ($matches) {
                return preg_replace_callback('/^---\(([^\):]*)( : [^\)]*)?\)---*/m', function ($innerMatches) {
                    return str_replace('-', '@-', $innerMatches[0]);
                }, $matches[0]);
            }, $data);
            $data = preg_replace('/--(.+?)--/', '<strike>$1</strike>', $data);
            $data = preg_replace_callback('/\{SPLIT\(.*\)\}(.+)\{SPLIT\}/s', function ($matches) {
                return preg_replace('/(?<![\-|])---(?![\-|])/', '@-@-@-', $matches[0]);
            }, $data);

            $data = preg_replace_callback('/~pp~(.+?)~\/pp~/s', function ($m) use (&$hashed) {
                $hash = $this->pushToHashed($hashed, $m[0]);
                return $hash;
            }, $data);
            $data = preg_replace('/^;:/m', '', $data);

            // Preserve user mentions
            $data = $parserlib->parse_tagged_users($data, function ($u, $m) use (&$hashed) {
                $hash = $this->pushToHashed($hashed, $m[0]);
                return $hash;
            });

            // Preserving dynamic variables
            $enclose = $parserlib->dynVarEnclose();
            if ($enclose) {
                $dVars = $parserlib->getDynVariables($data, $enclose);
                foreach ($dVars as $dVar) {
                    $dvar_preg = preg_quote($dVar);
                    $data = preg_replace_callback("+$enclose($dvar_preg)$enclose+", function ($m) use (&$hashed) {
                        $hash = $this->pushToHashed($hashed, $m[0]);
                        return $hash;
                    }, $data);
                }
            }
        }
        $data = $this->convertSmileysToUnicode($data);

        $wikiParserParsable = new WikiParser_Parsable($data);
        if (! $wikiParserParsable->convertable($data)) {
            throw new Exception(tr("Content has plugins that cannot be automatically converted. You should proceed with manual content conversion."));
        }
        $syntaxPluginResult = $wikiParserParsable->guess_syntax($data);
        $source_syntax = $syntaxPluginResult['syntax'];
        $wikiParserParsable->stripPlugins('code');
        $wikiParserParsable->stripPlugins('html');
        $wikiParserParsable->stripPlugins('maketoc');
        $wikiParserParsable->stripPlugins('wantedpages');
        $wikiParserParsable->stripPlugins('markdown');
        $wikiParserParsable->stripNP();
        $html = $wikiParserParsable->parse([
            'noparseplugins' => true,
            'noparseargvariables' => true,
            'noparsefilereferences' => true,
            'suppress_icons' => true,
            'markdown_conversion' => true,
        ]);

        $prefs['wiki_heading_links'] = $old_pref;

        if ($page_name) {
            $page_infos = $tikilib->get_page_info($page_name);

            if ($prefs['feature_wiki_allowhtml'] === 'y' && $page_infos['is_html']) {
                if ($target_syntax == $source_syntax) {
                    throw new Exception(tr('Content already in ' . $source_syntax . ' syntax.'));
                }
                return $data;
            }
        }

        if ($target_syntax == 'markdown') {
            // convert to markdown
            if ($source_syntax == 'markdown') {
                throw new Exception(tr("Content already in markdown syntax."));
            }
            static $converter = null;
            if (! $converter) {
                $converter = new League\HTMLToMarkdown\HtmlConverter([
                    'strip_tags' => false,
                    'hard_break' => true,
                    'header_style' => 'atx',
                ]);
                $converter->getEnvironment()->addConverter(new League\HTMLToMarkdown\Converter\TableConverter());
                $converter->getEnvironment()->addConverter(new DefinitionListConverter());
                $converter->getEnvironment()->addConverter(new StrikeConverter());
            }

            // GithubFlovoredMarkdown uses ~text~ for striking through.
            // This escapes ~ as in tiki syntax it is printed as it is
            $html = preg_replace('/~/', 'preserveTildeChar', $html);

            $invalidTagsMap = $this->preserveInvalidTags($html);
            $converted = $converter->convert($html);
            $converted = $this->restoreInvalidTags($invalidTagsMap, $converted);
            $converted = preg_replace('/\\\\([\[\]])/', '$1', $converted);
            $converted = preg_replace("/\\\_/", "_", $converted);

            // bring back escaped wiki plugin code
            $pattern = "/^(\s*{[^}]*)\\\\([*_\\[\\]\\\\][^}]*}\s*)$/m";
            while (preg_match($pattern, $converted)) {
                $converted = preg_replace($pattern, "$1$2", $converted);
            }

            // bring back escaped link syntax
            if (preg_match_all('/\\\\\[(.*?)\\\\\]/', $converted, $matches)) {
                foreach ($matches[1] as $key => $link) {
                    $link = preg_replace('/\\\\([*_])/', '$1', $link);
                    $converted = str_replace($matches[0][$key], '[' . $link . ']', $converted);
                }
            }

            // bring back escaped underscores in wiki plugins
            $pattern = "/({[^}]*)\\\\_([^}]*})/";
            while (preg_match($pattern, $converted)) {
                $converted = preg_replace($pattern, "$1_$2", $converted);
            }

            // bring back escaped underscores in urls
            $pattern = "/(https?:\/\/[^\s]*)\\\\_/";
            while (preg_match($pattern, $converted)) {
                $converted = preg_replace($pattern, "$1_$2", $converted);
            }

            // bring back html entities in wiki plugins
            $pattern = "/({[^}]*)&gt;([^}]*})/";
            while (preg_match($pattern, $converted)) {
                $converted = preg_replace($pattern, "$1>$2", $converted);
            }
            $pattern = "/({[^}]*)&lt;([^}]*})/";
            while (preg_match($pattern, $converted)) {
                $converted = preg_replace($pattern, "$1<$2", $converted);
            }

            // bring back wiki syntax new lines
            $pattern = "/\n?(?<![^%]\d)%%%/";
            $converted = preg_replace($pattern, "<br />", $converted);

            $converted = preg_replace('/^(#+)\s+(.+?)/m', "$1 $2", $converted);
            $converted = preg_replace_callback('/\[(.+)\]/', function ($m) {
                return str_replace('\\_', '_', $m[0]);
            }, $converted);

            // Processing plugin div as it needs space around
            // First of all add \n around plugin div split on multiple lines
            $converted = preg_replace_callback('/{DIV(.*?)}(.*?){DIV}/s', function ($m) {
                $lines = explode("\n", $m[0]);
                return count($lines) > 1 ? "\n{$m[0]}\n" : $m[0];
            }, $converted);
            // after add \n around plugin div which is not in a table
            $converted = preg_replace("/^(?!^\|)(.*)({DIV.*?}.*{DIV})(.*)(?!^\|\s*)$/m", "$1\n$2\n$3", $converted);

            // Bring back > character
            $converted = str_replace('&gt;', '>', $converted);

            // When ** closing is precedeed by : directly followed by a non space it fails
            $converted = preg_replace('/\*\*(.*):\*\*(?![\s\n\r])/', "**$1:** ", $converted);

            // Remarks box need \n as they return div tag
            $converted = preg_replace('/\{REMARKSBOX.*?\}(.*?)\{REMARKSBOX\}(?![\n\r])/s', "$0\n", $converted);

            // Fixing the double quotes
            $converted = preg_replace('/<tt>\s*“(.*?)”\s*<\/tt>/s', "“$1“", $converted);
        } else {
            // convert to tiki syntax
            if ($source_syntax == 'tiki') {
                throw new Exception(tr("Content already in Tiki syntax."));
            }
            $converted = TikiLib::lib('edit')->parseToWiki($html);
        }

        $converted = $wikiParserParsable->restorePlugins($converted);
        $converted = preg_replace('/&amp;(#\d{6}|rarr|excl|quest);/', "&$1;", $converted);
        $converted = str_replace('[[[[', "[[", $converted);

        if ($hashed) {
            $converted = str_replace($hashed['keys'], $hashed['values'], $converted);
        }

        if ($target_syntax == 'markdown') {
            $converted = preg_replace_callback('/\{tikiheading level=(.*?) options=(.*?)\}(.*?)\{\/tikiheading\}/', function ($matches) {
                return str_repeat('#', $matches[1]) . str_replace(['+#', '-#', '#'], ['$+', '$-', '$'], $matches[2]) . ' ' . $matches[3];
            }, $converted);
            $converted = preg_replace_callback('/\{VERSIONS\(.*\)\}(.+)\{VERSIONS\}/s', function ($matches) {
                return preg_replace_callback('/^(@-)+\(.*?\)(@-)+/m', function ($innerMatches) {
                    return str_replace('@-', '-', $innerMatches[0]);
                }, $matches[0]);
            }, $converted);
            $converted = preg_replace_callback('/\{SPLIT\(.*\)\}(.+)\{SPLIT\}/s', function ($matches) {
                return str_replace('@-', '-', $matches[0]);
            }, $converted);

            // Markdown plugin is not needed any more
            $converted = preg_replace('/\{MARKDOWN.*?\}(.*?)\{MARKDOWN\}/s', "$1", $converted);

            // Put back dynamic variables
            if ($enclose) {
                $converted = preg_replace('/\{dynVar\}(.*)\{dynVar\}/', "$enclose$1$enclose", $converted);
            }
            $converted = preg_replace('/preserveTildeChar/', '\\~', $converted);
        }

        return $converted;
    }

    private function addNewLineToDivsOutsideTables($text)
    {
        // Split the text by the || markers
        $segments = preg_split('/(\|\|.*?\|\|)/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        $result = '';
        foreach ($segments as $segment) {
            // If the segment is enclosed by ||...||, keep it as is
            if (preg_match('/^\|\|.*\|\|$/s', $segment)) {
                $result .= $segment;
            } else {
                // If the segment is not enclosed, perform the replacement
                $result .= preg_replace('/(\{DIV\(class=titlebar\)\}.*?\{DIV\})/s', "$1\n", $segment);
            }
        }

        return $result;
    }

    private function preserveInvalidTags(&$data)
    {
        $validTags = [
            '!DOCTYPE', 'a', 'abbr', 'address', 'area', 'article', 'aside', 'audio', 'base', 'bdo',
            'bgsound', 'blink', 'blockquote', 'body', 'br', 'button', 'canvas', 'caption', 'col',
            'colgroup', 'command', 'comme;nt', 'datalist', 'dd', 'del', 'details', 'div', 'dl', 'dt',
            'embed', 'fieldset', 'figure', 'b', 'i', 'small', 'tt', 'footer', 'form', 'head',
            'header', 'hgroup', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'html', 'isindex', 'iframe', 'ilayer', 'img',
            'input', 'ins', 'keygen', 'label', 'layer', 'legend', 'li', 'link', 'map', 'mark',
            'marquee', 'menu', 'meta', 'meter', 'multicol', 'nav', 'nobr', 'noembed', 'noscript',
            'object', 'ol', 'optgroup', 'option', 'output', 'p', 'param', 'cite', 'code', 'dfn', 'em',
            'kbd', 'samp', 'strong', 'var', 'plaintext', 'pre', 'progress', 'q', 'ruby', 'script',
            'section', 'select', 'spacer', 'span', 's', 'style', 'sub', 'sup', 'table', 'tbody', 'td',
            'textarea', 'tfoot', 'thead', 'time', 'title', 'tr', 'u', 'ul', 'video', 'wbr', 'xmp', 'strike'
        ];

        $invalidTags = [];
        $data = preg_replace_callback("/<(?:\"[^\"]*\"['\"]*|'[^']*'['\"]*|[^'\">])+>/s", function ($m) use (&$invalidTags, $validTags) {
            list($tagName) = explode(' ', substr($m[0], 1, strlen($m[0]) - 2));
            if (! in_array($tagName, $validTags) && $tagName[0] != '/') {
                $hash = $this->pushToHashed($invalidTags, $m[0]);
                return $hash;
            }
            return $m[0];
        }, $data);

        return $invalidTags;
    }

    public function pushToHashed(array &$array, string $content): string
    {
        $hash = '§' . md5(uniqid()) . '§';
        $array['keys'][] = $hash;
        $array['values'][] = $content;

        return $hash;
    }

    private function restoreInvalidTags(array $map, $data)
    {
        if ($data && isset($map['keys'], $map['values'])) {
            return str_replace($map['keys'], $map['values'], $data);
        }
        return $data;
    }

    public function convertContentPlugins($content, $targetSyntax)
    {
        $dcslib = TikiLib::lib('dcs');

        $contentData = $dataCopy = $content['data'];
        $wikiParserParsable = new WikiParser_Parsable($dataCopy);
        $syntaxPluginResult = $wikiParserParsable->guess_syntax($dataCopy);
        if ($syntaxPluginResult['syntax'] == $targetSyntax) {
            return;
        }
        try {
            $contentData = $this->convertWikiSyntax($contentData, $targetSyntax);
            $contentData = "{syntax type=\"{$targetSyntax}\"}\n" . $contentData;
            $dcslib->replace_programmed_content(
                $content['pId'],
                $content['contentId'],
                $content['publishDate'],
                $contentData,
                $content['content_type']
            );
        } catch (Exception $e) {
            // Nothing to do
        }
    }

    public function convertSmileysToUnicode($data)
    {
        $named = [
            'biggrin' => '😀',
            'confused' => '😕',
            'cool' => '😎',
            'cry' => '😢',
            'eek' => '😬',
            'evil' => '👻',
            'exclaim' => '❗',
            'frown' => '🙁',
            'idea' => '💡',
            'lol' => '😂',
            'mad' => '😧',
            'mrgreen' => '😁',
            'neutral' => '😐',
            'question' => '❓',
            'razz' => '😛',
            'redface' => '😡',
            'rolleyes' => '🙄',
            'sad' => '😑',
            'smile' => '😊',
            'surprised' => '😯',
            'twisted' => '🙀',
            'wink' => '😉',
            'arrow' => '➡️',
            'santa' => '🎅',
        ];

        $patterns = [
            // :) :-)
            '/(\s|^):-?\)/' => '$1😊',
            // :( :-(
            '/(\s|^):-?\(/' => '$1😑',
            // :D :-D
            '/(\s|^):-?D/' => '$1😀',
            // ;) ;-)
            '/(\s|^);-?\)/' => '$1😉',
            // :S :-S :s :-s
            '/(\s|^):-?S/i' => '$1😕',
            // :-/
            '/(\s|^):-\//' => '$1😕',
            // :-| :|
            '/(\s|^):-?\|/' => '$1😐',
            // B) B-) 8-)
            '/(\s|^)(B-?|8-)\)/' => '$1😎',
            // :'( :_(
            '/(\s|^):[\'|_]\(/' => '$1😢',
            // 8-o 8-O =-o =-O
            '/(\s|^)[8=]-O/i' => '$1😬',
            // :-o :o
            '/(\s|^):-?o/' => '$1😯',
            // :-p :p
            '/(\s|^):-?p/' => '$1😛',
            // }:( }:-(
            '/(\s|^)\}:-?\(/' => '$1👻',
            // !-) !)
            '/(\s|^)\!-?\)/' => '$1❗',
            // >:( >:-(
            '/(\s|^)\>:-?\(/' => '$1🙁',
            // i-)
            '/(\s|^)i-\)/' => '$1💡',
            // LOL
            '/(\s|^)LOL(\s|$)/' => '$1😂',
            // >X( >X[ >:[ >X-( >X-[ >:-[
            '/(\s|^)\>[:X]-?\(/' => '$1😧',
            // =D =-D
            '/(\s|^)[=]-?D/' => '$1😁',
        ];

        $data = preg_replace(array_keys($patterns), array_values($patterns), $data);

        $data = preg_replace_callback('/\(:(\w+):\)/i', function ($matches) use ($named) {
            if (! isset($named[$matches[1]])) {
                return $matches[0];
            }
            return $named[$matches[1]];
        }, $data);

        return $data;
    }

    public function removeSyntaxPlugin($inData)
    {
        return preg_replace('/^\{syntax.+\}(?:\r\n|\s)(.+)/', "$1", $inData);
    }

    public function toggleCheckbox($data, $checkboxNum)
    {
        $i = 0;
        $state = false;

        // Tasklist syntax: Use a dash or a star followed by a space, followed by an opening square bracket, followed a either a space for unchecked checkbox or x for checked checked, followed by a closing square bracket, followed by a text
        $data = preg_replace_callback('/^([-|*]\s)(\[[x|\s]\])(.+)/m', function ($m) use (&$i, $checkboxNum, &$state) {
            $i += 1;
            if ($checkboxNum == $i) {
                $state = $m[2] == '[ ]';
                return $m[1] . ($state ? '[x]' : '[ ]') . $m[3];
            }
            return $m[0];
        }, $data);

        return [$data, $state];
    }

    /**
     * Fix nested quotes in HTML attributes that can confuse the HTML parser
     *
     * @param string $html The HTML to fix
     * @return string The fixed HTML
     */
    private function fixNestedQuotesInAttributes($html)
    {
        $html = $this->fixBrokenStyleAttributes(html_entity_decode($html, ENT_QUOTES, 'UTF-8'));

        $wrappedHtml = '<div>' . $html . '</div>';

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        libxml_use_internal_errors(true);

        if (! $dom->loadHTML('<?xml encoding="UTF-8">' . $wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            libxml_clear_errors();
            return false;
        }

        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//*[@style]');

        foreach ($elements as $element) {
            if ($element instanceof DOMElement) {
                $style = $element->getAttribute('style');
                $fixedStyle = $this->fixStyleAttribute($style);
                $element->setAttribute('style', $fixedStyle);
            }
        }

        $wrapper = $dom->getElementsByTagName('div')->item(0);
        $innerHTML = '';

        if ($wrapper) {
            foreach ($wrapper->childNodes as $child) {
                $innerHTML .= $dom->saveHTML($child);
            }
        }

        return $innerHTML;
    }

    private function fixBrokenStyleAttributes($html)
    {
        // Find all style attributes that might be broken by nested quotes
        $html = preg_replace_callback(
            '/style\s*=\s*"([^"]*(?:\'[^\']*\'[^"]*)*(?:"[^"]*"[^"]*)*[^"]*)"(?:\s+[a-zA-Z-]+(?:\s*=\s*["\'][^"\']*["\'])?)*/',
            function ($matches) {
                $styleContent = $matches[1];

                $fixedStyle = $this->fixStyleAttribute($styleContent);

                return 'style="' . $fixedStyle . '"';
            },
            $html
        );

        // Handle broken attributes after style (legacy parser splits them incorrectly)
        $html = preg_replace_callback(
            '/style\s*=\s*"([^"]+)"\s+([a-zA-Z][a-zA-Z0-9-]*)\s+([a-zA-Z][a-zA-Z0-9-]*)\s+([a-zA-Z][a-zA-Z0-9-]*)/i',
            function ($matches) {
                $style = $matches[1];
                $attr1 = $matches[2];
                $attr2 = $matches[3];
                $attr3 = $matches[4];

                // These are likely broken parts of the font-family declaration
                // Reconstruct the complete style attribute
                if (str_contains($style, 'font-family:') && ! str_ends_with($style, ';')) {
                    $style .= ', ' . $attr1;
                    if ($attr2) {
                        $style .= ', ' . $attr2;
                    }
                    if ($attr3) {
                        $style .= ', ' . $attr3;
                    }
                    $style .= ';';

                    // Fix quotes in the reconstructed style
                    $style = $this->fixStyleAttribute($style);
                }

                return 'style="' . $style . '"';
            },
            $html
        );

        return $html;
    }

    private function fixStyleAttribute($style)
    {
        $style = html_entity_decode($style, ENT_QUOTES, 'UTF-8');

        $style = preg_replace_callback(
            '/font-family\s*:\s*([^;]+)/i',
            function ($matches) {
                $fontFamily = $matches[1];

                $fontFamily = preg_replace('/"([^"]*)"/', "'$1'", $fontFamily);

                // Handle any remaining quoted strings that might be malformed
                $fontFamily = preg_replace('/([a-zA-Z0-9-]+)\s*,\s*([a-zA-Z0-9-]+)\s*,\s*([a-zA-Z0-9-]+)/', '$1, $2, $3', $fontFamily);

                return 'font-family: ' . trim($fontFamily);
            },
            $style
        );

        // Fix other CSS properties that might have nested quotes
        $style = preg_replace_callback(
            '/(content|background-image|cursor|background)\s*:\s*([^;]+)/i',
            function ($matches) {
                $property = strtolower($matches[1]);
                $value = $matches[2];

                // For URL values, keep them quoted but use single quotes
                if ($property === 'background-image' && str_contains($value, 'url(')) {
                    $value = preg_replace('/url\s*\(\s*"([^"]*)"\s*\)/', "url('$1')", $value);
                } else {
                    // Replace inner double quotes with single quotes for other properties
                    $value = preg_replace('/"([^"]*)"/', "'$1'", $value);
                }
                return $property . ': ' . trim($value);
            },
            $style
        );

        // Clean up any malformed CSS and ensure proper semicolon termination
        $style = preg_replace('/\s*;\s*;+/', ';', $style); // Remove duplicate semicolons
        $style = trim($style);
        if (! empty($style) && substr($style, -1) !== ';') {
            $style .= ';';
        }

        return $style;
    }
}
