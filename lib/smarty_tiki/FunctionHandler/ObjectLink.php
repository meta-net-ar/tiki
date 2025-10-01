<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Perms;
use Tracker_Definition;
use Smarty\FunctionHandler\Base;
use Smarty\Template;

class ObjectLink extends Base
{
    public function handle($params, Template $template)
    {
        if (! isset($params['type'], $params['id']) &&  ! isset($params['type'], $params['objectId']) && ! isset($params['identifier'])) {
            return tra('No object information provided.');
        }

        if (isset($params['type'], $params['id'])) {
            $type = $params['type'];
            $object = $params['id'];
        } else {
            $identifier = isset($params['identifier']) ? explode(':', $params['identifier'], 2) : null;
            if (is_countable($identifier) && count($identifier) != 2) {
                return tra('Unable to parse object information provided.');
            }
            list($type, $object) = $identifier;
        }

        if (isset($params['objectId']) && ! isset($params['id'])) {
            $type = $params['type'];
            $object = $params['objectId'];
        }

        $title = $params['title'] ?? null;
        $url = $params['url'] ?? null;

        switch ($type) {
            case 'wiki page':
            case 'wikipage':
            case 'wiki':
                $type = 'wiki page';
                $function = 'smartyFunctionObjectLinkDefault';
                if (! $title) {
                    $title = $object;
                }
                global $prefs;
                if ($prefs['feature_wiki_structure'] === 'y') {
                    $structlib = \TikiLib::lib('struct');
                    $page_id = $structlib->get_struct_ref_id($title);
                    if ($page_id) {
                        $alias = $structlib->get_page_alias($page_id);
                        if ($alias) {
                            $title = $alias;
                        }
                    }
                }
                break;
            case 'user':
                $function = 'smartyFunctionObjectLinkUser';
                break;
            case 'external':
            case 'external_extended':
                $function = 'smartyFunctionObjectLinkExternal';
                break;
            case 'relation_source':
                $function = 'smartyFunctionObjectLinkRelationSource';
                break;
            case 'relation_target':
                $function = 'smartyFunctionObjectLinkRelationTarget';
                break;
            case 'freetag':
                $function = 'smartyFunctionObjectLinkFreetag';
                break;
            case 'trackeritemfield':
                $type = 'trackeritem';
                $object = (int)(explode(':', $object)[0]);
                $function = 'smartyFunctionObjectLinkTrackerItem';
                break;
            case 'trackeritem':
                $function = 'smartyFunctionObjectLinkTrackerItem';
                break;
            case 'group':
                // Nowhere to link, at least, yet.
                return $object;
            case 'forumpost':
            case 'forum post':
                $function = 'smartyFunctionObjectLinkForumPost';
                break;
            case 'comment':
                $function = 'smartyFunctionObjectLinkComment';
                break;
            case 'email':
                $function = 'smartyFunctionObjectLinkEmail';
                break;
            default:
                $function = 'smartyFunctionObjectLinkDefault';
                break;
        }

        return $this->$function($template, $object, $title, $type, $url, $params);
    }

