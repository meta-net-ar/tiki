<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Extension;

use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\FunctionHandler\FunctionHandlerInterface;
use Smarty\Compile\CompilerInterface;
use TikiLib;

class SmartyTikiExtension extends \Smarty\Extension\Base
{
    private $blockHandlers = [];
    private $functionHandlers = [];
    private $outputFilters = [];
    private $preFilters = [];
    private $tags = [];

    public function getTagCompiler(string $tag): ?CompilerInterface
    {
        if (isset($this->tags[$tag])) {
            return $this->tags[$tag];
        }

        switch ($tag) {
            case 'assign_content':
                $this->tags[$tag] = new \SmartyTiki\Compile\Tag\AssignContent();
                break;
        }

        return $this->tags[$tag] ?? null;
    }

    public function getModifierCallback(string $modifierName)
    {
        switch ($modifierName) {
            case 'a_or_an':
                return [new \SmartyTiki\Modifier\AorAn(), 'handle'];
            case 'addslashes':
                return [$this, 'smartyModifierAddslashes'];
            case 'adjust':
                return [new \SmartyTiki\Modifier\Adjust(), 'handle'];
            case 'array_reverse':
                return [$this, 'smartyModifierArrayreverse'];
            case 'array_key_exists':
                return [$this, 'smartyModifierArrayKeyExists'];
            case 'avatarize':
                return [new \SmartyTiki\Modifier\Avatarize(), 'handle'];
            case 'breakline':
                return [new \SmartyTiki\Modifier\Breakline(), 'handle'];
            case 'categid':
                return [new \SmartyTiki\Modifier\CategId(), 'handle'];
            case 'compactisodate':
                return [new \SmartyTiki\Modifier\CompactIsoDate(), 'handle'];
            case 'countryflag':
                return [new \SmartyTiki\Modifier\CountryFlag(), 'handle'];
            case 'count':
                return [$this, 'smartyModifierCount'];
            case 'd':
                return [new \SmartyTiki\Modifier\D(), 'handle'];
            case 'dbg':
                return [new \SmartyTiki\Modifier\Dbg(), 'handle'];
            case 'div':
                return [new \SmartyTiki\Modifier\Div(), 'handle'];
            case 'duration_short':
                return [new \SmartyTiki\Modifier\DurationShort(), 'handle'];
            case 'duration':
                return [new \SmartyTiki\Modifier\Duration(), 'handle'];
            case 'escape':
                return [new \SmartyTiki\Modifier\Escape(), 'handle'];
            case 'file_can_convert_to_pdf':
                return [new \SmartyTiki\Modifier\FileCanConvertToPdf(), 'handle'];
            case 'file_diagram':
                return [new \SmartyTiki\Modifier\FileDiagram(), 'handle'];
            case 'forumname':
                return [new \SmartyTiki\Modifier\ForumName(), 'handle'];
            case 'forumtopiccount':
                return [new \SmartyTiki\Modifier\ForumTopicCount(), 'handle'];
            case 'groupmembercount':
                return [new \SmartyTiki\Modifier\GroupMemberCount(), 'handle'];
            case 'how_many_user_inscriptions':
                return [new \SmartyTiki\Modifier\HowManyUserInscriptions(), 'handle'];
            case 'htmldecode':
                return [new \SmartyTiki\Modifier\HtmlDecode(), 'handle'];
            case 'iconify':
                return [new \SmartyTiki\Modifier\Iconify(), 'handle'];
            case 'in_group':
                return [new \SmartyTiki\Modifier\InGroup(), 'handle'];
            case 'is_array':
                return [$this, 'smartyModifierIsarray'];
            case 'is_numeric':
                return [$this, 'smartyModifierIsNumeric'];
            case 'isodate':
                return [new \SmartyTiki\Modifier\IsoDate(), 'handle'];
            case 'json_decode':
                return [$this, 'smartyModifierJsonDecode'];
            case 'kbsize':
                return [new \SmartyTiki\Modifier\KbSize(), 'handle'];
            case 'langname':
                return [new \SmartyTiki\Modifier\LangName(), 'handle'];
            case 'lcfirst':
                return [$this, 'smartyModifierLcfirst'];
            case 'max':
                return [$this, 'smartyModifierMax'];
            case 'max_user_inscriptions':
                return [new \SmartyTiki\Modifier\MaxUserInscriptions(), 'handle'];
            case 'md5':
                return [$this, 'smartyModifierMd5'];
            case 'money_format':
                return [new \SmartyTiki\Modifier\MoneyFormat(), 'handle'];
            case 'namespace':
                return [new \SmartyTiki\Modifier\NamespaceModifier(), 'handle'];
            case 'nonamespace':
                return [new \SmartyTiki\Modifier\NoNamespace(), 'handle'];
            case 'nonp':
                return [new \SmartyTiki\Modifier\Nonp(), 'handle'];
            case 'number_format':
                return [new \SmartyTiki\Modifier\NumberFormat(), 'handle'];
            case 'numStyle':
                return [new \SmartyTiki\Modifier\NumStyle(), 'handle'];
            case 'output':
                return [new \SmartyTiki\Modifier\Output(), 'handle'];
            case 'packageitemid':
                return [new \SmartyTiki\Modifier\PackageItemId(), 'handle'];
            case 'pagename':
                return [new \SmartyTiki\Modifier\PageName(), 'handle'];
            case 'parse':
                return [new \SmartyTiki\Modifier\Parse(), 'handle'];
            case 'percent':
                return [new \SmartyTiki\Modifier\Percent(), 'handle'];
            case 'preg_match':
                return [$this, 'smartyModifierPregMatch'];
            case 'preg_match_all':
                return [$this, 'smartyModifierPregMatchAll'];
            case 'quoted':
                return [new \SmartyTiki\Modifier\Quoted(), 'handle'];
            case 'replacei':
                return [$this, 'smartyModifierReplacei'];
            case 'reverse_array':
                return [$this, 'smartyModifierReverseArray'];
            case 'sefurl':
                return [new \SmartyTiki\Modifier\Sefurl(), 'handle'];
            case 'sizeof':
                return [$this, 'smartyModifierSizeof'];
            case 'slug':
                return [new \SmartyTiki\Modifier\Slug(), 'handle'];
            case 'star':
                return [new \SmartyTiki\Modifier\Star(), 'handle'];
            case 'stringfix':
                return [new \SmartyTiki\Modifier\StringFix(), 'handle'];
            case 'stristr':
                return [$this, 'smartyModifierStristr'];
            case 'strstr':
                return [$this, 'smartyModifierStrstr'];
            case 'strpos':
                return [$this, 'smartyModifierStrpos'];
            case 'strtolower':
                return [$this, 'smartyModifierStrtolower'];
            case 'substring':
                return [$this, 'smartyModifierSubstring'];
            case 'tasklink':
                return [new \SmartyTiki\Modifier\TaskLink(), 'handle'];
            case 'template':
                return [new \SmartyTiki\Modifier\Template(), 'handle'];
            case 'ternary':
                return [new \SmartyTiki\Modifier\Ternary(), 'handle'];
            case 'tiki_date_format':
                return [new \SmartyTiki\Modifier\TikiDateFormat(), 'handle'];
            case 'tiki_long_date':
                return [new \SmartyTiki\Modifier\TikiLongDate(), 'handle'];
            case 'tiki_long_datetime':
                return [new \SmartyTiki\Modifier\TikiLongDateTime(), 'handle'];
            case 'tiki_long_time':
                return [new \SmartyTiki\Modifier\TikiLongTime(), 'handle'];
            case 'tiki_remaining_days_from_now':
                return [new \SmartyTiki\Modifier\TikiRemainingDaysFromNow(), 'handle'];
            case 'tiki_short_date':
                return [new \SmartyTiki\Modifier\TikiShortDate(), 'handle'];
            case 'tiki_short_datetime':
                return [new \SmartyTiki\Modifier\TikiShortDateTime(), 'handle'];
            case 'tiki_short_time':
                return [new \SmartyTiki\Modifier\TikiShortTime(), 'handle'];
            case 'times':
                return [new \SmartyTiki\Modifier\Times(), 'handle'];
            case 'trim':
                return [$this, 'smartyModifierTrim'];
            case 'tra':
                return [new \SmartyTiki\Modifier\Tra(), 'handle'];
            case 'truncate':
                return [new \SmartyTiki\Modifier\Truncate(), 'handle'];
            case 'truex':
                return [new \SmartyTiki\Modifier\Truex(), 'handle'];
            case 'tr_if':
                return [new \SmartyTiki\Modifier\TrIf(), 'handle'];
            case 'ucfirst':
                return [$this, 'smartyModifierUcfirst'];
            case 'ucwords':
                return [$this, 'smartyModifierUcwords'];
            case 'urlencode':
                return [$this, 'smartyModifierUrlencode'];
            case 'userlink':
                return [new \SmartyTiki\Modifier\UserLink(), 'handle'];
            case 'username':
                return [new \SmartyTiki\Modifier\Username(), 'handle'];
            case 'utf8unicode':
                return [new \SmartyTiki\Modifier\Utf8Unicode(), 'handle'];
            case 'var_dump':
                return [$this, 'smartyModifierVardump'];
            case 'virtual_path':
                return [new \SmartyTiki\Modifier\VirtualPath(), 'handle'];
            case 'yesno':
                return [new \SmartyTiki\Modifier\YesNo(), 'handle'];
            case 'zone_is_empty':
                return [$this, 'smartyModifierZoneIsEmpty'];
        }
        return null;
    }

