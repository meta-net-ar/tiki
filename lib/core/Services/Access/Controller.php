<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

/**
 * Class Services_Access_Controller
 */
class Services_Access_Controller
{
    /**
     * Action to bring up the "confirm your action" popup.
     * Designed to be used with the $access->check_authenticity() function to prevent CSRF
     * by including a ticket in the popup form. See the action_delete_topic function at
     * lib/core/Services/Forum/Controller.php for an example
     *
     * @param $input JitFilter
     * @return array
     */
    public function action_confirm($input)
    {
        $ret = $this->prepareReturn($input);
        return $ret;
    }

    public function action_confirm_select($input)
    {
        $ret = $this->prepareReturn($input);
        return $ret;
    }

    /**
     * @param $input    JitFilter
     * @return array
     */
    private function prepareReturn($input)
    {
        // Encode input to neutralize special characters
        $title = htmlspecialchars(! empty($input['title']) ? $input['title'] : tra('Please confirm'), ENT_QUOTES, 'UTF-8');
        $confirmButton = htmlspecialchars(! empty($input['confirmButton']) ? $input['confirmButton'] : tra('OK'), ENT_QUOTES, 'UTF-8');
        $confirmButtonClass = htmlspecialchars(! empty($input['confirmButtonClass']) ? $input['confirmButtonClass'] : 'btn-primary', ENT_QUOTES, 'UTF-8');
        $items = array_map(function ($item) {
            return htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
        }, $input->asArray('items'));

        // Encode custom message and related parameters
        $customMsg = htmlspecialchars(! empty($input['customMsg']) ? $input['customMsg'] : '', ENT_QUOTES, 'UTF-8');
        $customVerb = htmlspecialchars(! empty($input['customVerb']) ? $input['customVerb'] : '', ENT_QUOTES, 'UTF-8');
        $customObject = htmlspecialchars(! empty($input['customObject']) ? $input['customObject'] : '', ENT_QUOTES, 'UTF-8');
        if (empty($customMsg)) {
            if (! empty($customVerb) && ! empty($customObject)) {
                $customMsg = htmlspecialchars(tr('Are you sure you want to %0 the following %1?', $customVerb, $customObject), ENT_QUOTES, 'UTF-8');
            } else {
                $customMsg = count($items) === 1
                    ? htmlspecialchars(tra('Please confirm your action for the following item:'), ENT_QUOTES, 'UTF-8')
                    : htmlspecialchars(tra('Please confirm your action for the following items:'), ENT_QUOTES, 'UTF-8');
            }
        }

        return [
            'items' => $items,
            'confirmAction' => htmlspecialchars($input->confirmAction->word(), ENT_QUOTES, 'UTF-8'),
            'confirmController' => htmlspecialchars($input->confirmController->word(), ENT_QUOTES, 'UTF-8'),
            'extra' => array_map(function ($extra) {
                return htmlspecialchars($extra, ENT_QUOTES, 'UTF-8');
            }, $input->asArray('extra')),
            'toMsg' => htmlspecialchars($input->toMsg->xss(), ENT_QUOTES, 'UTF-8'),
            'toList' => array_map(function ($toListItem) {
                return htmlspecialchars($toListItem, ENT_QUOTES, 'UTF-8');
            }, $input->asArray('toList')),
            'ticket' => htmlspecialchars($input->ticket->striptags(), ENT_QUOTES, 'UTF-8'),
            'title' => $title,
            'help' => htmlspecialchars($input->help->xss(), ENT_QUOTES, 'UTF-8'),
            'customMsg' => $customMsg,
            'confirmButton' => $confirmButton,
            'confirmButtonClass' => $confirmButtonClass,
            'modal' => 1,
            'ajax' => 1,
        ];
    }
}
