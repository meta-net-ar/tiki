<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Sentry\Event;
use Sentry\EventHint;
use Tiki\Errors;

/* This file handles reporting PHP errors to a remote service like glitchtip or sentry */

class ErrorTracking
{
    /** Set this to true when developing this tool.  It will ignore preferences and activate the code with a dummy DSN */
    private const LOCAL_DEBUG_MODE = false;

    private const STATE_DISABLED = 0;
    private const STATE_HOLD = 1;
    private const STATE_PUSH = 2;

    protected const REDACTED_PARAMS = ['twoFactorAuthCode', 'pass', 'passAgain', 'ticket', 'TOKEN'];
    protected const REDACTED_SESSION = ['', 'CV', '_CSRF'];

    protected int $state = self::STATE_DISABLED;

    protected bool $phpEnabled;
    protected bool $jsEnabled;
    protected bool $tracingEnabledPhp;
    protected bool $tracingEnabledJs;

    protected string $dsn;
    private bool $dsnIsInvalid = false;
    private bool $isInitialised = false;
    protected float $sampleRate;
    protected float $tracesSampleRate;

    protected array $stack = [];

    private ?closure $previousErrorHandler = null;
    private $currentTransaction = null;

    /**
     * Check if external error reporting for JavaScript is enabled.
     *
     * @return bool
     */
    public function isJSEnabled(): bool
    {
        return isset($this->dsn) && $this->jsEnabled;
    }

    /**
     * Check if performance tracing for PHP is enabled.
     *
     * @return bool
     */
    public function isTracingEnabledPhp(): bool
    {
        return isset($this->dsn) && $this->phpEnabled && $this->tracingEnabledPhp;
    }

    /**
     * Check if performance tracing for JavaScript is enabled.
     *
     * @return bool
     */
    public function isTracingEnabledJs(): bool
    {
        return isset($this->dsn) && $this->jsEnabled && $this->tracingEnabledJs;
    }