    public function getBlockHandler(string $blockTagName): ?BlockHandlerInterface
    {
        switch ($blockTagName) {
            case 'accordion_group':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\AccordionGroup();
                break;
            case 'accordion':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Accordion();
                break;
            case 'actions':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Actions();
                break;
            case 'activityframe':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\ActivityFrame();
                break;
            case 'ajax_href':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\AjaxHref();
                break;
            case 'compact':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Compact();
                break;
            case 'display':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Display();
                break;
            case 'filter':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Filter();
                break;
            case 'ifsearchexists':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\IfSearchExists();
                break;
            case 'ifsearchnotexists':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\IfSearchNotExists();
                break;
            case 'itemfield':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\ItemField();
                break;
            case 'jq':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Jq();
                break;
            case 'mailurl':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\MailUrl();
                break;
            case 'modules_list':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\ModulesList();
                break;
            case 'packageplugin':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\PackagePlugin();
                break;
            case 'pagination_links':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\PaginationLinks();
                break;
            case 'permission':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Permission();
                break;
            case 'popup_link':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\PopupLink();
                break;
            case 'repeat':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Repeat();
                break;
            case 'sortlinks':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\SortLinks();
                break;
            case 'self_link':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\SelfLink();
                break;
            case 'remarksbox':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Remarksbox();
                break;
            case 'tab':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Tab();
                break;
            case 'tabset':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Tabset();
                break;
            case 'textarea':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\TextArea();
                break;
            case 'tikimodule':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\TikiModule();
                break;
            case 'title':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Title();
                break;
            case 'tr':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Tr();
                break;
            case 'trackeritemcheck':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\TrackerItemCheck();
                break;
            case 'translation':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Translation();
                break;
            case 'vue':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Vue();
                break;
            case 'wiki':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Wiki();
                break;
            case 'wikiplugin':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Wikiplugin();
                break;
        }

        return $this->blockHandlers[$blockTagName] ?? null;
    }

