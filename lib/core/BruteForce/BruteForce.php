<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\BruteForce;

use TikiDb;

class BruteForce
{
    private $bruteForceTable;
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
        $this->bruteForceTable = TikiDb::get()->table('tiki_bruteforce_attempts');
    }

    public function attempt($operation, $properties)
    {
        $properties = $this->normalizeProperties($properties);

        foreach ($properties as $key => $value) {
            $propertyJson = json_encode([$key => $value]);
            $propertyHash = hash('sha256', $propertyJson);

            $this->db->queryException(
                'INSERT INTO `tiki_bruteforce_attempts` (`operation`, `properties`, `properties_hash`, `attempt_time`, `attempt_count`)'
                . ' VALUES (?, ?, ?, ?, 1)'
                . ' ON DUPLICATE KEY UPDATE `properties` = VALUES(`properties`), `attempt_time` = VALUES(`attempt_time`), `attempt_count` = `attempt_count` + 1',
                [$operation, $propertyJson, $propertyHash, time()]
            );
        }
    }

    private function getAttemptInfo($operation, $properties)
    {
        global $prefs;

        $properties = $this->normalizeProperties($properties);
        $maxAttemptCount = 0;
        $latestAttemptTime = 0;
        $currentTime = time();
        $forgetTime = $currentTime - (intval($prefs['bruteforce_forget_time']) * 60);

        foreach ($properties as $key => $value) {
            $propertyJson = json_encode([$key => $value]);
            $propertyHash = hash('sha256', $propertyJson);

            $keys = [
                'operation' => $operation,
                'properties_hash' => $propertyHash,
            ];

            $attemptRecord = $this->bruteForceTable->fetchRow(['attempt_count', 'attempt_time'], $keys);

            if (! empty($attemptRecord)) {
                if ($attemptRecord['attempt_time'] < $forgetTime) {
                    $this->bruteForceTable->deleteMultiple($keys);
                    continue;
                }
                $maxAttemptCount = max($maxAttemptCount, $attemptRecord['attempt_count']);
                $latestAttemptTime = max($latestAttemptTime, $attemptRecord['attempt_time']);
            }
        }

        return [
            'maxAttemptCount' => $maxAttemptCount,
            'latestAttemptTime' => $latestAttemptTime,
            'currentTime' => $currentTime
        ];
    }

    private function calculateNextAllowedTime($maxAttemptCount, $latestAttemptTime)
    {
        global $prefs;

        if ($maxAttemptCount > 0) {
            $delay = intval($prefs['bruteforce_initial_delay']) * pow(1 + (intval($prefs['bruteforce_growth_rate']) / 100), $maxAttemptCount - 1);
            return $latestAttemptTime + $delay;
        }

        return 0;
    }

    public function isOperationAllowed($operation, $properties, $registerAttempt = true)
    {
        $properties = $this->normalizeProperties($properties);
        $info = $this->getAttemptInfo($operation, $properties);
        $nextAllowedTime = $this->calculateNextAllowedTime($info['maxAttemptCount'], $info['latestAttemptTime']);

        if ($nextAllowedTime > 0 && $info['currentTime'] < $nextAllowedTime) {
            if ($registerAttempt) {
                $this->attempt($operation, $properties);
            }
            return false;
        }

        if ($registerAttempt) {
            $this->attempt($operation, $properties);
        }
        return true;
    }

    public function getNextAllowedTime($operation, $properties)
    {
        $info = $this->getAttemptInfo($operation, $properties);
        return $this->calculateNextAllowedTime($info['maxAttemptCount'], $info['latestAttemptTime']);
    }

    public function getWaitTime($operation, $properties)
    {
        return max(0, $this->getNextAllowedTime($operation, $properties) - time());
    }

    public function success($operation, $properties)
    {
        $properties = $this->normalizeProperties($properties);

        foreach ($properties as $key => $value) {
            $propertyJson = json_encode([$key => $value]);
            $propertyHash = hash('sha256', $propertyJson);

            $keys = [
                'operation' => $operation,
                'properties_hash' => $propertyHash,
            ];

            $this->bruteForceTable->deleteMultiple($keys);
        }
    }

    private function normalizeProperties($properties)
    {
        return array_filter(
            $properties,
            function ($value) {
                return $value !== null && $value !== false && $value !== '';
            }
        );
    }
}
