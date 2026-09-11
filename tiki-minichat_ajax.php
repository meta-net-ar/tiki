<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');
$access->check_feature('feature_minichat');
$access->check_permission('tiki_p_chat');
header("Pragma: public");
header("Pragma: no-cache");
header("Cache-Control: no-cache, must-revalidate, no-store, post-check=0, pre-check=0, max-age=0");
header("Expires: Tue, 27 Jul 1997 02:30:00 GMT"); // Date in the past
header('Content-Type: application/javascript; charset=utf-8');
$timeout_min = 1000;
$timeout_max = 15000;
$timeout_inc = 1000;
$lasttimeout = (int)$_REQUEST['lasttimeout'];
if ($lasttimeout < $timeout_min) {
    $lasttimeout = $timeout_min;
}
$chans = explode(',', $_REQUEST['chans']);
/**
 * @param $channel
 * @return string
 */
function escapechannel($channel)
{
    $channel = preg_replace('/[^a-zA-Z0-9\-\_]/i', '', $channel);
    $channel = substr($channel, 0, 30);
    return '#' . $channel;
}

/**
 * @param $chans
 */
function initchannelssession($chans)
{
    $_SESSION['minichat_channels'] = [];
    foreach ($chans as $chan) {
        $vals = explode(';', $chan);
        $channel = escapechannel($vals[0]);
        $_SESSION['minichat_channels'][] = $channel;
    }
}
if (isset($_REQUEST['msg_chat'])) {
    $msg = $_REQUEST['msg_chat'];
    $msg = strtr($msg, "\n\r\t", "   ");
    $msgon = $_REQUEST['msgon'] ?? null;
    if (empty($msg)) {
        $msgon = null;
    }
} else {
    $msg = '';
    $msgon = null;
}
if (str_starts_with($msg, '/')) {
    $words = explode(' ', $msg);
    switch ($words[0]) {
        case '/join':
            $words[1] = escapechannel($words[1]);
            echo "minichat_addchannel('" . $words[1] . "');\n";
            if (! isset($_SESSION['minichat_channels'])) {
                initchannelssession($chans);
            }
            $k = in_array($words[1], $_SESSION['minichat_channels']);
            if ($k === false) {
                $_SESSION['minichat_channels'][] = $words[1];
            }
            break;
    }
}
foreach ($chans as $chan) {
    $vals = explode(';', $chan);
    $channel = escapechannel($vals[0]);
    $lastid = (int)$vals[1];
    $closed = false;
    if (($msgon == $channel) && (! is_null($channel))) {
        $time = time();
        if (str_starts_with($msg, '/')) {
            $words = explode(' ', $msg);
            switch ($words[0]) {
                case '/part':
                case '/close':
                    echo "minichat_removechannel('" . $channel . "');\n";
                    $closed = true;
                    if (! isset($_SESSION['minichat_channels'])) {
                        initchannelssession($chans);
                    }
                    $k = array_search($words[1], $_SESSION['minichat_channels']);
                    if ($k !== false) {
                        unset($_SESSION['minichat_channels'][$k]);
                    }
                    break;
            }
        } else {
                $tikilib->query("INSERT INTO tiki_minichat (nick,user,ts,channel,msg) VALUES (?,?,?,?,?)", [\SmartyTiki\Modifier\Username::apply($user), $user, $tikilib->now, $channel, $msg]);
                $lastid = 0;
        }
            $lasttimeout = $timeout_min;
    }
    if ($closed) {
        continue;
    }
    if (empty($channel)) {
        continue;
    }
    if ($lastid > 0) {
        $result = $tikilib->query("SELECT MAX(id) AS maxid FROM tiki_minichat WHERE channel=?", [$channel]);
        $res = $result->fetchRow();
        $maxid = $res['maxid'];
        if ($maxid != $lastid) {
            $lastid = 0;
            $lasttimeout = $timeout_min;
        } else {
            $lasttimeout += $timeout_inc;
        }
    }
    if ($lastid == 0) {
        $result = $tikilib->query("SELECT * FROM tiki_minichat WHERE channel=? ORDER by id desc LIMIT 100", [$channel]);
        // collect rows, reverse to chronological order (oldest first)
        $rows = [];
        while ($row = $result->fetchRow()) {
            $rows[] = $row;
        }
        $rows = array_reverse($rows);

        $msgtotal = "";
        $prev_user = null;
        foreach ($rows as $row) {
            // compute time display (same logic as before)
            $daytmes = date("d/m/y", $row['ts']);
            $daytnow = date("d/m/y");
            if ($daytmes == $daytnow) {
                $t = date("H:i", $row['ts']);
            } else {
                $formats = [
                    'DMY' => "d/m/y H:i",
                    'DYM' => "d/y/m H:i",
                    'MDY' => "m/d/y H:i",
                    'MYD' => "m/y/d H:i",
                    'YDM' => "y/d/m H:i",
                    'YMD' => "y/m/d H:i",
                ];
                $format = $formats[$prefs['display_field_order']] ?? "H:i";
                $t = date($format, $row['ts']);
            }

            $nick_html = ($row['nick'] == '' ? "<em>" . tra('Anonymous') . "</em>" : \SmartyTiki\Modifier\UserLink::apply($row['user']));
            $msg_html = htmlentities($row['msg'], ENT_QUOTES, 'UTF-8');
            $side_class = ($row['user'] == $user) ? 'mine' : 'other';

            $show_header = ($prev_user !== $row['user']);

            $align_class = ($side_class === 'mine') ? 'justify-content-end' : 'justify-content-start';
            $card_classes = ($side_class === 'mine') ? 'bg-info-subtle text-info-emphasis' : 'bg-light-subtle text-body';


            $bubble = "<div class='d-flex mb-2 {$align_class}'>";
            $bubble .= "<div class='{$card_classes}' style='max-width:70%'>";
            $bubble .= "<div class='p-2'>";
            if ($show_header) {
                $bubble .= "<div class='fw-bold small'>" . $nick_html . "</div>";
            }
            $bubble .= "<div class='card-text'>{$msg_html}</div>";
            $bubble .= "<div class='text-end'><small class='text-muted'>{$t}</small></div>";
            $bubble .= "</div></div></div>";

            $msgtotal .= $bubble; // append to keep chronological order top->bottom
            $prev_user = $row['user'];
        }

        // update lastid with the newest message id (last element in $rows)
        if (! empty($rows)) {
            $newlast = $rows[count($rows) - 1]['id'];
            $lastid = (int)$newlast;
            echo "minichat_updatelastid(" . json_encode($channel) . ", $lastid);\n";
        }
        $editlib = TikiLib::lib('edit');
        $msgtotal = $editlib->convertSmileysToUnicode($msgtotal);
        echo "document.getElementById('minichatdiv_'+minichat_getchanid(" . json_encode($channel) . ")).innerHTML=" . json_encode($msgtotal) . ";\n";
        echo "document.getElementById('minichat').scrollTop=99999;\n";
    }
}
echo "minichatlasttimeout = $lasttimeout;\n";
if (! isset($_REQUEST['msg'])) {
    echo "setTimeout('minichat_update()', $lasttimeout);\n";
}
