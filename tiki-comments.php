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
            }
        });
        onDOMElementRemoved("single-spa-application:@vue-mf/comments", function () {
            window.unregisterApplication("@vue-mf/comments");
        });
');

$smarty->assign('mid', 'tiki-comments.tpl');
$smarty->display("tiki.tpl");
