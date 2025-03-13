<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Maintenance;

use DateTime;
use TikiLib;

class Maintenance
{
    private DateTime $currentTime;

    public function __construct(?DateTime $time = null)
    {
        $this->currentTime = $time ?: new DateTime('now');
    }

    public function isTime(): bool
    {
        return $this->isOnceOffTime() || $this->isRecurringTime();
    }

    public function getMessage(): string
    {
        $message = '';
        if ($this->isOnceOffTime()) {
            $message = $this->getOnceOffDuringMessage();
        } elseif ($this->isRecurringTime()) {
            $message = $this->getRecurrentDuringMessage();
        }

        return $message;
    }

    public function isRecurringTime(): bool
    {
        global $prefs;

        if (
            $prefs['maintenanceRecurrentEnable'] === 'y'
            && ! empty($prefs['maintenanceRecurrentStartTime'])
            && preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $prefs['maintenanceRecurrentStartTime'])
            && ! empty($prefs['maintenanceRecurrentDuration'])
            && (int)$prefs['maintenanceRecurrentDuration'] > 0
            && in_array(date('w'), $prefs['maintenanceEnableWeekdays'] ?? [])
        ) {
            $startMaintenanceTime = new DateTime($prefs['maintenanceRecurrentStartTime']);
            $endMaintenanceTime = (clone $startMaintenanceTime)->modify('+' . $prefs['maintenanceRecurrentDuration'] . ' minutes');
            return $startMaintenanceTime <= $this->currentTime && $this->currentTime < $endMaintenanceTime;
        }

