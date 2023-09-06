<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Score_Controller
{
    public function setUp()
    {
    }

    public function action_create_score_event($input)
    {

        $eventType = $input->eventType->xss();

        if ($input->rowOnly->text() == 'y') {
            $rowOnly = 1;
        } else {
            $rowOnly = 0;
        }

        if ($input->rowCount->text() > 0) {
            $rowCount = $input->rowCount->int();
        } else {
            $rowCount = 0;
        }

        return  [
            'eventType' => $eventType,
            'rowOnly' => $rowOnly,
            'rowCount' => $rowCount,
        ];
    }
}