    public function getFunctionHandler(string $functionName): ?FunctionHandlerInterface
    {
        if (isset($this->functionHandlers[$functionName])) {
            return $this->functionHandlers[$functionName];
        }

        switch ($functionName) {
            case 'activity':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Activity();
                break;
            case 'article':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Article();
                break;
            case 'attachments':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Attachments();
                break;
            case 'autocomplete':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Autocomplete();
                break;
            case 'banner':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Banner();
                break;
            case 'breadcrumbs':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Breadcrumbs();
                break;
            case 'bootstrap_modal':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\BootstrapModal();
                break;
            case 'button':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Button();
                break;
            case 'categoryName':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\CategoryName();
                break;
            case 'categoryselector':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\CategorySelector();
                break;
            case 'content':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Content();
                break;
            case 'cookie_jar':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\CookieJar();
                break;
            case 'cookie':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Cookie();
                break;
            case 'count':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Count();
                break;
            case 'currency':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Currency();
                break;
            case 'custom_template':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\CustomTemplate();
                break;
            case 'datetime_range':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\DatetimeRange();
                break;
            case 'debugger':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Debugger();
                break;
            case 'defaultmapcenter':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\DefaultMapCenter();
                break;
            case 'ed':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Ed();
                break;
            case 'elapsed':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Elapsed();
                break;
            case 'favorite':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Favorite();
                break;
            case 'feedback':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Feedback();
                break;
            case 'fgal_browse':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FgalBrowse();
                break;
            case 'file_selector':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FileSelector();
                break;
            case 'filegal_manager_url':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FileGalManagerUrl();
                break;
            case 'filegal_uploader':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FileGalUploader();
                break;
            case 'fileinfo':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FileInfo();
                break;
            case 'formitem':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FormItem();
                break;
            case 'help':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Help();
                break;
            case 'html_body_attributes':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\HtmlBodyAttributes();
                break;
            case 'html_select_date':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\HtmlSelectDate();
                break;
            case 'html_select_duration':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\HtmlSelectDuration();
                break;
            case 'html_select_time':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\HtmlSelectTime();
                break;
            case 'icon':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Icon();
                break;
            case 'initials_filter_links':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\InitialsFilterLinks();
                break;
            case 'interactivetranslation':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\InteractiveTranslation();
                break;
            case 'js_insert_icon':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\JsInsertIcon();
                break;
            case 'js_maxlength':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\JsMaxLength();
                break;
            case 'jscalendar':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\JsCalendar();
                break;
            case 'jspopup':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\JsPopup();
                break;
            case 'like':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Like();
                break;
            case 'listfilter':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ListFilter();
                break;
            case 'lock':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Lock();
                break;
            case 'memusage':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\MemUsage();
                break;
            case 'menu':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Menu();
                break;
            case 'module':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Module();
                break;
            case 'modulelist':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ModuleList();
                break;
            case 'monitor_link':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\MonitorLink();
                break;
            case 'multilike':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\MultiLike();
                break;
            case 'norecords':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\NoRecords();
                break;
            case 'notification_link':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\NotificationLink();
                break;
            case 'obj_in_cat':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjInCat();
                break;
            case 'object_title':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectTitle();
                break;
            case 'object_link':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectLink();
                break;
            case 'object_score':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectScore();
                break;
            case 'object_selector_multi':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectSelectorMulti();
                break;
            case 'object_selector':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectSelector();
                break;
            case 'object_type':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectType();
                break;
            case 'page_alias':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\PageAlias();
                break;
            case 'page_in_structure':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\PageInStructure();
                break;
            case 'payment':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Payment();
                break;
            case 'permission_link':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\PermissionLink();
                break;
            case 'pluralize':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Pluralize();
                break;
            case 'poll':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Poll();
                break;
            case 'popup':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Popup();
                break;
            case 'preference':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Preference();
                break;
            case 'profilesymbolvalue':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ProfileSymbolValue();
                break;
            case 'query':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Query();
                break;
            case 'quotabar':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Quotabar();
                break;
            case 'rating':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Rating();
                break;
            case 'rating_choice':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RatingChoice();
                break;
            case 'rating_override_menu':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RatingOverrideMenu();
                break;
            case 'rating_result_avg':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RatingResultAvg();
                break;
            case 'rating_result':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RatingResult();
                break;
            case 'rcontent':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Rcontent();
                break;
            case 'redirect':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Redirect();
                break;
            case 'reindex_file_pixel':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ReindexFilePixel();
                break;
            case 'router_params':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RouterParams();
                break;
            case 'rss':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Rss();
                break;
            case 'sameurl':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\SameUrl();
                break;
            case 'scheduler_params':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\SchedulerParams();
                break;
            case 'sefurl':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Sefurl();
                break;
            case 'select_all':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\SelectAll();
                break;
            case 'service_inline':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ServiceInline();
                break;
            case 'service':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Service();
                break;
            case 'set':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Set();
                break;
            case 'show_short':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ShowShort();
                break;
            case 'svn_lastup':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\SvnLastup();
                break;
            case 'svn_rev':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\SvnRev();
                break;
            case 'syntax':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Syntax();
                break;
            case 'thumb':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Thumb();
                break;
            case 'ticket':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Ticket();
                break;
            case 'toolbars':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Toolbars();
                break;
            case 'tracker_item_status_icon':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerItemStatusIcon();
                break;
            case 'trackerfields':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerFields();
                break;
            case 'trackerheader':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerHeader();
                break;
            case 'trackerinput':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerInput();
                break;
            case 'trackeroutput':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerOutput();
                break;
            case 'trackerrules':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerRules();
                break;
            case 'treetable':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TreeTable();
                break;
            case 'user_registration':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\UserRegistration();
                break;
            case 'user_selector':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\UserSelector();
                break;
            case 'var_dump':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\VarDump();
                break;
            case 'vimeo_uploader':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\VimeoUploader();
                break;
            case 'wiki_diff':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\WikiDiff();
                break;
            case 'wikistructure':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\WikiStructure();
                break;
        }
        return $this->functionHandlers[$functionName] ?? null;
    }

