<?php

/**
 * Inspired by smarty_internal_errorhandler.php
 */
class SmartyTikiErrorHandler
{
    private $previousErrorHandler = null;
    private $activationStack = [];
    public function activate(): void
    {
        $activation = [];
        $previousErrorHandler = set_error_handler([$this, 'handleError']);
        if (! $previousErrorHandler) {
            throw new Error("This should not be possible, there should be a custom error handler, if only tiki's");
        }
        if (is_array($previousErrorHandler) && $previousErrorHandler[0] === $this) {
            //Something in smarty called display or fetch from display or fetch.
            restore_error_handler();
            $activation['skipped'] = true;
        } else {
            $this->previousErrorHandler = $previousErrorHandler;
            $activation['skipped'] = false;
            $activation['previousErrorHandler'] = $previousErrorHandler;
        }
        array_push($this->activationStack, $activation);
    }

    /**
     * Disable error handler
     */
    public function deactivate(): void
    {
        $activation = array_pop($this->activationStack);
        if (! $activation) {
            throw new Error("This should not be possible, unbalanced number of calls to activate and deactivate" . count($this->activationStack));
        }
        if ($activation['skipped'] === false) {
            restore_error_handler();
            $this->previousErrorHandler = null;
        }
    }

    /**
     * Error Handler to downgrade some errors that are E_NOTICE in PHP7 but
     * E_WARNING in PHP8 down to E_USER_NOTICE
     *
     * The refactoring needed on templates to clear them all was just
     * excessive.
     *
     * @link https://php.net/set_error_handler
     *
     * @param integer $errno Error level
     * @param         $errstr
     * @param         $errfile
     * @param         $errline
     * @param         $errcontext
     *
     * @return bool
     */
    public function handleError($errno, $errstr, $errfile, $errline, $errcontext = [])
    {
        $suppressedAnError = false;
        //We are only interested in downgrading errors that are E_WARNING
        if (0 !== (E_WARNING & $errno)) {
            if (
                preg_match(
                    '/^(Attempt to read property "value" on null|Trying to get property (\'value\' )?of non-object)/',
                    $errstr
                )
            ) {
                $suppressedAnError = true;
            } elseif (
                preg_match(
                    '/^(Undefined index|Undefined array key|Trying to access array offset on value of type)/',
                    $errstr
                )
            ) {
                $suppressedAnError = true;
            } elseif (
                preg_match(
                    '/^Attempt to read property " . + ? " on/',
                    $errstr
                )
            ) {
                $suppressedAnError = true;
            }

            if ($suppressedAnError) {
                $errno = E_USER_NOTICE; //Downgrade these error to E_USER_NOTICE to make it closer to php7 behaviour.  They were E_NOTICE before PHP8, they are now E_WARNING
            }
        }
        // pass all errors through to the previous error handler or to the default PHP error handler
        //Note that the downgrade above will have no effect if there was no error handler, but that should never happen in tiki.
        return call_user_func($this->previousErrorHandler, $errno, $errstr, $errfile, $errline, $errcontext);
    }
}
