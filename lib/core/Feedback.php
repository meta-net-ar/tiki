<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
declare(strict_types=1);

if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

/**
 * Class Feedback
 *
 * Class for adding feedback to the top of the page either through the php SESSION['tikifeedback'] global variable or
 * through a {$tikifeedback} Smarty template variable. The {remarksbox} Smarty function is used so that errors,
 * warnings, notes, success feedback types and related styling are available.
 *
 * Through this class and the use of the smarty function {feedback} in the basic page layout templates (layout_view.tpl),
 * such feedback can be sent, retreived and displayed without any Smarty template coding needed. Custom templates can
 * also be added for additional use cases.
 *
 */
class Feedback
{
    /**
     * Add error feedback
     *
     * This is a specific application of the add function below for errors.
     *
     * @param $feedback
     * @param bool $sendHeaders
     * @throws Exception
     */
    public static function error($feedback, $sendHeaders = false)
    {
        $feedback = self::checkFeedback($feedback);
        $feedback['type'] = 'error';
        $feedback['title'] = empty($feedback['title']) ? tr('Error') : $feedback['title'];
        $feedback['icon'] = empty($feedback['icon']) ? 'error' : $feedback['icon'];
        self::add($feedback, $sendHeaders);
    }

    /**
     * Redirect to a page with error feedback
     *
     * @param $feedback
     *
     * @throws Exception
     */
    public static function errorPage($feedback)
    {
        $feedback = self::checkFeedback($feedback);
        //only one feedback expected for errorPage
        if (is_array($feedback['mes'])) {
            $feedback['mes'] = $feedback['mes'][0];
        }
        $smarty = TikiLib::lib('smarty');
        $smarty->assign('msg', $feedback['mes']);
        if (! empty($feedback['errortype'])) {
            $smarty->assign('errortype', $feedback['errortype']);
        }
        $smarty->display(! empty($feedback['tpl']) ? $feedback['tpl'] : 'error.tpl');
        die;
    }

    /**
     * Redirect to a page with error feedback and Die
     *
     * @param string $message
     * @param int $httpCode
     * @param string|null $errorPage
     * @return never
     * @throws Exception
     */

    public static function errorAndDie(string $message, int $httpCode, ?string $errorPage = null): never
    {
        $errorPage = $errorPage ?? "error.tpl";
        $smarty = TikiLib::lib('smarty');
        $smarty->assign('errortype', $httpCode);
        $smarty->assign('msg', $message);
        http_response_code($httpCode);
        $smarty->display($errorPage);
        die;
    }

    /**
     * Add note feedback
     *
     * This is a specific application of the add function below for notes.
     *
     * @param $feedback
     * @param bool $sendHeaders
     * @throws Exception
     */
    public static function note($feedback, $sendHeaders = false)
    {
        $feedback = self::checkFeedback($feedback);
        $feedback['type'] = 'note';
        $feedback['title'] = empty($feedback['title']) ? tr('Note') : $feedback['title'];
        $feedback['icon'] = empty($feedback['icon']) ? 'information' : $feedback['icon'];
        self::add($feedback, $sendHeaders);
    }

    /**
     * Add success feedback
     *
     * This is a specific application of the add function below for success feedback.
     *
     * @param $feedback
     * @param bool $sendHeaders
     * @throws Exception
     */
    public static function success($feedback, $sendHeaders = false)
    {
        $feedback = self::checkFeedback($feedback);
        $feedback['type'] = 'feedback';
        $feedback['title'] = empty($feedback['title']) ? tr('Success') : $feedback['title'];
        $feedback['icon'] = empty($feedback['icon']) ? 'success' : $feedback['icon'];
        self::add($feedback, $sendHeaders);
    }

    /**
     * Add warning feedback
     *
     * This is a specific application of the add function below for warnings.
     *
     * @param $feedback
     * @param bool $sendHeaders
     * @throws Exception
     */
    public static function warning($feedback, $sendHeaders = false)
    {
        $feedback = self::checkFeedback($feedback);
        $feedback['type'] = 'warning';
        $feedback['title'] = empty($feedback['title']) ? tr('Warning') : $feedback['title'];
        $feedback['icon'] = empty($feedback['icon']) ? 'warning' : $feedback['icon'];
        self::add($feedback, $sendHeaders);
    }

