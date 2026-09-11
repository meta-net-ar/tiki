<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Logs;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * Configurable application logger backed by Monolog.
 *
 * Logging is disabled by default through the `log_tiki_enabled` preference.
 * When enabled, records are sent either to PHP's configured error-log
 * destination or to `<TIKI_PATH>/logs/<filename>`, according to
 * `log_tiki_destination` and `log_tiki_file`.
 *
 * The logger uses an INFO threshold. DEBUG records are therefore ignored by
 * the configured handlers, while INFO and higher-severity records are kept.
 * The context array is structured diagnostic data; do not put passwords,
 * tokens, complete email contents, or large request payloads in it.
 *
 * Example:
 *
 * ```php
 * use Tiki\Lib\Logs\Tiki_Monolog;
 *
 * $logger = new Tiki_Monolog();
 * $logger->error('Unable to process the request', [
 *     'component' => __CLASS__,
 *     'exception' => $exception->getMessage(),
 * ]);
 * ```
 */
class Tiki_Monolog
{
    private Logger $logger;

    public function __construct()
    {
        global $prefs;

        $this->logger = new Logger('Tiki');

        if (($prefs['log_tiki_enabled'] ?? 'n') !== 'y') {
            $this->logger->pushHandler(new NullHandler());
            return;
        }

        if (($prefs['log_tiki_destination'] ?? 'php_error_log') === 'file') {
            $this->logger->pushHandler(new StreamHandler($this->getFilePath($prefs['log_tiki_file'] ?? 'tiki.log'), Level::Info));
            return;
        }

        $this->logger->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, Level::Info));
    }

    /**
     * Records a critical failure of an important component.
     *
     * @param string $message Stable description of the event.
     * @param array $context Diagnostic fields for the event.
     */
    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Records an operation failure.
     *
     * @param string $message Stable description of the event.
     * @param array $context Diagnostic fields for the event.
     */
    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * Records an unexpected but recoverable condition.
     *
     * @param string $message Stable description of the event.
     * @param array $context Diagnostic fields for the event.
     */
    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Records a normal operational event.
     *
     * @param string $message Stable description of the event.
     * @param array $context Diagnostic fields for the event.
     */
    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Records detailed diagnostic information.
     *
     * DEBUG records are below the default INFO handler threshold and are not
     * written unless the handler configuration is changed.
     *
     * @param string $message Stable description of the event.
     * @param array $context Diagnostic fields for the event.
     */
    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    private function getFilePath(string $fileName): string
    {
        $fileName = basename($fileName);
        $directory = TIKI_PATH . DIRECTORY_SEPARATOR . 'logs';

        if (! is_dir($directory) && ! mkdir($directory, 0644, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Unable to create the Tiki log directory: ' . $directory);
        }

        return $directory . DIRECTORY_SEPARATOR . ($fileName ?: 'tiki.log');
    }
}