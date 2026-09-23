<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\Template;
use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty block toast
 *
 * Floating notification pill (utils/toast_model.png).
 *
 * Usage:
 *  {toast type="info" title="General information" description="Something you might like to know."
 *      action=true callback="acceptToast" action_label="Accept"
 *      secondary_label="Learn more" secondary_href="https://doc.tiki.org"
 *      close=true duration=12}
 *  {/toast}
 *
 * Params:
 *  - type              enum: simple|success|error|danger|warning|info  (default: info)
 *  - title             string
 *  - description       string (falls back to block content when empty)
 *  - action            boolean — show action button(s)
 *  - callback          string JS function name — only when action is true
 *  - action_label      primary button label (default: Accept)
 *  - secondary_label   optional second button label (e.g. Learn more)
 *  - secondary_callback optional JS function name for the secondary button
 *  - secondary_href    optional URL for the secondary button (renders as <a>)
 *  - close             boolean — show dismiss control (default: true)
 *  - duration          int seconds — auto-close countdown; 0 = disabled (default: 0)
 *  - class             optional extra CSS classes
 */
class Toast implements BlockHandlerInterface, TikiSmartyExtensionInterface
{
    /** @var list<string> */
    private const TYPES = ['simple', 'success', 'error', 'danger', 'warning', 'info'];

    public static function getSmartyName(): string
    {
        return 'toast';
    }

    public function handle($params, $content, Template $template, &$repeat)
    {
        if ($repeat) {
            return '';
        }

        $smarty = \TikiLib::lib('smarty');

        static $toastInstance = 0;
        $toastInstance++;

        $params = array_merge([
            'type' => 'info',
            'title' => '',
            'description' => '',
            'action' => false,
            'callback' => '',
            'action_label' => '',
            'secondary_label' => '',
            'secondary_callback' => '',
            'secondary_href' => '',
            'close' => true,
            'duration' => 0,
            'class' => '',
            'id' => 'tki_toast_' . $toastInstance,
        ], $params);

        $type = $this->normalizeType($params['type']);
        $title = trim((string) $params['title']);
        $description = $this->resolveDescription($params['description'], $content);
        $action = $this->toBool($params['action']);
        $close = $this->toBool($params['close']);
        $duration = max(0, (int) $params['duration']);
        $extraClass = trim((string) $params['class']);
        $id = trim((string) $params['id']);

        $callback = '';
        $actionLabel = '';
        $secondaryLabel = '';
        $secondaryCallback = '';
        $secondaryHref = '';

        if ($action) {
            $callback = $this->sanitizeJsName((string) $params['callback']);
            $actionLabel = trim((string) $params['action_label']);
            if ($actionLabel === '') {
                $actionLabel = tra('Accept');
            }

            $secondaryLabel = trim((string) $params['secondary_label']);
            $secondaryCallback = $this->sanitizeJsName((string) $params['secondary_callback']);
            $secondaryHref = trim((string) $params['secondary_href']);
        }

        $typeModifier = $type === 'danger' ? 'error' : $type;
        $toastClass = trim('tki-toast tki-toast--' . $typeModifier . ' ' . $extraClass);
        $icon = $this->iconForType($type);

        $smarty->assign('toast_id', $id);
        $smarty->assign('toast_type', $type);
        $smarty->assign('toast_title', $title);
        $smarty->assign('toast_description', $description);
        $smarty->assign('toast_action', $action);
        $smarty->assign('toast_callback', $callback);
        $smarty->assign('toast_action_label', $actionLabel);
        $smarty->assign('toast_secondary_label', $secondaryLabel);
        $smarty->assign('toast_secondary_callback', $secondaryCallback);
        $smarty->assign('toast_secondary_href', $secondaryHref);
        $smarty->assign('toast_close', $close);
        $smarty->assign('toast_duration', $duration);
        $smarty->assign('toast_class', $toastClass);
        $smarty->assign('toast_icon', $icon);

        return $smarty->fetch('toast.tpl');
    }

    public function isCacheable(): bool
    {
        return true;
    }

    private function normalizeType(mixed $type): string
    {
        $type = strtolower(trim((string) $type));

        $aliases = [
            'errors' => 'error',
            'confirm' => 'success',
            'feedback' => 'success',
            'note' => 'info',
            'tip' => 'info',
        ];

        if (isset($aliases[$type])) {
            $type = $aliases[$type];
        }

        if (! in_array($type, self::TYPES, true)) {
            return 'info';
        }

        return $type;
    }

    private function resolveDescription(mixed $description, mixed $content): string
    {
        $description = trim((string) $description);
        if ($description !== '') {
            return $description;
        }

        return trim((string) $content);
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'y', 'yes', 'true', 'on'], true);
    }

    /**
     * Allow only a safe JS identifier / dotted path (e.g. acceptToast, window.foo.bar).
     */
    private function sanitizeJsName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || ! preg_match('/^[A-Za-z_$][\w$.]*$/', $name)) {
            return '';
        }

        return $name;
    }

    private function iconForType(string $type): string
    {
        return match ($type) {
            'success' => 'success',
            'error', 'danger' => 'error',
            'warning' => 'warning',
            'simple' => 'information',
            default => 'information',
        };
    }
}