    /**
     * Add feedback to a global or smarty variable
     *
     * Adds feedback to either the PHP $_SESSION['tikifeedback'] global variable or to a Smarty {$tikifeedback}
     * variable. Typically one of the custom functions above that use this function and that are specific for errors,
     * warnings, notes and success feedback will be used in the individual php file where the error is generated.
     *
     * @param $feedback
     *          - Must at least contain at least a string message
     *          - Can be an array of messages too, in which case the array key 'mes' should be used
     *          - Other array keys can be used that correspond to remarksbox parameters, such as 'type', 'title',
     *              and 'icon'
     *          - A custom smarty template can be indicated using the 'tpl' array key (otherwise
     *              templates/feedback/default.tpl is used). The specified Smarty template will need to be added to the
     *              templates/feedback directory. E.g., including 'tpl' => 'pref' in the $feedback array would cause
     *              the templates/feedback/pref.tpl to be used
     *          - Other custom array keys can be added for use on custom templates
     * @param bool $sendHeaders
     * @return void or bool
     * @throws Exception
     */
    public static function add($feedback, $sendHeaders = false)
    {
        $feedback = self::checkFeedback($feedback);
        if (isset($_SESSION['tikifeedback'])) {
            if (! in_array($feedback, $_SESSION['tikifeedback'])) {
                $_SESSION['tikifeedback'][] = $feedback;
            }
        } else {
            $_SESSION['tikifeedback'][] = $feedback;
        }
        if ($sendHeaders) {
            self::sendHeaders();
        }
    }

    /**
     * Clear local feedback storage
     */
    public static function clear()
    {
        $_SESSION['tikifeedback'] = [];
    }

    /**
     * Utility to ensure $feedback parameter is in the right format
     *
     * @param $feedback
     * @return array|bool
     */
    private static function checkFeedback($feedback)
    {
        if (empty($feedback)) {
            trigger_error(tr('Feedback class called with no feedback provided.'), E_USER_NOTICE);
            return false;
        } elseif (! is_array($feedback)) {
            $feedback = ['mes' => $feedback];
        } else {
            if (empty($feedback['mes'])) {
                trigger_error(tr('Feedback class called with no feedback provided.'), E_USER_NOTICE);
                return false;
            } elseif (! is_array($feedback['mes'])) {
                $feedback['mes'] = [$feedback['mes']];
            }
        }
        return $feedback;
    }

    /**
     * Gets feedback that has been added to either the global PHP $_SESSION['tikifeedback'] or Smarty {$tikifeedback}
     * variable
     *
     * This function is mainly used and already included in the Smarty {feedback} function included in the basic
     * layout_view templates to retrieve and display any feedback that has been added. Normally there isn't a need for
     * developers to use this function otherwise.
     *
     * @return array|bool
     */
    public static function get()
    {
        $result = false;
        if (isset($_SESSION['tikifeedback'])) {
            //get feedback from session variable
            if (isset($_SESSION['tikifeedback'])) {
                $feedback = $_SESSION['tikifeedback'];
                unset($_SESSION['tikifeedback']);
            } else {
                $feedback = [];
            }
            //add default tpl if not set
            foreach ($feedback as $key => $item) {
                if (is_array($item)) {
                    $feedback[$key] = array_merge([
                        'tpl' => 'default',
                        'type' => 'feedback',
                        'icon' => '',
                        'title' => tr('Note')
                    ], $item);
                }
                if (empty($item['tpl'])) {
                    $feedback[$key]['tpl'] = 'default';
                }
            }
            //make the tpl the first level array key
            $fbbytpl = [];
            foreach ($feedback as $key => $item) {
                $tplkey = $item['tpl'];
                unset($item['tpl']);
                $fbbytpl[$tplkey][] = $item;
            }
            if (! empty($fbbytpl)) {
                $result = $fbbytpl;
            }
        }
        return $result;
    }

    /**
     * Add feedback through ajax
     *
     * @throws Exception
     */
    public static function sendHeaders()
    {
        require_once 'lib/smarty_tiki/function.feedback.php';
        $feedback = rawurlencode(str_replace(["\n", "\r", "\t"], '', smarty_function_feedback(
            [], // Encode since HTTP headers are ASCII-only. Other characters can go through, but header()'s documentation has no word on their treatment. Chealer 2017-06-20
            TikiLib::lib('smarty')->getEmptyInternalTemplate()
        )));
        header('X-Tiki-Feedback: ' . $feedback);
    }