    public function smartyFunctionObjectLinkDefault($template, $object, $title = null, $type = 'wiki page', $url = null, $params = [])
    {
        global $base_url;

        if (empty($title)) {
            $title = \TikiLib::lib('object')->get_title($type, $object, empty($params['format']) ? null : $params['format'], $params['metaItemId'] ?? null);
        }

        if (empty($title) && ! empty($params['backuptitle'])) {
            $title = $params['backuptitle'];
        }

        if (empty($title) && $type == 'freetag') {
            // Blank freetag should not be returned with "No title specified"
            return '';
        }

        $text = $title;
        $titleAttribute = '';
        if ($type == 'wiki page') {
            $titleAttribute .= ' title="' . smarty_modifier_escape($title) . '"';
            $text = \TikiLib::lib('wiki')->get_without_namespace($title);
        }

        $escapedText = smarty_modifier_escape($text ? $text : tra('No title specified'), 'html', 'UTF-8', false);

        if ($url) {
            $escapedHref = smarty_modifier_escape(\TikiLib::tikiUrlOpt($url));
        } else {
            if ($type == 'tracker') {
                $definition = Tracker_Definition::get($object);
                if ($definition && $definition->isEnabled('adminOnlyViewEditItem') && ! Perms::get($type, $object)->admin_trackers) {
                    return $escapedText;
                }
            }
            $escapedHref = smarty_modifier_escape(smarty_modifier_sefurl($object, $type));
        }

        $classList = [];

        if ($type == "blog post") {
            $classList[] = "link";
        } elseif ($type == "freetag") {
            $classList[] = 'freetag';
        }

        $metadata = \TikiLib::lib('object')->get_metadata($type, $object, $classList);

        if (! empty($params['class'])) {
            $classList[] = $params['class'];
        }

        $class = ' class="' . implode(' ', $classList) . '"';

        if (! str_contains($escapedHref, '://')) {
            //$html = '<a href="' . $base_url . $escapedHref . '"' . $class . $titleAttribute . $metadata . '>' . $escapedText . '</a>';
            // When the link is created for a tiki page, then we do NOT want the baseurl included,
            // because it might be we are using a reverse proxy or a an ssl offloader, or we access from a public fqdn that is not
            // configured for teh ip adress we run our webserver.
            // Eaxmple: Fqdn = tiki.mydomain.com -> port forwarding/nat to: 192.168.1.110.
            // In this case links should NOT be generated as absolut urls pointing to  192.168.1.110 which would be the part of the baseUrl.
            $html = '<a href="' . $escapedHref . '"' . $class . $titleAttribute . $metadata . '>' . $escapedText . '</a>';
        } else {
            $html = '<a rel="external" href="' . $escapedHref . '"' . $class . $titleAttribute . $metadata . '>' . $escapedText . '</a>';
        }

        $attributelib = \TikiLib::lib('attribute');
        $attributes = $attributelib->get_attributes($type, $object);

        global $prefs;
        if (isset($attributes['tiki.content.source']) && $prefs['fgal_source_show_refresh'] == 'y') {
            $html .= '<a class="file-refresh" href="' .
            smarty_function_service(
                [
                    'controller' => 'file',
                    'action' => 'refresh',
                    'fileId' => (int)$object,
                ],
                $template
            ) . '">' .
            smarty_function_icon(
                ['_id' => 'arrow_refresh',],
                $template
            ) . '</a>';

            \TikiLib::lib('header')->add_js(
                '
                $(".file-refresh").removeClass("file-refresh").on("click", function () {
                $.getJSON($(this).attr("href"));
                $(this).remove();
                return false;
            });'
            );
        }

        if (! empty($params['metaItemId'])) {
            $html .= smarty_function_icon([
                'name' => 'clipboard-list',
                'title' => tr('show metadata'),
                'href' => smarty_modifier_escape(smarty_modifier_sefurl($params['metaItemId'], 'trackeritem')),
            ], $template) . '</a>';
        }

        return $html;
    }

    public function smartyFunctionObjectLinkComment($template, $object, $title = null, $type = 'wiki page', $url = null, $params = [])
    {
        $comments_lib = \TikiLib::lib('comments');
        $comment = $comments_lib->get_comment($object);
        $url = is_array($comment) ? smarty_modifier_sefurl($object, $comment['objectType'] . '_comment') : smarty_modifier_sefurl($object, 'comment' . '_comment');

        if (empty($title)) {
            $title = \TikiLib::lib('object')->get_title($type, $object, empty($params['format']) ? null : $params['format']);
        }

        return '<a href="' . $url . '">' . $title . '</a>';
    }

    public function smartyFunctionObjectLinkTrackerItem($template, $object, $title = null, $type = 'wiki page', $url = null, $params = [])
    {
        $item = \Tracker_Item::fromId($object);

        $pre = smarty_function_tracker_item_status_icon(['item' => $item], $template);
        if (! empty($pre)) {
            $pre .= " ";
        }

        if ($item && $item->canView() && (! $item->getDefinition()->isEnabled('adminOnlyViewEditItem') || Perms::get('trackeritem', $object)->admin_trackers)) {
            return $pre . $this->smartyFunctionObjectLinkDefault($template, $object, $title, $type, $url, $params);
        } else {
            if (empty($title)) {
                $title = \TikiLib::lib('object')->get_title($type, $object, empty($params['format']) ? null : $params['format'], $params['metaItemId'] ?? null);
            }

            return $pre . smarty_modifier_escape($title);
        }
    }

    public function smartyFunctionObjectLinkUser($template, $user, $title = null)
    {
        return smarty_modifier_userlink($user, 'link', 'not_set', $title ? $title : '');
    }

