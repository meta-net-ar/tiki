<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\EmailQueue;

use TikiLib;

class EmailQueueLib
{
    public function listStalledEmailQueues($offset, $maxRecords, $sort_mode, $find)
    {
        $tikilib = TikiLib::lib('tiki');

        if ($find) {
            $mid = ' WHERE (`attempts` > ?)';
            $bindvars = [$find];
        } else {
            $mid = '';
            $bindvars = [];
        }

        $query = 'SELECT * FROM `tiki_mail_queue` ' . $mid . ' ORDER BY ' . $tikilib->convertSortMode($sort_mode);
        $query_cant = 'SELECT COUNT(*) FROM `tiki_mail_queue` ' . $mid;
        $total_cant = 'SELECT COUNT(*) FROM `tiki_mail_queue`';
        $result = $tikilib->query($query, $bindvars, $maxRecords, $offset);
        $cant = $tikilib->getOne($query_cant, $bindvars);
        $total_cant = $tikilib->getOne($total_cant, []);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        $retval = [];
        $retval['data'] = $ret;
        $retval['total_cant'] = $total_cant;
        $retval['max_retries'] = $find;
        $retval['cant'] = $cant;
        return $retval;
    }

    public function resetAttemptsOfEmailQueue($queueId)
    {
        $tikilib = TikiLib::lib('tiki');

        $query = 'UPDATE `tiki_mail_queue` SET `attempts`= 0 WHERE `messageId` = ? ';
        return $tikilib->query($query, $queueId);
    }

    public function deleteEmailQueue($queueId)
    {
        $tikilib = TikiLib::lib('tiki');

        $query = 'DELETE FROM `tiki_mail_queue` WHERE `messageId` = ? ';
        return $tikilib->query($query, $queueId);
    }
}