    /**
     * Capture thrown exception. Exceptions are always added to the exceptions stack.
     *
     * @param \Throwable $exception The exception to capture
     * @param array $tags Optional associative array of tags to attach to this specific event only
     */
    public function captureException(\Throwable $exception, array $tags = [])
    {
        if ($this->state === self::STATE_DISABLED) {
            return;
        }

        if (empty($tags)) {
            // No event-specific tags, capture directly
            \Sentry\captureException($exception);
        } else {
            // Use withScope to set event-specific tags without polluting global scope
            \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($exception, $tags) {
                foreach ($tags as $key => $value) {
                    $scope->setTag($key, (string)$value);
                }
                \Sentry\captureException($exception);
            });
        }
    }

    /**
     * Start a new transaction for performance tracing
     *
     * @param string $name Transaction name (e.g., 'GET /tiki-index.php')
     * @param string $op Operation type (e.g., 'http.server', 'db.query', 'cache.get')
     * @return \Sentry\Tracing\Transaction|null
     */
    public function startTransaction(string $name, string $op = 'http.server')
    {
        if ($this->state === self::STATE_DISABLED) {
            return null;
        }

        $transactionContext = \Sentry\Tracing\TransactionContext::make()
            ->setName($name)
            ->setOp($op);

        $transaction = \Sentry\startTransaction($transactionContext);

        // Set as current span to maintain proper hierarchy
        \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);

        return $transaction;
    }

    /**
     * Start a child span within a transaction
     *
     * @param string $op Operation type (e.g., 'db.query', 'http.client', 'cache.get')
     * @param string|null $description Optional description
     * @param array $data Optional data to attach to the span
     * @return \Sentry\Tracing\Span|null
     */
    public function startSpan(string $op, ?string $description = null, array $data = [])
    {
        if ($this->state === self::STATE_DISABLED) {
            return null;
        }

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        if (! $parent) {
            // No active transaction/span, cannot create child span
            return null;
        }

        $context = \Sentry\Tracing\SpanContext::make()
            ->setOp($op);

        if ($description) {
            $context->setDescription($description);
        }

        if (! empty($data)) {
            $context->setData($data);
        }

        $span = $parent->startChild($context);

        // Set as current span to maintain hierarchy
        \Sentry\SentrySdk::getCurrentHub()->setSpan($span);

        return $span;
    }

    /**
     * Finish a span and restore parent context
     *
     * @param \Sentry\Tracing\Span|null $span The span to finish
     * @return void
     */
    public function finishSpan($span): void
    {
        if (! $span) {
            return;
        }

        // Get parent before finishing
        $parent = $span->getParentSpanId() !== null ? $span : null;

        $span->finish();

        // Restore parent span context if available
        $hub = \Sentry\SentrySdk::getCurrentHub();
        if ($parent && $hub->getSpan() === $span) {
            // Restore to transaction or parent span
            $transaction = $this->currentTransaction;
            if ($transaction) {
                $hub->setSpan($transaction);
            }
        }
    }

    /**
     * Trace a callable with automatic span management (recommended approach)
     *
     * This is a convenience wrapper that automatically creates, manages, and finishes
     * a span around the provided callable, following Sentry best practices.
     *
     * @param callable $callback The function to trace
     * @param string $op Operation type (e.g., 'db.query', 'http.client')
     * @param string|null $description Optional description
     * @param array $data Optional data to attach to the span
     * @return mixed The return value of the callback
     *
     * @example
     * $result = $errorTracking->trace(function() {
     *     return performDatabaseQuery();
     * }, 'db.query', 'SELECT * FROM users');
     */
    public function trace(callable $callback, string $op, ?string $description = null, array $data = [])
    {
        if (! $this->isTracingEnabledPhp()) {
            // Tracing disabled, just execute callback
            return $callback();
        }

        $context = \Sentry\Tracing\SpanContext::make()
            ->setOp($op);

        if ($description) {
            $context->setDescription($description);
        }

        if (! empty($data)) {
            $context->setData($data);
        }

        // Use Sentry's trace function which handles everything automatically
        return \Sentry\trace($callback, $context);
    }

    /**
     * Capture thrown exception. Exceptions are always added to the exceptions stack.
     *
     * @param Event $event
     */
    protected function registerEvent(Event $event)
    {
        $this->stack[] = $event;
    }

    /**
     * ErrorTracking constructor.
     * Initializes internal variables and Sentry itself.
     *
     * @return void
     * @throws Exception
     */
    public function __construct()
    {
        global $prefs;
        $this->phpEnabled = ($prefs['error_tracking_enabled_php'] ?? 'n') === 'y';
        $this->jsEnabled = ($prefs['error_tracking_enabled_js'] ?? 'n') === 'y';
        $this->tracingEnabledPhp = ($prefs['error_tracking_tracing_enabled_php'] ?? 'n') === 'y';
        $this->tracingEnabledJs = ($prefs['error_tracking_tracing_enabled_js'] ?? 'n') === 'y';

        if (! self::LOCAL_DEBUG_MODE) {
            $this->dsn = $prefs['error_tracking_dsn'] ?? false;
        } else {
            //Sentry is picky about DSN format. This will work:
            $this->dsn = 'https://something@dummydsn.com/something';
        }


        $sampleRate = $prefs['error_tracking_sample_rate'] ?? 1;
        $this->sampleRate = is_numeric($sampleRate) ? $sampleRate : 1;

        $tracesSampleRate = $prefs['error_tracking_traces_sample_rate'] ?? 0.1;
        $this->tracesSampleRate = is_numeric($tracesSampleRate) ? $tracesSampleRate : 0.1;
    }

    public function init()
    {
        global $prefs;
        if ($this->isInitialised) {
            throw new Error('Error tracking can only be initialised once, so we can control where that happens');
        }
        if (! self::LOCAL_DEBUG_MODE && (! isset($this->dsn) || ! $this->phpEnabled || $this->state !== self::STATE_DISABLED)) {
            return;
        }
        try {
            Sentry\init([
                'dsn'                     => $this->getDSN(),
                'http_proxy'              => ($prefs['use_proxy'] ?? 'n') === 'y' ? $this->getProxyURL() : null,
                'sample_rate'             => $this->getSampleRate(),
                'error_types'             => Errors::getErrorReportingLevel(),
                'attach_stacktrace'       => true,
                'traces_sampler' => function (\Sentry\Tracing\SamplingContext $context): float {
                    // If tracing is not enabled, don't sample any transactions
                    if (! $this->isTracingEnabledPhp()) {
                        return 0.0;
                    }

                    // Custom sampling logic based on transaction name and context
                    $transactionContext = $context->getTransactionContext();
                    $parentSampled = $context->getParentSampled();

                    // Inherit parent sampling decision if available
                    if ($parentSampled !== null) {
                        return $parentSampled ? 1.0 : 0.0;
                    }

                    $transactionName = $transactionContext->getName();

                    // Don't trace health checks or status endpoints
                    if (preg_match('/\/(health|status|ping)/', $transactionName)) {
                        return 0.0;
                    }

                    // Sample AJAX/API calls at a higher rate (tiki-ajax_services.php)
                    if (strpos($transactionName, 'tiki-ajax_services.php') !== false) {
                        return min($this->getTracesSampleRate() * 2.0, 1.0);
                    }

                    // Sample admin pages at a lower rate (they're usually slower)
                    if (strpos($transactionName, 'tiki-admin') !== false) {
                        return min($this->getTracesSampleRate() * 0.5, 1.0);
                    }

                    // Use the configured default sample rate
                    return $this->getTracesSampleRate();
                },
                'before_send'             => function (Event $event, ?EventHint $hint): ?Event {
                    if (true && self::LOCAL_DEBUG_MODE) {
                        echo '<pre>';
                        print_r("Incoming sentry event:<br/>");
                        //cho $event->getId();
                        echo $event->getLevel() . ': ' . $event->getMessage();
                        print($hint->exception->getMessage());
                        echo '<br/></pre>';
                    }

                    if ($this->state === self::STATE_PUSH) {
                        // only filter entries when pushing, since will not impact rendering time for pages
                        $eventExceptions = $event->getExceptions();
                        foreach ($eventExceptions as &$exception) {
                            $stackTrace = $exception->getStacktrace();
                            if (empty($stackTrace)) {
                                continue;
                            }
                            $frames = $stackTrace->getFrames();
                            if (empty($frames)) {
                                continue;
                            }
                            foreach ($frames as &$frame) {
                                $vars = $frame->getVars();
                                $this->redactEntries($vars);
                                $frame->setVars($vars);
                            }
                            $exception->setStacktrace(new \Sentry\Stacktrace($frames));
                        }
                        $event->setExceptions($eventExceptions);

                        return $event;
                    }

                    if ($this->state === self::STATE_HOLD) {
                        if (empty($event->getUser())) {
                            // Set here because when we run the function from Sentry\configureScope user may not be set
                            global $user;
                            $event->setUser(Sentry\UserDataBag::createFromArray(['username' => $user ?? 'Anonymous']));
                        }
                        $this->registerEvent($event);
                    }

                    return null;
                },
                'before_send_transaction' => function (Event $transaction): ?Event {
                    if (false && self::LOCAL_DEBUG_MODE) {
                        echo '<pre>';
                        print_r("Incoming sentry transaction:<br/>");
                        var_dump($transaction);
                        echo '</pre>';
                    }
                    return $transaction;
                },
            ]);

            $this->setState(self::STATE_HOLD);

            Sentry\configureScope(function (Sentry\State\Scope $scope) {
                // track REQUEST parameters
                $requestCopy = $_REQUEST;
                $this->redactEntries($requestCopy);
                $scope->setExtra('_REQUEST', $requestCopy);
            });
        } catch (Symfony\Component\OptionsResolver\Exception\InvalidOptionsException $e) {
            //Without this catch, if you enter an invalid DSN, you won't be able to access the tiki admin interface.
            $message = 'Tiki:  the options in error_tracking_dsn was refused by Sentry\init() with message: ' . $e->getMessage();
            //We can't echo anything to the HTML output yet, it will get stripped off if you try

            $this->dsnIsInvalid = true;
            //At least this will log to server log
            trigger_error('Tiki:  the options in error_tracking_dsn was refused by Sentry\init() with message: ' . $e->getMessage(), E_USER_WARNING);
        }
        $this->isInitialised = true;
    }

    /**
     * Push exceptions to third party service
     *
     * @return void
     */
    private function pushEvents()
    {
        foreach ($this->stack as $event) {
            \Sentry\captureEvent($event);
        }
    }

    /**
     * Set current state of the Error Tracking.
     *
     * @param int $state
     */
    private function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * Set the client sample_rate
     *
     * @param float $rate
     *
     * @return void
     */
    private function setSampleRate(float $rate): void
    {
        \Sentry\SentrySdk::getCurrentHub()
            ->getClient()
            ->getOptions()
            ->setSampleRate($rate);
    }

    /**
     * Get currently configured project Data Source Name
     *
     * @return string
     */
    public function getDSN(): string
    {
        return $this->dsn;
    }

    /**
     * Get currently configured sample rate
     *
     * @return float
     */
    public function getSampleRate(): float
    {
        return (float) $this->sampleRate;
    }

    /**
     * Get currently configured traces sample rate
     *
     * @return float
     */
    public function getTracesSampleRate(): float
    {
        return (float) $this->tracesSampleRate;
    }

    /**
     * Get the proxy connection url
     *
     * @return string
     */
    private function getProxyURL(): string
    {
        global $prefs;

        $proxy = '';

        if (! empty($prefs['proxy_user']) && ! empty($prefs['proxy_pass'])) {
            $proxy .= $prefs['proxy_user'] . ':' . $prefs['proxy_pass'] . '@';
        }

        $proxy .= $prefs['proxy_host'];

        if (isset($prefs['proxy_port'])) {
            $proxy .= ':' . $prefs['proxy_port'];
        }

        return $proxy;
    }

    /**
     * Start an automatic transaction for the current HTTP request
     *
     * @return void
     */
    public function startHttpTransaction(): void
    {
        if (! $this->isTracingEnabledPhp() || $this->currentTransaction !== null) {
            return;
        }

        // Build transaction name from request
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Remove query string for cleaner transaction names
        $path = parse_url($uri, PHP_URL_PATH) ?? $uri;

        $transactionName = "$method $path";

        $this->currentTransaction = $this->startTransaction($transactionName, 'http.server');

        if ($this->currentTransaction) {
            // Add request context
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($method, $uri) {
                $scope->setTag('http.method', $method);
                $scope->setTag('http.url', $uri);
            });
        }
    }

    /**
     * Finish the current transaction
     *
     * @return void
     */
    public function finishTransaction(): void
    {
        if ($this->currentTransaction) {
            $this->currentTransaction->finish();
            $this->currentTransaction = null;
        }
    }

    public function bindEvents(Tiki_Event_Manager $manager)
    {
        if ($this->state !== self::STATE_DISABLED) {
            $manager->bind(
                'tiki.process.shutdown',
                function () {
                    // Finish any open transaction before shutdown
                    $this->finishTransaction();

                    // Events were already sampled when prepared
                    // Setting to 1 will send all of them
                    $this->setSampleRate(1);
                    $this->setState(self::STATE_PUSH);
                    $this->pushEvents();
                }
            );
        }
    }

    /**
     * A callback for PHP set_error_handler()
     *
     * In practice, this is called directly by initlib::tiki_error_handling
     *
     * Set how Tiki will report Errors
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     *
     * @return bool Skip running any other error handler after this one.
     */
    public function handleError($errno, $errstr, $errfile, $errline): bool
    {
        if ($this->previousErrorHandler) {
            return ($this->previousErrorHandler)($errno, $errstr, $errfile, $errline);
        }
        //If there was no previousErrorHandler, we do not want PHPs default handler to run.
        return true;
    }

    public function setPreviousErrorHandler(Closure $handler)
    {
        $this->previousErrorHandler = $handler;
    }

    /**
     * Replace entries that may have sensitive information with [Redacted]
     *
     * @param $arrayToProcess
     *
     * @return void
     */
    protected function redactEntries(&$arrayToProcess): void
    {
        static $redactedParametersLowercase = null;

        if ($redactedParametersLowercase === null) {
            $redactedSessionEntries = array_map( // prepend session name
                function ($item) {
                    return session_name() . $item;
                },
                self::REDACTED_SESSION
            );
            $redactedParametersLowercase = array_map(
                'strtolower',
                array_merge(self::REDACTED_PARAMS, $redactedSessionEntries)
            );
        }

        array_walk_recursive($arrayToProcess, function (&$item, $key) use ($redactedParametersLowercase) {
            if (in_array(strtolower($key), $redactedParametersLowercase)) {
                $item = '[Redacted]';
            }
        });
    }
}