    public function getOutputFilters(): array
    {
        if (isset($_REQUEST['highlight']) || (isset($prefs['feature_referer_highlight']) && $prefs['feature_referer_highlight'] == 'y')) {
            $this->outputFilters[] = new \SmartyTiki\Filter\Output\Highlight();
        }

        if (! empty($prefs['feature_sefurl_filter']) && $prefs['feature_sefurl_filter'] === 'y') {
            $this->outputFilters[] = new \SmartyTiki\Filter\Output\Sefurl();
        }

        return $this->outputFilters;
    }

    public function getPreFilters(): array
    {
        global $prefs;

        $this->preFilters = [
            new \SmartyTiki\Filter\Pre\Tr(),
            new \SmartyTiki\Filter\Pre\Jq()
        ];

        if (! empty($prefs['log_tpl']) && $prefs['log_tpl'] === 'y') {
            $this->preFilters[] = new \SmartyTiki\Filter\Pre\LogTpl();
        }

        return $this->preFilters;
    }

    /**
     * Smarty modifier strtolower
     * --------------------------
     * Purpose: Make a string lowercase - using default PHP function
     *
     * @param string $string   The string to be lowercased.
     * @return string
     */
    public function smartyModifierStrtolower($string)
    {
        return strtolower($string);
    }