    /**
     * Print any feedback out to the command line
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param bool $cron
     */
    public static function printToConsole($output, $cron = false)
    {

        if ($output->isQuiet()) {
            return;
        }

        $errors = \Feedback::get();
        if (is_array($errors)) {
            foreach ($errors as $type => $message) {
                if (is_array($message)) {
                    if (is_array($message[0]) && ! empty($message[0]['mes'])) {
                        $out = '';
                        foreach ($message as $msg) {
                            $type = $msg['type'];
                            $out .= $type . ': ' . str_replace('<br />', "\n", $msg['mes'][0]) . "\n";
                        }
                        $message = $out;
                    } elseif (! empty($message['mes'])) {
                        $message = $type . ': ' . str_replace('<br />', "\n", $message['mes']);
                    }

                    if ($type === 'success' || $type === 'note') {
                        if (! $output->isVeryVerbose()) {
                            continue;
                        }
                        $type = 'info';
                    } elseif ($type === 'warning') {
                        if (! $output->isVerbose()) {
                            continue;
                        }
                        $type = 'comment';
                    }
                    if (! $cron || $type === 'error') {
                        $output->writeln("<$type>$message</$type>");
                    }
                } else {
                    $output->writeln("<error>$message</error>");
                }
            }
        }
    }

    /**
     * Print any feedback out to a log file
     *
     * @param \Laminas\Log\Logger $log
     * @param bool $clear - remove existing entries from the local storage after sending to log file
     */
    public static function printToLog($log, $clear = false)
    {
        $errors = \Feedback::get();
        if (is_array($errors)) {
            foreach ($errors as $type => $message) {
                if (is_array($message)) {
                    if (is_array($message[0]) && ! empty($message[0]['mes'])) {
                        $out = '';
                        foreach ($message as $msg) {
                            $type = $msg['type'];
                            $out .= $type . ': ' . str_replace('<br />', "\n", $msg['mes'][0]) . "\n";
                        }
                        $message = $out;
                    } elseif (! empty($message['mes'])) {
                        $message = $type . ': ' . str_replace('<br />', "\n", $message['mes']);
                    }

                    switch ($type) {
                        case 'error':
                            $log->err($message);
                            break;
                        case 'warning':
                            $log->warn($message);
                            break;
                        case 'feedback':
                        case 'success':
                            $log->info($message);
                            break;
                        case 'note':
                            $log->notice($message);
                            break;
                    }
                } else {
                    $log->err($message);
                }
            }
        }
    }

    /**
     * Get an array of error or warning messages only.
     */
    public static function errorMessages()
    {
        $messages = [];
        $errors = \Feedback::get();
        if (is_array($errors)) {
            foreach ($errors as $type => $message) {
                if (is_array($message)) {
                    if (is_array($message[0]) && ! empty($message[0]['mes'])) {
                        $out = '';
                        foreach ($message as $msg) {
                            $type = $msg['type'];
                            $out .= str_replace('<br />', "\n", $msg['mes'][0]) . "\n";
                        }
                        $message = $out;
                    } elseif (! empty($message['mes'])) {
                        $message = str_replace('<br />', "\n", $message['mes']);
                    }
                    if ($type == 'error' || $type == 'warning') {
                        $messages[] = $message;
                    }
                } else {
                    $messages[] = $message;
                }
            }
        }
        return $messages;
    }

    /**
     * Remove a specific message from feedback
     * @param callable $comparableFunction $item as param and should return a boolean
     */
    public static function removeIf(callable $comparableFunction)
    {
        if (! isset($_SESSION['tikifeedback'])) {
            return;
        }

        foreach ($_SESSION['tikifeedback'] as $key => $value) {
            if ($comparableFunction($value)) {
                unset($_SESSION['tikifeedback'][$key]);
            }
        }
    }

    /**
     * Show a note about users who have been notified about an event
     * @param string $watch_event
     * @param string $object
     * @param string $extra_event
     *
     * @return void
     */
    public static function showWatchers($watch_event, $object, $extra_event = null)
    {
        global $prefs;
        if ($prefs['feature_user_watches'] === 'y') {
            $watches = TikiLib::lib('tiki')->get_event_watches($watch_event, $object);

            if ($extra_event) {
                $extra_watches = TikiLib::lib('tiki')->get_event_watches($extra_event, $object);
                $extra_watches = array_filter($extra_watches, function ($watch) use ($watches) {
                    $watches = array_column($watches, 'user');
                    return ! in_array($watch['user'], $watches);
                });
                $watches = array_merge($watches, $extra_watches);
            }

            if (count($watches)) {
                $message = "<ul>";
                foreach ($watches as $watch) {
                    $message .= "<li class='fw-bold'><a href='tiki-user_information.php?user=" . $watch['user'] . "'>" . $watch['user'] . "</a></li>";
                }
                $message .= "</ul>";
                self::note([
                    'title' => tr('Notification sent to:'),
                    'mes' => $message,
                    'icon' => 'bell'
                ]);
            }
        }
    }
}
