<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Monolog\Logger;

/**
 * Shared PSR-3-style logger backed by Monolog's IDS file handler.
 *
 * Obtain the shared service instead of constructing this class directly:
 *
 * ```php
 * TikiLib::lib('idslog')->error('The operation failed', [
 *     'component' => __CLASS__,
 *     'method' => __FUNCTION__,
 * ]);
 * ```
 *
 * The level is selected by the meaning of the event, not by the presence of
 * words such as "error" or "warning" in the message:
 *
 * ```php
 * $logger = TikiLib::lib('idslog');
 *
 * // An expected successful operation.
 * $logger->info('Mail queue cleanup completed', [
 *     'component' => __CLASS__,
 *     'removed_count' => $removedCount,
 * ]);
 *
 * // An unexpected condition from which execution can continue.
 * $logger->warning('Using the fallback mail transport', [
 *     'component' => __CLASS__,
 *     'transport' => $transportName,
 * ]);
 *
 * // An operation failed.
 * $logger->error('Unable to remove a mail queue entry', [
 *     'component' => __CLASS__,
 *     'message_id' => $messageId,
 *     'exception' => $exception->getMessage(),
 * ]);
 * ```
 *
 * Context keys can be any meaningful field names, and values can be strings,
 * integers, booleans, or arrays. Keep sensitive data, passwords, tokens,
 * complete email contents, and large payloads out of the context. Put the
 * stable summary in the message and variable diagnostic values in context.
 * For an exception, prefer reporting the exception to the error-tracking
 * service and use the logger only for a local operational record.
 *
 * `TikiLib::lib('idslog')` resolves the `tiki.lib.idslog` service from the
 * Tiki service container. The service creates a Monolog `Logger` named `IDS`
 * with a `StreamHandler`. The message is passed to Monolog with its severity,
 * formatted with the context, and written to the configured
 * `ids_log_to_file` path, or to `ids.log` when no path is configured.
 *
 * The handler threshold is `INFO`: `debug()` messages are ignored, while
 * `info()` and all higher levels are written. The context array is structured
 * metadata for the log formatter; it does not select the severity. Use
 * `warning()` for recoverable unexpected conditions, `error()` for failed
 * operations, and `critical()` or higher only when a major service is unable
 * to operate.
 *
 * This logger writes to a file. It does not write to Tiki's action-log
 * database, display feedback to users, or report exceptions to Sentry/GlitchTip.
 */
class IDS_log extends \Expose\Log  // @phpstan-ignore class.notFound (depends on enygma/expose, installed through packages)
{
    protected $logger = null;

    protected $resource = null;

    /**
     * Initialize the IDS logger.
     *
     * When no path is provided, use the configured IDS log path. This allows
     * the logger to be exposed as a shared Tiki service.
     *
     * @param string|null $filePath The file to log
     */
    public function __construct($filePath = null)
    {
        global $prefs;

        if (empty($filePath)) {
            $filePath = $prefs['ids_log_to_file'] ?? 'ids.log';
        }

        $logger = new Logger('IDS');
        $logger->pushHandler(new Monolog\Handler\StreamHandler($filePath, Logger::INFO));
        $this->setLogger($logger);
    }

    /**
     * Set the logger object instance
     *
     * @param object $logger Logger instance
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get the current logger instance
     *
     * @return object Logger instance
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Log emergency messages
     *
     * @param string $message Log message
     * @param array $context Extra contact information
      * @return void
     */
    public function emergency($message, array $context = [])
    {
        return $this->log('emergency', $message, $context);
    }

    /**
     * Log alert messages
     *
     * @param string $message Log message
     * @param array $context Extra contact information
      * @return void
     */
    public function alert($message, array $context = [])
    {
        return $this->log('alert', $message, $context);
    }

    /**
     * Log critical messages
     *
     * @param string $message Log message
     * @param array $context Extra contact information
      * @return void
     */
    public function critical($message, array $context = [])
    {
        return $this->log('critical', $message, $context);
    }

    /**
     * Log error messages
     *
     * @param string $message Log message
     * @param array $context Extra contact information
      * @return void
     */
    public function error($message, array $context = [])
    {
        return $this->log('error', $message, $context);
    }

    /**
     * Log warning messages
     *
     * @param string $message Log message
     * @param array $context Extra contact information
      * @return void
     */
    public function warning($message, array $context = [])
    {
        return $this->log('warning', $message, $context);
    }

    /**
     * Log notice messages
     *
     * @param string $message Log message
     * @param array $context Extra contact information
      * @return void
     */
    public function notice($message, array $context = [])
    {
        return $this->log('notice', $message, $context);
    }

    /**
     * Log info messages
     *
     * @param string $message Log message
     * @param array $context Extra contact information
        * @return void
     */
    public function info($message, array $context = [])
    {
        return $this->log('info', $message, $context);
    }

    /**
     * Log debug messages
     *
     * @param string $message Log message
     * @param array $context Extra contact information
    * @return void
     */
    public function debug($message, array $context = [])
    {
        return $this->log('debug', $message, $context);
    }

    /**
        * Push the log message and context information into the IDS log file.
     *
     * @param string $level Logging level (ex. info, debug, notice...)
     * @param string $message Log message
     * @param array $context Extra context information
      * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $logger = $this->getLogger();
        return $logger->$level($message, $context);
    }
}
