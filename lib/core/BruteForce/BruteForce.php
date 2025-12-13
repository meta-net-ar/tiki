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

    public function __construct()
    {
        $this->bruteForceTable = TikiDb::get()->table('tiki_bruteforce_attempts');
    }

    public function attempt($operation, $properties)
    {
        foreach ($properties as $key => $value) {
            $propertyJson = json_encode([$key => $value]);
            $propertyHash = hash('sha256', $propertyJson);

            $keys = [
                'operation' => $operation,
                'properties_hash' => $propertyHash,
            ];

            $data = [
                'operation' => $operation,
                'properties' => $propertyJson,
                'properties_hash' => $propertyHash,
                'attempt_time' => time()
            ];

            $existingRecord = $this->bruteForceTable->fetchRow(['attempt_count'], $keys);
            if ($existingRecord) {
                $data['attempt_count'] = $existingRecord['attempt_count'] + 1;
                $this->bruteForceTable->update($data, $keys);
            } else {
                $data['attempt_count'] = 1;
                $this->bruteForceTable->insert($data);
            }
        }
    }

    private function getAttemptInfo($operation, $properties)
    {
        global $prefs;

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

    public function success($operation, $properties)
    {
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
}
