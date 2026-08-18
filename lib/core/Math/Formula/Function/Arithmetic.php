<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Math\Formula\Function;

/**
 * Whitelist-backed PHP math builtins for graph formulas.
 *
 * Supports explicit form (arithmetic sin x) and resolves bare names (sin x) via resolver().
 * add, mul, pow, etc. remain standard Math_Formula_Function_* classes.
 */
class Arithmetic extends \Math_Formula_Function
{
    /** @var string|null lowercase PHP function when resolved as (sin x) */
    private $phpName;

    public function __construct(?string $phpName = null)
    {
        $this->phpName = $phpName !== null ? strtolower($phpName) : null;
    }

    /** @return string[] */
    public static function allowedNames(): array
    {
        return [
            'acos',
            'acosh',
            'asin',
            'asinh',
            'atan',
            'atan2',
            'atanh',
            'cos',
            'cosh',
            'deg2rad',
            'exp',
            'expm1',
            'fmod',
            'hypot',
            'log',
            'log10',
            'log1p',
            'pi',
            'rad2deg',
            'sin',
            'sinh',
            'tan',
            'tanh',
        ];
    }

    /**
     * Runner factory: map whitelisted operation names to this class.
     */
    public static function resolver(): callable
    {
        static $allowed;
        if ($allowed === null) {
            $allowed = array_fill_keys(self::allowedNames(), true);
        }

        return function ($functionName) use (&$allowed) {
            $name = strtolower((string) $functionName);
            if ($name === 'arithmetic') {
                return new self();
            }
            if (isset($allowed[$name])) {
                return new self($name);
            }

            return null;
        };
    }

    public function evaluate($element)
    {
        if ($this->phpName !== null) {
            $args = [];
            foreach ($element as $child) {
                $args[] = $this->evaluateChild($child);
            }

            return self::dispatch($this->phpName, $args);
        }

        $first = $element[0];
        if (is_string($first)) {
            $name = strtolower($first);
        } else {
            $name = $this->evaluateChild($first);
            if (! is_string($name)) {
                $this->error(tr('Arithmetic function name must be a string, got "%0"', $name));
            }
            $name = strtolower($name);
        }

        $args = [];
        $i = 0;
        foreach ($element as $child) {
            if ($i++ === 0) {
                continue;
            }
            $args[] = $this->evaluateChild($child);
        }

        return self::dispatch($name, $args);
    }

    /**
     * @param float[] $a
     */
    private static function dispatch(string $name, array $a): float
    {
        $c = count($a);

        switch ($name) {
            case 'pi':
                if ($c !== 0) {
                    self::badArity('pi', 0, $c);
                }
                return M_PI;

            case 'acos':
                self::needArity($name, 1, $c);
                $v = (float) $a[0];
                if ($v < -1 || $v > 1) {
                    throw new \Math_Formula_Exception(tr('acos domain: value outside [-1, 1]'));
                }
                return acos($v);

            case 'asin':
                self::needArity($name, 1, $c);
                $v = (float) $a[0];
                if ($v < -1 || $v > 1) {
                    throw new \Math_Formula_Exception(tr('asin domain: value outside [-1, 1]'));
                }
                return asin($v);

            case 'atan':
                self::needArity($name, 1, $c);
                return atan((float) $a[0]);

            case 'atan2':
                self::needArity($name, 2, $c);
                return atan2((float) $a[0], (float) $a[1]);

            case 'acosh':
                self::needArity($name, 1, $c);
                $v = (float) $a[0];
                if ($v < 1) {
                    throw new \Math_Formula_Exception(tr('acosh domain: value must be >= 1'));
                }
                return acosh($v);

            case 'asinh':
                self::needArity($name, 1, $c);
                return asinh((float) $a[0]);

            case 'atanh':
                self::needArity($name, 1, $c);
                $v = (float) $a[0];
                if ($v <= -1 || $v >= 1) {
                    throw new \Math_Formula_Exception(tr('atanh domain: value must be in (-1, 1)'));
                }
                return atanh($v);

            case 'cos':
                self::needArity($name, 1, $c);
                return cos((float) $a[0]);

            case 'cosh':
                self::needArity($name, 1, $c);
                return cosh((float) $a[0]);

            case 'deg2rad':
                self::needArity($name, 1, $c);
                return deg2rad((float) $a[0]);

            case 'exp':
                self::needArity($name, 1, $c);
                return exp((float) $a[0]);

            case 'expm1':
                self::needArity($name, 1, $c);
                return expm1((float) $a[0]);

            case 'fmod':
                self::needArity($name, 2, $c);
                return fmod((float) $a[0], (float) $a[1]);

            case 'hypot':
                self::needArity($name, 2, $c);
                return hypot((float) $a[0], (float) $a[1]);

            case 'log':
                if ($c === 1) {
                    $xf = (float) $a[0];
                    if ($xf <= 0) {
                        throw new \Math_Formula_Exception(tr('log of non-positive value'));
                    }
                    return log($xf);
                }
                if ($c === 2) {
                    $xf = (float) $a[0];
                    $base = (float) $a[1];
                    if ($xf <= 0) {
                        throw new \Math_Formula_Exception(tr('log of non-positive value'));
                    }
                    if ($base <= 0 || $base == 1.0) {
                        throw new \Math_Formula_Exception(tr('Invalid logarithm base'));
                    }
                    return log($xf, $base);
                }
                self::badArity('log', '1 or 2', $c);
                // no break

            case 'log10':
                self::needArity($name, 1, $c);
                $v = (float) $a[0];
                if ($v <= 0) {
                    throw new \Math_Formula_Exception(tr('log10 of non-positive value'));
                }
                return log10($v);

            case 'log1p':
                self::needArity($name, 1, $c);
                $v = (float) $a[0];
                if ($v <= -1) {
                    throw new \Math_Formula_Exception(tr('log1p domain: value must be > -1'));
                }
                return log1p($v);

            case 'rad2deg':
                self::needArity($name, 1, $c);
                return rad2deg((float) $a[0]);

            case 'sin':
                self::needArity($name, 1, $c);
                return sin((float) $a[0]);

            case 'sinh':
                self::needArity($name, 1, $c);
                return sinh((float) $a[0]);

            case 'tan':
                self::needArity($name, 1, $c);
                return tan((float) $a[0]);

            case 'tanh':
                self::needArity($name, 1, $c);
                return tanh((float) $a[0]);

            default:
                throw new \Math_Formula_Exception(tr('Unknown arithmetic function "%0"', $name));
        }
    }

    /**
     * @param int|string $expected
     */
    private static function needArity(string $name, $expected, int $c): void
    {
        if (is_int($expected) && $c !== $expected) {
            self::badArity($name, (string) $expected, $c);
        }
    }

    /**
     * @param int|string $expected
     */
    private static function badArity(string $name, $expected, int $c): void
    {
        throw new \Math_Formula_Exception(
            tr('Function %0 expects %1 arguments (%2 given)', $name, $expected, $c)
        );
    }
}
