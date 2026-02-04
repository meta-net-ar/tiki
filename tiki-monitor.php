<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\Installer\Installer;

require_once('tiki-setup.php');
//TODO Use a pref to handle the list
if (! empty($tikiMonitorRestriction)) {
    if (is_array($tikiMonitorRestriction)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && ! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $aListIp = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $sIpToCheck = $aListIp[0];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && ! empty($_SERVER['REMOTE_ADDR'])) {
            $sIpToCheck = $_SERVER['REMOTE_ADDR'];
        } else {
            $sIpToCheck = null;
        }
        if (in_array($sIpToCheck, $tikiMonitorRestriction) === false) {
            header('location: index.php');
        }
    } else {
        echo tra("\$tikiMonitorRestriction need to be an array");
        exit;
    }
}
$opcode_stats = TikiLib::lib('admin')->getOpcodeCacheStatus();

# TODO: The results will be wrong for WinCache
# The following is the relevant snippet from
# admin/include_performance.php
$txtUsed = tr('Used');
$txtAvailable = tr('Available');
if ($opcode_cache == 'WinCache') {
    // Somehow WinCache seems to flip the representations
    $txtAvailable = tr('Used');
    $txtUsed = tr('Available');
}

$result = [];
$result['OPCodeCache'] = $opcode_stats['opcode_cache'];
$result['OpCodeStats'] = $opcode_stats;

include_once('installer/installlib.php');
$installer = Installer::getInstance();
$result['DbRequiresUpdate'] = $installer->requiresUpdate();

$result['SearchIndexRebuildLast'] = $tikilib->get_preference('unified_last_rebuild');

$display = json_encode($result);
echo $display;

/**
 * Check monitor is restricted by IP
 * Security: Uses get_ip_address() which properly validates reverse proxy headers
 *
 * @return null
 */
function isMonitorRestrited()
{
    global $prefs;
    $tikilib = TikiLib::lib('tiki');

    $tikiMonitorRestriction = ! empty($prefs['monitor_restricted_ips']) ? explode(',', preg_replace('/\s+/', '', $prefs['monitor_restricted_ips'])) : [];
    $sIpToCheck = null;
    if (! empty($tikiMonitorRestriction)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && ! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $aListIp = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $sIpToCheck = $aListIp[0];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && ! empty($_SERVER['REMOTE_ADDR'])) {
            $sIpToCheck = $_SERVER['REMOTE_ADDR'];
        }
    }

    if (in_array($sIpToCheck, $tikiMonitorRestriction) === false) {
        header('location: index.php');
        exit();
    }
}

/**
 * Get monitor role based on token authentication
 *
 * @return string;
 */
function getMonitorRole()
{
    global $prefs, $tiki_p_admin;

    $role = 'public';
    if ($tiki_p_admin === 'y') {
        $role = 'auth';
    }

    if (! empty($prefs['monitor_token'])) {
        $requestMonitorToken = getRequestParam('monitoring_token', 'X-Tiki-Monitoring-Token');
        if ($prefs['monitor_token'] !== $requestMonitorToken) {
            header("HTTP/1.1 401 Unauthorized");
            exit;
        }
        $role = 'auth';
    }

    return $role;
}

/**
 * Get default monitor authentication
 *
 * @return array
 */
function getDefaultMonitorRules()
{
    return [
        'OPCodeCache:public',
        'OpCodeStats:public',
        'DbRequiresUpdate:public',
        'SearchIndexRebuildLast:public',
        '*:auth'
    ];
}

/**
 * Get parameters from request or from header
 *
 * @param string $param
 * @param string $headerParam
 * @return string
 */
function getRequestParam($param, $headerParam)
{
    $requestParam = ! empty($_REQUEST[$param]) ? $_REQUEST[$param] : '';
    $allHeaders = getallheaders();
    if (! empty($allHeaders[$headerParam])) {
        $requestParam = $allHeaders[$headerParam];
    }

    return $requestParam;
}

/**
 * Check monitor permission is valid
 *
 * @param string $monitor
 * @return bool
 */