    /**
     * Smarty modifier addslashes
     * --------------------------
     * Purpose: Quote string with slashes
     *
     * @param string $string   The string to be escaped.
     * @return string          A string with backslashes added before characters that need to be escaped. These characters are: ',",\, NUL (the NUL byte)
     */
    public function smartyModifierAddslashes($string)
    {
        return addslashes($string);
    }

    /**
     * Smarty modifier array_reverse
     * -----------------------------
     * Purpose: Reverse the order of array elements
     * @param array $array          The entry table.
     * @param bool  $preserve_key   Optional. If set to true, numeric keys will be preserved. Non-numeric keys will not be affected by this configuration, and will always be preserved.
     * @return array                The array in reverse order.
     */
    public function smartyModifierArrayreverse($array, $preserve_keys = false)
    {
        return array_reverse($array, $preserve_keys);
    }

    /**
     * Smarty modifier array_key_exists
     * --------------------------------
     * Purpose: Checks if the given key or index exists in the array
     * @param int|string $key — Value to check.
     * @param array|ArrayObject $array — An array with keys to check.
     * @return bool — true on success or false on failure.
     */
    public function smartyModifierArrayKeyExists($key, $array)
    {
        return array_key_exists($key, $array);
    }

    public function smartyModifierCount($arrayOrObject, $mode = 0)
    {
        if ($arrayOrObject instanceof \Countable || is_array($arrayOrObject)) {
            return count($arrayOrObject, (int) $mode);
        } elseif ($arrayOrObject === null) {
            return 0;
        }
        return 1;
    }

    public function smartyModifierIsarray($array)
    {
        return is_array($array);
    }

    public function smartyModifierIsNumeric($value)
    {
        return is_numeric($value);
    }