        return false;
    }

    public function isOnceOffTime(): bool
    {
        global $prefs;

        if (
            $prefs['maintenanceOnceOffEnable'] === 'y'
            && ! empty($prefs['maintenanceOnceOffStartDate'])
            && preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $prefs['maintenanceOnceOffStartDate'])
            && ! empty($prefs['maintenanceOnceOffStartTime'])
            && preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $prefs['maintenanceOnceOffStartTime'])
            && ! empty($prefs['maintenanceOnceOffDuration'])
            && (int)$prefs['maintenanceOnceOffDuration'] > 0
        ) {
            $startTime = $prefs['maintenanceOnceOffStartDate'] . ' ' . $prefs['maintenanceOnceOffStartTime'];
            $startMaintenanceTime = new DateTime($startTime);
            $endMaintenanceTime = (clone $startMaintenanceTime)->modify('+' . $prefs['maintenanceOnceOffDuration'] . ' minutes');
            return $startMaintenanceTime <= $this->currentTime && $this->currentTime < $endMaintenanceTime;
        }

        return false;
    }

    public function getOnceOffDuringMessage(): string
    {
        global $prefs;

        $startTime = $prefs['maintenanceOnceOffStartDate'] . ' ' . $prefs['maintenanceOnceOffStartTime'];
        $startMaintenanceTime = new DateTime($startTime);
        $endMaintenanceTime = (clone $startMaintenanceTime)->modify('+' . $prefs['maintenanceOnceOffDuration'] . ' minutes');
        $remainMinutes = $this->getRemainingMinutes($endMaintenanceTime);
        $messageTemplate = $prefs['maintenanceOnceOffDuringMessage'];
        return str_replace('DOWN', $remainMinutes, $messageTemplate);
    }

    public function getRecurrentDuringMessage(): string
    {
        global $prefs;

        $startMaintenanceTime = new DateTime($prefs['maintenanceRecurrentStartTime']);
        $endMaintenanceTime = (clone $startMaintenanceTime)->modify('+' . $prefs['maintenanceRecurrentDuration'] . ' minutes');
        $remainMinutes = $this->getRemainingMinutes($endMaintenanceTime);
        $messageTemplate = $prefs['maintenanceRecurrentDuringMessage'];
        return str_replace('DOWN', $remainMinutes, $messageTemplate);
    }

    public function isPreMaintenanceTime(): bool
    {
        return $this->isOnceOffPreMaintenanceTime() || $this->isRecurringPreMaintenanceTime();
    }

    public function getPreMaintenanceMessage(): string
    {
        $message = '';
        if ($this->isOnceOffPreMaintenanceTime()) {
            $message = $this->getOnceOffPreMaintenanceMessage();
        } elseif ($this->isRecurringPreMaintenanceTime()) {
            $message = $this->getRecurrentPreMaintenanceMessage();
        }

        return $message;
    }

    public function isRecurringPreMaintenanceTime(): bool
    {
        global $prefs;

        if (
            $prefs['maintenanceRecurrentEnable'] === 'y'
            && ! empty($prefs['maintenanceRecurrentStartTime'])
            && preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $prefs['maintenanceRecurrentStartTime'])
            && ! empty($prefs['maintenanceTimeBeforeDisplayMessage'])
            && (int)$prefs['maintenanceTimeBeforeDisplayMessage'] > 0
            && in_array(date('w'), $prefs['maintenanceEnableWeekdays'] ?? [])
        ) {
            $startMaintenanceTime = new DateTime($prefs['maintenanceRecurrentStartTime']);
            $notificationStartTime = (clone $startMaintenanceTime)->modify('-' . $prefs['maintenanceTimeBeforeDisplayMessage'] . ' minutes');

            return $notificationStartTime <= $this->currentTime && $this->currentTime < $startMaintenanceTime;
        }

        return false;
    }

    public function isOnceOffPreMaintenanceTime(): bool
    {
        global $prefs;

        if (
            $prefs['maintenanceOnceOffEnable'] === 'y'
            && ! empty($prefs['maintenanceOnceOffStartDate'])
            && preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-([0-2][0-9]|3[01])$/', $prefs['maintenanceOnceOffStartDate'])
            && ! empty($prefs['maintenanceOnceOffStartTime'])
            && preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $prefs['maintenanceOnceOffStartTime'])
            && ! empty($prefs['maintenanceTimeBeforeDisplayMessage'])
            && (int)$prefs['maintenanceTimeBeforeDisplayMessage'] > 0
        ) {
            $startTime = $prefs['maintenanceOnceOffStartDate'] . ' ' . $prefs['maintenanceOnceOffStartTime'];
            $startMaintenanceTime = new DateTime($startTime);
            $notificationStartTime = (clone $startMaintenanceTime)->modify('-' . $prefs['maintenanceTimeBeforeDisplayMessage'] . ' minutes');

            return $notificationStartTime <= $this->currentTime && $this->currentTime < $startMaintenanceTime;
        }

        return false;
    }

    public function getOnceOffPreMaintenanceMessage(): string
    {
        global $prefs;

        $startTime = $prefs['maintenanceOnceOffStartDate'] . ' ' . $prefs['maintenanceOnceOffStartTime'];
        $remainMinutes = $this->getRemainingMinutes($startTime);
        $downMinutes = (int)$prefs['maintenanceOnceOffDuration'];
        $messageTemplate = $prefs['maintenanceOnceOffPreMessage'];
        return str_replace(['TIME', 'DOWN'], [$remainMinutes, $downMinutes], $messageTemplate);
    }

    public function getRecurrentPreMaintenanceMessage(): string
    {
        global $prefs;

        $remainMinutes = $this->getRemainingMinutes($prefs['maintenanceRecurrentStartTime']);
        $downMinutes = (int)$prefs['maintenanceRecurrentDuration'];
        $messageTemplate = $prefs['maintenanceRecurrentPreMessage'];
        return str_replace(['TIME', 'DOWN'], [$remainMinutes, $downMinutes], $messageTemplate);
    }

    public function isIndexing(): bool
    {
        $unifiedsearchlib = TikiLib::lib('unifiedsearch');
        return $unifiedsearchlib->rebuildInProgress();
    }

    public function getIndexingMessage(): string
    {
        global $prefs;

        $message = '';
        if ($this->isIndexing() && $prefs['maintenanceMessageReindex'] === 'y') {
            $message = $prefs['maintenanceReindexMessage'];
        }

        return $message;
    }

    private function getRemainingMinutes($time)
    {
        $time = $time instanceof DateTime ? $time->format('Y-m-d H:i:s') : $time;
        $startMaintenanceTime = new DateTime($time);
        $diff = $this->currentTime->diff($startMaintenanceTime);
        $remainMinutes = $diff->days * 24 * 60;
        $remainMinutes += $diff->h * 60;
        $remainMinutes += $diff->i;
        if ($remainMinutes < 1) {
            $remainMinutes = tra('less than 1');
        }

        return $remainMinutes;
    }
}
