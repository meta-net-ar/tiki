<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
declare(strict_types=1);

namespace Tiki;

if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

/**
 * Class Toast
 *
 * Session-backed notification queue for the floating {toast} UI component.
 * API mirrors Feedback (Toast::error / success / warning / info) so callers
 * can switch display without changing the call pattern.
 *
 * Usage:
 *  Toast::error(['title' => tr('Error'), 'mes' => tr('Something went wrong.')]);
 *  Toast::success('Saved.');
 *
 * Drain once before render:
 *  $smarty->assign('installer_toasts', Toast::get());
 */
class Toast
{
    private const SESSION_KEY = 'tikitoast';

    /**
     * @param mixed $toast string message or ['title' => …, 'mes'|'description' => …]
     */
    public static function error($toast): void
    {
        $toast = self::normalize($toast);
        if ($toast === false) {
            return;
        }
        $toast['type'] = 'error';
        $toast['title'] = $toast['title'] !== '' ? $toast['title'] : tr('Error');
        self::add($toast);
    }

    /**
     * @param mixed $toast
     */
    public static function success($toast): void
    {
        $toast = self::normalize($toast);
        if ($toast === false) {
            return;
        }
        $toast['type'] = 'success';
        $toast['title'] = $toast['title'] !== '' ? $toast['title'] : tr('Success');
        self::add($toast);
    }

    /**
     * @param mixed $toast
     */
    public static function warning($toast): void
    {
        $toast = self::normalize($toast);
        if ($toast === false) {
            return;
        }
        $toast['type'] = 'warning';
        $toast['title'] = $toast['title'] !== '' ? $toast['title'] : tr('Warning');
        self::add($toast);
    }

    /**
     * @param mixed $toast
     */
    public static function info($toast): void
    {
        $toast = self::normalize($toast);
        if ($toast === false) {
            return;
        }
        $toast['type'] = 'info';
        $toast['title'] = $toast['title'] !== '' ? $toast['title'] : tr('Note');
        self::add($toast);
    }

    /**
     * Alias for info (parity with Feedback::note).
     *
     * @param mixed $toast
     */
    public static function note($toast): void
    {
        self::info($toast);
    }

    /**
     * @param array $toast Must include type + title + description (after normalize)
     */
    public static function add(array $toast): void
    {
        if (! isset($_SESSION[self::SESSION_KEY]) || ! is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        if (! in_array($toast, $_SESSION[self::SESSION_KEY], true)) {
            $_SESSION[self::SESSION_KEY][] = $toast;
        }
    }

    public static function clear(): void
    {
        $_SESSION[self::SESSION_KEY] = [];
    }

    /**
     * Drain the queue (one-shot) as payloads ready for {toast}.
     *
     * @return list<array{type:string,title:string,description:string}>
     */
    public static function get(): array
    {
        $items = $_SESSION[self::SESSION_KEY] ?? [];
        unset($_SESSION[self::SESSION_KEY]);

        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $out[] = [
                'type' => (string) ($item['type'] ?? 'info'),
                'title' => (string) ($item['title'] ?? ''),
                'description' => (string) ($item['description'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param mixed $toast
     * @return array{title:string,description:string}|false
     */
    private static function normalize($toast)
    {
        if (empty($toast) && $toast !== '0' && $toast !== 0) {
            trigger_error(tr('Toast class called with no message provided.'), E_USER_NOTICE);
            return false;
        }

        if (! is_array($toast)) {
            return [
                'title' => '',
                'description' => (string) $toast,
            ];
        }

        $title = trim((string) ($toast['title'] ?? ''));

        // Accept Feedback-style "mes" or toast-style "description"
        $body = $toast['description'] ?? $toast['mes'] ?? '';
        if (is_array($body)) {
            $body = implode("\n", array_map('strval', $body));
        }
        $body = trim((string) $body);

        if ($body === '' && $title === '') {
            trigger_error(tr('Toast class called with no message provided.'), E_USER_NOTICE);
            return false;
        }

        return [
            'title' => $title,
            'description' => $body,
        ];
    }
}