    /**
     * Smarty modifer json_decode
     * --------------------------
     * Purpose: Decode a JSON string. Get a string JSON encoded and convert it into a PHP value.
     *
     * @param string    $json          The JSON string
     * @param bool      $associative
     * @param int       $depth
     * @param int       $flags
     * @return mixed
     * @see https://php.net/manual/en/function.json-decode.php for more details about params
     */
    public function smartyModifierJsonDecode($json, $associative = null, $depth = 512, $flags = 0)
    {
        return json_decode($json, $associative, $depth, $flags);
    }

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Name:     lcfirst
    * Purpose:  lowercase the initial character in a string
    * -------------------------------------------------------------
    */
    public function smartyModifierLcfirst($s)
    {
        return strtolower($s[0]) . substr($s, 1);
    }

    /**
    * Smarty modifier max
    * -------------------------------------------------------------
    *
    * Purpose:  Find highest value from given values. This mmodifer implements the PHP max function.
    * ----------------------------------------------------------------------------------------------
     * @param mixed      $value Any comparable value
     * @param mixed      $values Any comparable value
     * @return mixed
     * @see https://php.net/manual/en/function.json-decode.php for more details about params, returned values and how it works.
    */
    public function smartyModifierMax(mixed $value, mixed ...$values): mixed
    {
        return max($value, $values);
    }

    /**
     * Smarty modifier md5
     * -------------------
     * Purpose: Calculate the md5 of a string
     *
     * @param string $string  The string
     * @param bool   $binary  Optional. If set to true, then the md5 is returned in raw binary format with a length of 16.
     * @return string         Returns the md5 of the string, as a 32-character hexadecimal number.
     */
    public function smartyModifierMd5($string, $binary = false)
    {
        return md5($string, $binary);
    }

   /**
    * Smarty modifier preg_match
    * --------------------------
    * Purpose: Perform a regular expression match
    * @param string $pattern     The pattern to search for, as a string.
    * @param string $subject     The input string.
    * @param array  $matches
    * @param int    $flags
    * @param int    $offset
    * @return int|flase
    * @see https://php.net/manual/en/function.preg-match.php for details about the params description
    */
    public function smartyModifierPregMatch($pattern, $subject, $matches = null, $flags = 0, $offset = 0)
    {
        return preg_match($pattern, $subject, $matches, $flags, $offset);
    }

    /**
    * Smarty modifier preg_match_all
    * ------------------------------
    * Purpose: Perform a global regular expression match
    * @param string $pattern     The pattern to search for, as a string.
    * @param string $subject     The input string.
    * @param array  $matches
    * @param int    $flags
    * @param int    $offset
    * @return int|flase
    * @see https://php.net/manual/en/function.preg-match.php for details about the params description
    */
    public function smartyModifierPregMatchAll($pattern, $subject, $matches = null, $flags = 0, $offset = 0)
    {
        return preg_match_all($pattern, $subject, $matches, $flags, $offset);
    }

    /**
     * Smarty plugin
     * @package Smarty
     * @subpackage plugins
     */

    /**
     * Smarty replacei modifier plugin
     *
     * Type:     modifier<br>
     * Name:     replacei<br>
     * Purpose:  Returns a case insensitive replaced string.
     *           Same arguments as PHP str_ireplace function.
     */
    public function smartyModifierReplacei($string, $find, $replacement)
    {
        return str_ireplace($find, $replacement, $string);
    }

    /**
     * Smarty reverse_array modifier plugin
     *
     * Type:     modifier<br>
     * Name:     reverse_array<br>
     * Purpose:  reverse arrays
     * @param array
     * @return array
     */
    public function smartyModifierReverseArray($array)
    {
        return array_reverse($array);
    }

    /**
     * Smarty "sizeof" modifier plugin
     *
     * Purpose: Same as "count" modifier. Counts all elements in an array or in a Countable object
     *
     * @param Countable|array $value
     * @param int $mode - [optional]
     * @return int<0, max>
     */
    public function smartyModifierSizeof($value, $mode = COUNT_NORMAL)
    {
        return sizeof($value, $mode);
    }

    /**
     * Smarty substring modifier plugin
     *
     * Type:     modifier<br>
     * Name:     substring<br>
     * Purpose:  Returns a substring of string.  Same arguments as
     *           PHP substr function.
     * @link based on substr(): http://www.zend.com/manual/function.substr.php
     * @author   Mike Kerr <tiki.kerrnel at kerris dot com>
     * @param string
     * @param position: start position of substring (default=0, negative starts N from end)
     * @param length: length of substring (default=to end of string; negative=left N from end)
     * @return string
     */
    public function smartyModifierSubstring($string, $position = 0, $length = null)
    {

        if ($length == null) {
            return substr($string, $position);
        } else {
            return substr($string, $position, $length);
        }
    }

