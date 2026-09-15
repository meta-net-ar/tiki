<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once('tiki-setup.php');

$smarty = TikiLib::lib('smarty');
$access = TikiLib::lib('access');
$access->check_permission('tiki_p_view');

// Mirrors the filtering Services_Comment_Controller::action_list applies, so the page can only
// hand the module a context the service would accept. Missing parameters filter down to null.
$commentsType = (string) $jitRequest->type->alphaspace();
$commentsObjectId = (string) ($commentsType === 'wiki page' ? $jitRequest->objectId->pagename() : $jitRequest->objectId->digits());

// Lands inside an inline <script>, so escape the characters that could break out of it.
$commentsContext = json_encode(
    ['type' => $commentsType, 'objectId' => $commentsObjectId],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

$headerlib = TikiLib::lib('header');
$headerlib->add_js_module('
        import "@vue-mf/comments";
    ');
$headerlib->add_jq_onready('
        window.registerApplication({
            name: "@vue-mf/comments",
            app: () => importShim("@vue-mf/comments"),
            activeWhen: (location) => {
                let condition = true;
                return condition;
            },
            customProps: {
                commentsContext: ' . $commentsContext . ',
            },
        });
        onDOMElementRemoved("single-spa-application:@vue-mf/comments", function () {
            window.unregisterApplication("@vue-mf/comments");
        });
');

$smarty->assign('mid', 'tiki-comments.tpl');
$smarty->display("tiki.tpl");