    public function smartyFunctionObjectLinkExternal($template, $link_orig, $title = null, $type = null)
    {
        $cachelib = \TikiLib::lib('cache');
        $tikilib = \TikiLib::lib('tiki');

        if (str_starts_with($link_orig, 'www.')) {
            $link = 'http://' . $link_orig;
        } else {
            $link = $link_orig;
        }

        if (! $title) {
            if (! $title = $cachelib->getCached($link, 'object_link_ext_title')) {
                $body = $tikilib->httprequest($link);
                if (preg_match('|<title>(.+)</title>|', $body, $parts)) {
                    $title = \TikiFilter::get('text')->filter($parts[1]);
                } else {
                    $title = $link_orig;
                }

                $cachelib->cacheItem($link, $title, 'object_link_ext_title');
            }
        }

        $escapedHref = smarty_modifier_escape($link);
        $escapedLink = smarty_modifier_escape($link_orig);
        $escapedTitle = smarty_modifier_escape($title);

        if ($type == 'external_extended' && "$link_orig" != "$title") {
            $data = '<a rel="external" href="' . $escapedHref . '">' . $escapedLink . '</a>'
                        . "<div class='link_extend_title'><em>" . $escapedTitle . "</em></div>";
        } else {
            $data = '<a rel="external" href="' . $escapedHref . '">' . $escapedTitle . '</a>';
        }

        return $data;
    }

    public function smartyFunctionObjectLinkRelationSource($template, $relationId, $title = null)
    {
        return $this->smartyFunctionObjectLinkRelationEnd($template, 'source', $relationId, $title);
    }

    public function smartyFunctionObjectLinkRelationTarget($template, $relationId, $title = null)
    {
        return $this->smartyFunctionObjectLinkRelationEnd($template, 'target', $relationId, $title);
    }

    public function smartyFunctionObjectLinkRelationEnd($template, $end, $relationId, $title = null)
    {
        $relationlib = \TikiLib::lib('relation');
        $attributelib = \TikiLib::lib('attribute');
        $cachelib = \TikiLib::lib('cache');

        $cacheKey = "$relationId:$end:$title";

        if (! $out = $cachelib->getCached($cacheKey, 'relation_link')) {
            $relation = $relationlib->get_relation($relationId);

            if ($relation) {
                if (! $title) {
                    $attributes = $attributelib->get_attributes('relation', $relationId);
                    $key = 'tiki.relation.' . $end;

                    if (isset($attributes[$key]) && ! empty($attributes[$key])) {
                        $title = $attributes[$key];
                    }
                }

                $type = $relation[$end . '_type'];
                $object = $relation[$end . '_itemId'];

                $smartyFunctionObjectLinkHandler = new ObjectLink();
                $out = $smartyFunctionObjectLinkHandler->handle(
                    [
                        'type' => $type,
                        'id' => $object,
                        'title' => $title,
                    ],
                    $template
                );

                $cachelib->cacheItem($cacheKey, $out, 'relation_link');
            } else {
                $out = tra('Relation not found.');
            }
        }

        return $out;
    }

    public function smartyFunctionObjectLinkFreetag($template, $tag, $title = null)
    {
        global $prefs;
        if ($prefs['feature_freetags'] != 'y') {
            return tr('tags disabled');
        }

        if (is_numeric($tag)) {
            $tag = \TikiLib::lib('freetag')->get_tag_from_id($tag);
        }

        return $this->smartyFunctionObjectLinkDefault($template, $tag, $tag, 'freetag');
    }

    public function smartyFunctionObjectLinkForumPost($template, $object, $title = null, $type = 'forumpost', $url = null)
    {
        $commentslib = \TikiLib::lib('comments');
        $comment = $commentslib->getCommentLight($object);
        if (! is_array($comment)) {
            $comment = [];
        }
        while (empty($comment['title'])) {
            $parent = isset($comment['parentId']) ? $commentslib->getCommentLight($comment['parentId']) : null;
            if (is_array($parent)) {
                $comment['parentId'] = $parent['parentId'];
                $comment['title'] = $parent['title'] ?? '';
                if ($parent['parentId'] == 0) {
                    break;
                }
            } else {
                break;
            }
        }
        // Check if 'threadId' key exists in $comment array before accessing it
        return array_key_exists('threadId', $comment) && isset($comment['threadId']) ? "<a href='tiki-view_forum_thread.php?threadId=" . $comment['threadId'] . "'>" . smarty_modifier_escape($comment['title']) . "</a>" : "<span>" . smarty_modifier_escape($comment['title']) . "</span>";
    }

    public function smartyFunctionObjectLinkEmail($template, $object)
    {
        $data = json_decode($object, true);
        $pageParams = '';
        if (isset($data['page_id'])) {
            $pageParams = "/tiki-index.php?page_id={$data['page_id']}";
        } else {
            $pageParams = "/tiki-webmail.php?page_id={$data['page_id']}";
        }

        return '<a href="' . $pageParams . '&page=message&uid=' . $data['uid'] . '&list_path=' . $data['list_path'] . '&list_parent=' . $data['list_parent'] . '">' . $data['title'] . '</a>';
    }
}