    /**
     * Smarty modifier stristr
     * -----------------------
     * Purpose: Returns a haystack substring, from the first occurrence case insensitive of needle (inclusive) to the end of the string.
     *
     * @param string $haystack       The string in which to search.
     * @param string $needle         The string to look for.
     * @param bool   $before_needle  Optional. If true, stristr() returns the part of haystack before the first occurrence of needle (needle excluded).
     * @return string|false          Returns the matching part of the string. If needle is not found, the function returns false.
     */
    public function smartyModifierStristr($haystack, $needle, $before_needle = false)
    {
        return stristr($haystack, $needle, $before_needle);
    }

    /**
     * Smarty modifier strstr
     * -----------------------
     * Purpose: Find the first occurrence of a string. Returns part of haystack string starting from and including the first occurrence of needle to the end of haystack.
     *
     * @param string $haystack       The input string in which to search.
     * @param string $needle         The string to search for.
     * @param bool   $before_needle  Optional. If true, strstr modifier returns the part of haystack before the first occurrence of needle (needle excluded).
     * @return string|false          Returns the matching part of the string. If needle is not found, the function returns false.
     */
    public function smartyModifierStrstr($haystack, $needle, $before_needle = false)
    {
        return strstr($haystack, $needle ?? '', $before_needle);
    }

    /**
     * Smarty modifier strpos
     * ----------------------
     * Purpose: Find the position of the first occurrence in a string
     *
     * @param string $haystack     The string in which to search.
     * @param string $needle       The string to search for.
     * @param int<0, max>  $offset       Optional. The position from which to start the search
     * @return int|false
     *
     * Synthax: {$haystack|strpos:$needle:$offset}
     */
    public function smartyModifierStrpos($haystack, $needle, $offset = 0)
    {
        return strpos($haystack, $needle, $offset);
    }

    /**
     * @param string $string - Required. Specifies the string to check
     * @param string $chars - Optional. Specifies which characters to remove from the string. If omitted, the following characters will be removed: " \t\n\r\0\x0B"
     *
     * @return string - String trimed
     */
    public function smartyModifierTrim($string, $chars = null)
    {
        return empty($chars) ? trim($string) : trim($string, $chars);
    }

    /**
     * Smarty nodifier ucfirst
     * -----------------------
     * Purpose: Make a string's first character uppercase
     *
     * @param string      $string   The input string.
     * @return string               A string with the first character of string capitalized, if that character is an ASCII character in the range from "a" (0x61) to "z" (0x7a).
     */
    public function smartyModifierUcfirst($string)
    {
        return ucfirst($string);
    }

    /**
     * Smarty nodifier ucwords
     * -----------------------
     * Purpose: Uppercase the first character of each word in a string
     *
     * @param string  $string     The input string.
     * @param string  $separator  Optional. contains the word separator characters.
     * @return string             A string with the first character of each word in string capitalized, if that character is an ASCII character between "a" (0x61) and "z" (0x7a)
     */
    public function smartyModifierUcwords($string, $separator = " \t\r\n\f\v")
    {
        return ucwords($string, $separator);
    }

    /**
     * Smarty nodifier urlencode
     * -------------------------
     * Purpose: URL-encodes string
     *
     * @param string  $string     The string to be encoded.
     * @return string             A string in which all non-alphanumeric characters except -_. have been replaced with a percent (%) sign followed by two hex digits and spaces encoded as plus (+) signs.
     */
    public function smartyModifierUrlencode($string)
    {
        return urlencode($string);
    }

    /**
     * Smarty modifier var_dump
     * ------------------------
     * Purpose: Dumps information about a variable
     *
     * @param mixed $value
     * @return void
     */
    public function smartyModifierVardump($value)
    {
        return var_dump($value);
    }

    public function smartyModifierZoneIsEmpty($zoneName)
    {
        return zone_is_empty($zoneName);
    }
}