function isValidMonitor($monitor)
{
    global $prefs;

    $defaultMonitorRules = getDefaultMonitorRules();
    $monitorRules = ! empty($prefs['monitor_rules']) ? explode(PHP_EOL, $prefs['monitor_rules']) : $defaultMonitorRules;
    foreach ($monitorRules as $authRule) {
        $rule = ! empty($authRule) ? explode(':', $authRule) : null;
        if (! empty($rule[0]) && ! empty($rule[1])) {
            if ($rule[0] === '*') {
                $rule[0] = $monitor;
            }
            if (str_contains($monitor, '.')) {
                $subMonitor = explode('.', $monitor);
                if (isValidRule($subMonitor, $rule[1], $rule[0])) {
                    return true;
                }
            }
            if (str_contains($rule[0], '.')) {
                $subRule = explode('.', $rule[0]);
                if (isValidRule($subRule, $rule[1], $monitor)) {
                    return true;
                }
            }
            if (isValidRule($rule[0], $rule[1], $monitor)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Check monitor rule
 *
 * @param string|array $rule
 * @param string $ruleRole
 * @param string $monitor
 * @return bool
 */
function isValidRule($rule, $ruleRole, $monitor)
{
    $role = getMonitorRole();

    if (empty($rule) || empty($ruleRole) || empty($monitor)) {
        return false;
    }

    if (
        (is_array($rule) && in_array($monitor, $rule)
        || (is_string($rule) && $rule === $monitor))
        && ($ruleRole === 'public' || $ruleRole === 'auth' && $role === 'auth')
    ) {
        return true;
    }

    return false;
}

/**
 * Get probes calculations
 *
 * @param array $result
 * @return array
 */
function getProbes($result)
{
    global $prefs;

    $probes = [];
    $probesList = ! empty($prefs['monitor_probes']) ? explode(PHP_EOL, $prefs['monitor_probes']) : [];
    if (! empty($probesList)) {
        $probes['result'] = "OK";

        $runner = new Math_Formula_Runner(
            [
                'Math_Formula_Function_' => '',
                'Tiki_Formula_Function_' => '',
            ]
        );

        $probesDetails = [];
        foreach ($probesList as $line => $probe) {
            $probeDetailLine = 'probe_' . ($line + 1);
            $probesDetails[$probeDetailLine] = "OK";

            try {
                preg_match('/\(\S+\s+(\S+)\s+(\S+)\)/', $probe, $matches);
                $probeMonitor = ! empty($matches[1]) ? $matches[1] : '';
                $probeMonitorValue = ! empty($matches[2]) ? $matches[2] : '';
                $monitorValue = isset($result[$probeMonitor]) ? $result[$probeMonitor] : '';

                if (str_contains($probeMonitor, '.')) {
                    $subMonitor = explode('.', $probeMonitor);
                    $monitorValue = isset($result[$subMonitor[0]][$subMonitor[1]])
                        ? $result[$subMonitor[0]][$subMonitor[1]] : '';
                }
                if (empty($monitorValue)) {
                    $probesDetails[$probeDetailLine] = "FAIL";
                    $probes['result'] = "FAIL";
                    continue;
                }

                // When value of monitor is a timestamp, probe value should be converted to timestamp
                if (
                    ! empty($probeMonitorValue) && is_numeric($probeMonitorValue)
                    && ((string) (int) $monitorValue === $monitorValue)
                    && ($monitorValue <= PHP_INT_MAX)
                    && ($monitorValue >= ~PHP_INT_MAX)
                ) {
                    $dateTime = new DateTime();
                    $dateTime->setTimestamp($monitorValue);
                    $dateTime->modify("+" . $probeMonitorValue . " minutes");
                    $newTimestamp = $dateTime->getTimestamp();
                    $probe = str_replace($probeMonitorValue, $newTimestamp, $probe);
                }
                if ($probeMonitorValue === 'NOW') {
                    $probe = str_replace($probeMonitorValue, time(), $probe);
                }
                $runner->setFormula($probe);
                $runner->setVariables([$probeMonitor => $monitorValue]);
                if (! $runner->evaluate()) {
                    $probesDetails[$probeDetailLine] = "FAIL";
                    $probes['result'] = "FAIL";
                }
            } catch (Math_Formula_Exception $e) {
                $probes['result'] = "FAIL";
                $probesDetails[$probeDetailLine] = "FAIL";
            }
        }

        $probes['details'] = $probesDetails;
    }

    return $probes;
}
