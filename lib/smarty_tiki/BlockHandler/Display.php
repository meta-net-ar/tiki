<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;

/**
 * Smarty plugin Display
 *
 * \brief Smarty plugin to display content only to some groups, friends or combination of all per specified user(s)
 * (if user is not specified, current user is used)
 * ex.: {display groups='Anonymous,-Registered,foo' friends=$f_42[ error='You may not see this item']}$f_1...$f_9///else///Become friend with $_42 first{/display}
 * TODO : Re-implement friend filter
 */
class Display extends Base
{
    public function handle($params, $content, Template $template, &$repeat)
    {
        global $prefs, $user;
        $userlib = \TikiLib::lib('user');

        if ($repeat) {
            return;
        }
        $ok = true;
        if (! empty($params['groups'])) {
            $groups = explode(',', $params['groups']);
            $userGroups = $userlib->get_user_groups($user);
        }

        $content = explode('///else///', $content);

        if (! empty($params['error'])) {
            $errmsg = $params['error'];
        } elseif (empty($params['error']) && isset($groups)) {
            $errmsg = '';
        } else {
            $errmsg = 'Smarty block.display.php: Missing error param';
        }

        $anon = false; // see the workaround to exclude Registered below

        foreach ($groups as $gr) {
            $gr = trim($gr);
            if ($gr == 'Anonymous') {
                $anon = true;
            }
            if (substr($gr, 0, 1) == '-') {
                $nogr = substr($gr, 1);
                if ((in_array($nogr, $userGroups) && $nogr != 'Registered') or (in_array($nogr, $userGroups) && $nogr == 'Registered' && $anon == true)) {
                    // workaround to display to Anonymous only if Registered excluded (because Registered includes Anonymous always)
                    $ok = false;
                    $anon = false;
                }
            } elseif (! in_array($gr, $userGroups) && $anon == false) {
                $ok = false;
            } else {
                $ok = true;
            }
        }

        /* is it ok ? */
        if (! $ok) {
            if (isset($content[1])) {
                return $content[1];
            } else {
                return $errmsg;
            }
        } else {
            return $content[0];
        }
    }
}
