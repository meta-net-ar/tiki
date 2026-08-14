<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Diff;

use Tiki\Lib\Diff\Renderer\Bytes;
use Tiki\Lib\Diff\Renderer\HtmlDiff;
use Tiki\Lib\Diff\Renderer\Inline;
use Tiki\Lib\Diff\Renderer\SideBySide;
use Tiki\Lib\Diff\Renderer\Unified;

class DiffUtils
{
    public static function getMappingForDiffRenderer()
    {
        return [
            'character_inline' => 'CharacterInline',
            'bytes' => 'Bytes',
            'inline' => 'Inline',
            'sidebyside' => 'SideBySide',
            'unified' => 'Unified',
            'htmldiff' => 'HtmlDiff',
            'character' => 'Character',
        ];
    }

    public static function diff2($page1, $page2, $type = 'sidediff')
    {
        global $tikilib, $prefs;
        $page1 = $tikilib->removeInlineSyntaxTags($page1);
        $page2 = $tikilib->removeInlineSyntaxTags($page2);
        if ($type == 'htmldiff') {
            $search = "#(<[^>]+>|[,\"':\s]+|[^\s,\"':<]+|</[^>]+>)#";
            preg_match_all($search, $page1, $out, PREG_PATTERN_ORDER);
            $page1 = $out[0];
            preg_match_all($search, $page2, $out, PREG_PATTERN_ORDER);
            $page2 = $out[0];
        } else {
            $page1 = empty($page1) ? [] : explode("\n", $page1);
            $page2 = empty($page2) ? [] : explode("\n", $page2);
        }

        $z = new TextDiff($page1, $page2);

        if ($z->isEmpty()) {
            return '';
        }

        $context = 2;
        $words = 1;

        if (str_contains($type, "-")) {
            list($type,$opt) = explode("-", $type, 2);
            if (str_contains($opt, "full")) {
                $context = count($page1);
            }
            if (str_contains($opt, "char")) {
                $words = 0;
            }
        }

        if ($type == 'unidiff') {
            $renderer = new Unified($context);
        } elseif ($type == 'inlinediff') {
            $renderer = new Inline($context, $words);
        } elseif ($type == 'sidediff') {
            $renderer = new SideBySide($context, $words);
        } elseif ($type == 'bytes' && $prefs['feature_actionlog_bytes'] == 'y') {
            $renderer = new Bytes();
        } elseif ($type == 'htmldiff') {
            $renderer = new HtmlDiff($context, $words);
        } else {
            return '';
        }

        return $renderer->render($z);
    }

    /* @brief compute the characters differences between a list of lines
    * @param $orig array list lines in the original version
    * @param $final array the same lines in the final version
    * @param int $words
    * @param string $function
    * @return array
    */
    public static function diffChar($orig, $final, $words = 0, $function = 'character')
    {
        $glue = strpos($function, 'inline') !== false ? "<br />" : "\n";
        if ($words) {
            preg_match_all("/\w+\s+(?=\w)|\w+|\W/u", implode($glue, $orig), $matches);
            $line1 = self::restoreLineBreaks($matches[0], $glue);
            preg_match_all("/\w+\s+(?=\w)|\w+|\W/u", implode($glue, $final), $matches);
            $line2 = self::restoreLineBreaks($matches[0], $glue);
        } else {
            $line1 = preg_split('//u', implode($glue, $orig), -1, PREG_SPLIT_NO_EMPTY);
            $line2 = preg_split('//u', implode($glue, $final), -1, PREG_SPLIT_NO_EMPTY);
        }
        $z = new TextDiff($line1, $line2);
        if ($z->isEmpty()) {
            return [$orig[0], $final[0]];
        }

        self::compileRendererClass($function);

        $rendererMap = self::getMappingForDiffRenderer();
        $new = "\\Tiki\\Lib\\Diff\\Renderer\\" . $rendererMap[$function];
        $renderer = new $new(count($line1));
        return $renderer->render($z);
    }

    public static function restoreLineBreaks($lines, $glue)
    {
        foreach ($lines as $key => $line) {
            if (str_ends_with($line, $glue)) {
                $lines[$key] = substr($line, 0, -1);
                array_splice($lines, $key + 1, 0, $glue);
            }
        }

        return $lines;
    }

    public static function compileRendererClass($function)
    {
        /*
        * The various subclasses of Tiki\Lib\Diff\Renderer\Base have methods whose signatures are incompatible
        * with those of their parents. This raises some warnings which don't matter in production settings.
        *
        * But when running phpunit tests, this causes some failures, because we have configured phpunit
        * to report warnings as failures.
        *
        * Making the methods compatible with each other would be very involved, and might introduce some
        * actual bugs. So instead, temporarily disable warning reporting, just for the compilation of
        * this file.
        */
        global $old_error_reporting_level;
        if (defined('TIKI_IN_TEST')) {
            $old_error_reporting_level = error_reporting(E_ERROR | E_PARSE);
        }

        // PSR-12 uses autoloading (via Composer), So no longer need to manually include files
        // require_once("renderer_$function.php");

        if (defined('TIKI_IN_TEST')) {
            error_reporting($old_error_reporting_level);
        }
    }

    /**
     * Find mentions
     *
     * @param $lines
     * @param $state
     * @return array
     */
    public static function findMentions($lines, $state)
    {
        $allMatches = [] ;

        if (isset($lines) && is_array($lines)) {
            foreach (array_filter($lines) as $line) {
                preg_match_all("/@([\w\-\.]+)/i", $line, $matches);
                foreach ($matches[0] as $match) {
                    $allMatches[] = [
                        'state' => $state,
                        'mention' => trim($match)
                    ];
                }
            }
        }

        return $allMatches;
    }

    /**
     * Find mentions on change content
     *
     * @param $edit
     * @return array
     */
    public static function findMentionsOnChange($edit)
    {
        $allMatches = [];

        if ((isset($edit->orig) && is_array($edit->orig)) && (isset($edit->final) && is_array($edit->final))) {
            if (empty($edit->orig[0])) {
                $mentions = self::findMentions($edit->final, 'new');
                foreach ($mentions as $m) {
                    $allMatches[] = $m;
                }
            } else {
                $renderer = new Inline(1);
                $html = $renderer->render([$edit], true);

                // remove unnecessary content
                $html = preg_replace("#<tr class=\"diffheader\">(.*?)</tr>#", "", $html);
                $html = preg_replace("#<span class='diffinldel'>(.*?)</span>#", "", $html);
                $html = str_replace(["<tr class='diffbody'>", "</tr>", "<td colspan='3'>", "</td>"], "", $html);
                $html = str_replace(["<span class='diffadded'>", "</span>"], "<ins>", $html);
                $finalContent = explode('<ins>', $html);

                $index = 0;
                foreach ($finalContent as $key => $value) {
                    if (($index % 2) == 1) {
                        // new mention
                        $charToAdd = '';
                        $previousMention = $finalContent[$key - 1];
                        if (! empty($previousMention)) {
                            $lastChar = substr($previousMention, -1);
                            if ($lastChar == '@') {
                                $charToAdd = '@';
                            }
                        }

                        $mentions = self::findMentions([$charToAdd . $value], 'new');
                    } else {
                        // old mention
                        $mentions = self::findMentions([$value], 'old');
                    }

                    foreach ($mentions as $m) {
                        $allMatches[] = $m;
                    }
                    $index++;
                }
            }
        }

        return $allMatches;
    }
}
