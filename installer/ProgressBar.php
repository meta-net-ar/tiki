<?php

namespace Tiki\Installer;

use SplObserver;
use SplSubject;

class ProgressBar implements SplObserver
{
    private const EXTRACT_TABLE_REGEX = '/CREATE ((FULLTEXT INDEX|INDEX) \w+ ON|(TABLE)) `?(\w+)`?/';

    private const PHASES = ['schema', 'indexing', 'patches', 'scripts'];

    private string $lastPhase = '';

    /** @var 'install'|'upgrade' */
    private string $mode;

    /**
     * @param 'install'|'upgrade' $mode
     */
    public function __construct(string $mode = 'install')
    {
        $this->mode = $mode === 'upgrade' ? 'upgrade' : 'install';
        $this->generateModal();
    }

    public function update(SplSubject $installer): void
    {
        if (! $installer instanceof Installer) {
            return;
        }

        $phase = $installer->progressPhase;
        if ($phase !== '' && $phase !== $this->lastPhase) {
            $this->transitionPhase($phase);
            $this->lastPhase = $phase;
        }

        if ($phase === 'patches') {
            $total = max(1, $installer->progressPatchTotal);
            $applied = $installer->progressPatchApplied;
            $percent = (int) ($applied / $total * 100);
            self::streamJs('window.TikiInstallerProgress.setPhaseProgress("patches", ' . $percent . ');');
            return;
        }

        if (! in_array($phase, ['schema', 'indexing'], true)) {
            return;
        }

        $stats = $installer->progressPhases[$phase];
        $total = max(1, $stats['total']);
        $percent = (int) ($stats['executed'] / $total * 100);
        self::streamJs('window.TikiInstallerProgress.setPhaseProgress(' . json_encode($phase) . ', ' . $percent . ');');

        $sql = $installer->queries['currentStmt'];
        if (! empty($sql) && preg_match(self::EXTRACT_TABLE_REGEX, $sql, $match)) {
            $tableName = $match[4];
            self::streamJs(
                'window.TikiInstallerProgress.setPhaseDetail('
                . json_encode($phase) . ', '
                . json_encode($tableName)
                . ');'
            );
        }
    }

    public static function streamPatchStart(string $patchName): void
    {
        self::streamJs(
            'window.TikiInstallerProgress.setActivePhase("patches");'
            . 'window.TikiInstallerProgress.setCurrentPatch(' . json_encode($patchName) . ');'
        );
    }

    public static function streamPatchComplete(string $patchName): void
    {
        $installer = Installer::getInstance();
        $js = 'window.TikiInstallerProgress.completePatch(' . json_encode($patchName) . ');';

        if ($installer->progressPatchTotal > 0) {
            $installer->progressPatchApplied++;
            $total = max(1, $installer->progressPatchTotal);
            $percent = (int) ($installer->progressPatchApplied / $total * 100);
            $js .= 'window.TikiInstallerProgress.setPhaseProgress("patches", ' . $percent . ');';
            if ($installer->progressPatchApplied >= $installer->progressPatchTotal) {
                $js .= 'window.TikiInstallerProgress.setPhaseState("patches", "done");';
            }
        }

        self::streamJs($js);
    }

    public static function streamScriptStart(string $scriptName): void
    {
        self::streamJs(
            'window.TikiInstallerProgress.setActivePhase("scripts");'
            . 'window.TikiInstallerProgress.setScriptCurrent(' . json_encode($scriptName) . ');'
        );
    }

    public static function streamScriptComplete(string $scriptName): void
    {
        self::streamJs(
            'window.TikiInstallerProgress.completeScript(' . json_encode($scriptName) . ');'
            . 'window.TikiInstallerProgress.setPhaseProgress("scripts", 100);'
            . 'window.TikiInstallerProgress.setPhaseState("scripts", "done");'
        );
    }

    public static function streamFinalize(): void
    {
        self::streamJs('window.TikiInstallerProgress.finalize();');
    }

    public static function streamEmptyPhase(string $phase): void
    {
        self::streamJs(
            'window.TikiInstallerProgress.markEmptyPhase(' . json_encode($phase) . ');'
        );
    }

    private function transitionPhase(string $newPhase): void
    {
        if (array_search($newPhase, self::PHASES, true) === false) {
            return;
        }

        // setActivePhase marks prior phases as done — do not pre-mark them skipped.
        self::streamJs(
            'window.TikiInstallerProgress.setActivePhase(' . json_encode($newPhase) . ');'
        );
    }

    private static function streamJs(string $js): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        echo '<script class="progress_bar_script">'
            . '(function(){var n=0;function r(){if(window.TikiInstallerProgress){'
            . $js
            . '}else if(++n<200){setTimeout(r,25);}}r();})();'
            . '</script>';
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    public function generateModal(): void
    {
        if (php_sapi_name() !== 'cli') {
            @ini_set('zlib.output_compression', '0');
            @ini_set('output_buffering', '0');
            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', '1');
            }
        }

        if ($this->mode === 'upgrade') {
            $title = tra('Database upgrade');
            $description = tra('Schema changes, search indexes, version patches and post-install scripts run in sequence. Keep this window open.');
        } else {
            $title = tra('Database installation');
            $description = tra('Schema, search indexes, version patches and post-install scripts run in sequence. Keep this window open.');
        }

        $kicker = tra('Tiki installer');
        $phaseSchema = tra('Schema');
        $phaseIndexing = tra('Indexing');
        $phasePatches = tra('Patches');
        $phaseScripts = tra('Scripts');
        $statusWaiting = tra('Waiting');
        $statusActive = tra('In progress');
        $statusDone = tra('Done');
        $statusSkipped = tra('Skipped');
        $noItemsFound = tra('No items found');

        $statusJson = json_encode([
            'pending' => $statusWaiting,
            'active' => $statusActive,
            'done' => $statusDone,
            'skipped' => $statusSkipped,
            'noItems' => $noItemsFound,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        echo $this->renderStyles();
        echo $this->renderMarkup(
            $kicker,
            $title,
            $description,
            $phaseSchema,
            $phaseIndexing,
            $phasePatches,
            $phaseScripts,
            $statusActive,
            $statusWaiting
        );
        echo $this->renderScript($statusJson);
        flush();
    }

    private function renderStyles(): string
    {
        return <<<'HTML'
<style>
@import url("https://fonts.bunny.net/css?family=montserrat:400,500,600,700");
#progressBar.tki-install-progress {
    /* Light — aligned with _tiki-installer.scss @mixin tki-theme-light */
    --tki-prog-backdrop: #f8f9fa;
    --tki-prog-backdrop-blur: 14px;
    --tki-prog-panel: #ffffff;
    --tki-prog-text: #1a1a1a;
    --tki-prog-muted: #757575;
    --tki-prog-border: #e0e0e0;
    --tki-prog-border-strong: #d1d5db;
    --tki-prog-accent: #147aac;
    --tki-prog-accent-rgb: 20, 122, 172;
    --tki-prog-accent-soft: rgba(20, 122, 172, 0.08);
    --tki-prog-accent-glow: rgba(20, 122, 172, 0.16);
    --tki-prog-done: #21b573;
    --tki-prog-fail: #d64545;
    --tki-prog-step-muted: #8e8e8e;
    --tki-prog-marker-fg: #aaaaaa;
    --tki-prog-marker-skipped: #f3f4f6;
    --tki-prog-spinner-border: #d1d5db;
    --tki-prog-scrollbar: rgba(20, 122, 172, 0.65);
    --tki-prog-scrollbar-hover: rgba(20, 122, 172, 0.45);
    --tki-prog-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
    margin: 0;
    padding: 1.5rem;
    position: fixed;
    inset: 0;
    z-index: 10000;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    align-items: safe center;
    justify-content: center;
    overflow: auto;
    font-family: Montserrat, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--tki-prog-text);
    background-color: var(--tki-prog-backdrop);
    backdrop-filter: blur(var(--tki-prog-backdrop-blur)) saturate(1.15);
    -webkit-backdrop-filter: blur(var(--tki-prog-backdrop-blur)) saturate(1.15);
}
#progressBar.tki-install-progress[data-tki-theme="dark"] {
    /* Dark — aligned with _tiki-installer.scss @mixin tki-theme-dark */
    --tki-prog-backdrop: #202020;
    --tki-prog-panel: #262626;
    --tki-prog-text: #ffffff;
    --tki-prog-muted: #b0b0b0;
    --tki-prog-border: #404040;
    --tki-prog-border-strong: #555555;
    --tki-prog-accent: #5898b4;
    --tki-prog-accent-rgb: 88, 152, 180;
    --tki-prog-accent-soft: rgba(88, 152, 180, 0.14);
    --tki-prog-accent-glow: rgba(88, 152, 180, 0.22);
    --tki-prog-done: #6b9e78;
    --tki-prog-fail: #e25c5c;
    --tki-prog-step-muted: #8e8e8e;
    --tki-prog-marker-fg: #8e8e8e;
    --tki-prog-marker-skipped: #2c2c2c;
    --tki-prog-spinner-border: #555555;
    --tki-prog-scrollbar: rgba(88, 152, 180, 0.45);
    --tki-prog-scrollbar-hover: rgba(88, 152, 180, 0.6);
    --tki-prog-shadow: 0 24px 60px rgba(0, 0, 0, 0.4);
}
#progressBar .tki-install-progress__panel {
    width: 100%;
    max-width: 36rem;
    margin: auto;
    flex-shrink: 0;
    padding: 1.75rem 1.75rem 1.35rem;
    box-sizing: border-box;
    background: transparent;
    border: none;
    box-shadow: none;
    animation: tki-prog-panel-in 0.22s ease-out;
}
@keyframes tki-prog-panel-in {
    from { opacity: 0; transform: translateY(8px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
#progressBar .tki-install-progress__brand { margin: 0 0 2.25rem; }
#progressBar .tki-install-progress__kicker {
    margin: 0 0 0.2rem;
    font-size: 0.65625rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--tki-prog-accent);
}
#progressBar .tki-install-progress__title {
    margin: 0;
    font-size: 1.15625rem;
    font-weight: 600;
    line-height: 1.35;
    letter-spacing: -0.005em;
    color: var(--tki-prog-text);
}
#progressBar .tki-install-progress__desc {
    margin: 0.45rem 0 0;
    font-size: 0.84375rem;
    font-weight: 400;
    line-height: 1.5;
    color: var(--tki-prog-muted);
}
#progressBar .tki-checklist {
    --tki-marker: 1.875rem;
    --tki-marker-icon: 1rem;
    --tki-marker-gap: 0.5rem;
    list-style: none;
    margin: 0;
    padding: 0;
    border: none;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    position: relative;
}
#progressBar .tki-checklist::before {
    content: "";
    position: absolute;
    left: calc(var(--tki-marker) / 2);
    top: calc(0.1rem + var(--tki-marker) / 2);
    bottom: calc(0.1rem + var(--tki-marker) / 2);
    width: 1px;
    background: var(--tki-prog-border);
    z-index: 0;
    pointer-events: none;
}
#progressBar .tki-check-item {
    position: relative;
    display: grid;
    grid-template-columns: var(--tki-marker) 1fr;
    column-gap: var(--tki-marker-gap);
    align-items: start;
    padding: 0.1rem 0 0.4rem;
    border-radius: 0.25rem;
}
#progressBar .tki-check-item:last-child { padding-bottom: 0.1rem; }
#progressBar .tki-check-body { min-width: 0; }
#progressBar .tki-check-item.is-done .tki-check-title { color: var(--tki-prog-muted); }
#progressBar .tki-check-item.is-pending .tki-check-title,
#progressBar .tki-check-item.is-skipped .tki-check-title {
    color: var(--tki-prog-step-muted);
    font-weight: 500;
}
#progressBar .tki-check-marker {
    width: var(--tki-marker);
    height: var(--tki-marker);
    margin-top: 0;
    border-radius: 0.25rem;
    border: 1.5px solid var(--tki-prog-border);
    background: var(--tki-prog-panel);
    box-sizing: border-box;
    position: relative;
    z-index: 1;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--tki-prog-marker-fg);
}
#progressBar .tki-check-icon {
    width: var(--tki-marker-icon);
    height: var(--tki-marker-icon);
    display: block;
}
#progressBar .tki-check-item.is-active .tki-check-marker {
    border-color: var(--tki-prog-accent);
    color: var(--tki-prog-accent);
    box-shadow: 0 0 0 4px var(--tki-prog-accent-glow);
}
#progressBar .tki-check-item.is-done .tki-check-marker {
    border-color: var(--tki-prog-done);
    color: var(--tki-prog-done);
    background: var(--tki-prog-panel);
}
#progressBar .tki-check-item.is-skipped .tki-check-marker {
    background: var(--tki-prog-marker-skipped);
    color: var(--tki-prog-marker-fg);
}
#progressBar .tki-check-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    min-height: var(--tki-marker);
}
#progressBar .tki-check-title {
    font-size: 0.96875rem;
    font-weight: 600;
    line-height: 1.4;
}
#progressBar .tki-check-status {
    font-size: 0.71875rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--tki-prog-step-muted);
    white-space: nowrap;
}
#progressBar .tki-check-item.is-active .tki-check-status { color: var(--tki-prog-accent); }
#progressBar .tki-check-item.is-done .tki-check-status { color: var(--tki-prog-done); }
#progressBar .tki-api-fill {
    position: absolute;
    width: 0;
    height: 0;
    overflow: hidden;
    pointer-events: none;
}
#progressBar .tki-log-branch {
    display: none;
    position: relative;
    align-items: stretch;
    margin-top: 0.28rem;
    margin-bottom: 0.05rem;
    padding-left: 0;
}
#progressBar .tki-check-item.is-active .tki-log-branch:has(.tki-log-list li),
#progressBar .tki-check-item.is-active .tki-log-branch:has(.tki-log-current:not(:empty)),
#progressBar .tki-check-item.is-done .tki-log-branch:has(.tki-log-list li),
#progressBar .tki-check-item.is-active.has-log .tki-log-branch,
#progressBar .tki-check-item.is-done.has-log .tki-log-branch {
    display: flex;
}
#progressBar .tki-log-elbow {
    position: absolute;
    left: calc(0px - var(--tki-marker-gap) - var(--tki-marker) / 2);
    top: 0;
    width: calc(var(--tki-marker-gap) + var(--tki-marker) / 2 + 0.42rem);
    height: 0.7rem;
    border-left: 1px solid var(--tki-prog-border);
    border-bottom: 1px solid var(--tki-prog-border);
    border-bottom-left-radius: 6px;
    pointer-events: none;
}
#progressBar .tki-log-card {
    position: relative;
    flex: 1;
    min-width: 0;
    max-height: 9rem;
    overflow-y: auto;
    overflow-x: hidden;
    scroll-behavior: smooth;
    background: transparent;
    border: none;
    box-shadow: none;
    padding: 0;
    scrollbar-width: thin;
    scrollbar-color: var(--tki-prog-scrollbar) transparent;
}
#progressBar .tki-log-card::-webkit-scrollbar {
    width: 8px;
}
#progressBar .tki-log-card::-webkit-scrollbar-track {
    background: transparent;
}
#progressBar .tki-log-card::-webkit-scrollbar-thumb {
    background: var(--tki-prog-scrollbar);
    border-radius: 999px;
}
#progressBar .tki-log-card::-webkit-scrollbar-thumb:hover {
    background: var(--tki-prog-scrollbar-hover);
}
#progressBar .tki-log-card::before {
    content: "";
    position: absolute;
    left: 0.42rem;
    top: 0.35rem;
    bottom: 0;
    width: 1px;
    background: var(--tki-prog-border);
}
#progressBar .tki-log-list {
    display: none;
    list-style: none;
    margin: 0;
    padding: 0;
    overflow: visible;
    flex-direction: column;
    gap: 0.2rem;
}
#progressBar .tki-log-list:not(:empty) { display: flex; }
#progressBar .tki-log-current {
    display: none;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.71875rem;
    color: var(--tki-prog-text);
}
#progressBar .tki-log-current:not(:empty) { display: flex; }
#progressBar .tki-log-list:not(:empty) + .tki-log-current:not(:empty) { margin-top: 0.15rem; }
#progressBar .tki-log-item,
#progressBar .tki-log-current { font-size: 0.71875rem; }
#progressBar .tki-log-item {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: var(--tki-prog-muted);
    animation: tki-log-in 0.22s ease-out;
    position: relative;
    z-index: 1;
}
@keyframes tki-log-in {
    from { opacity: 0; transform: translateY(-3px); }
    to { opacity: 1; transform: none; }
}
#progressBar .tki-log-item__icon {
    width: 0.85rem;
    height: 0.85rem;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--tki-prog-done);
    background: var(--tki-prog-panel);
    border-radius: 50%;
}
#progressBar .tki-log-item.is-failed { color: var(--tki-prog-fail); }
#progressBar .tki-log-item.is-failed .tki-log-item__icon,
#progressBar .tki-log-item.is-failed .tki-log-item__name { color: var(--tki-prog-fail); }
#progressBar .tki-log-item.is-empty {
    color: var(--tki-prog-muted);
    font-style: italic;
}
#progressBar .tki-log-item.is-empty .tki-log-item__name {
    font-family: Montserrat, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-style: italic;
}
#progressBar .tki-log-item__name {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    word-break: break-all;
}
#progressBar .tki-log-spinner {
    width: 0.85rem;
    height: 0.85rem;
    border: 1.5px solid var(--tki-prog-spinner-border);
    border-top-color: var(--tki-prog-accent);
    border-radius: 50%;
    animation: tki-patch-spin 0.8s linear infinite;
    flex-shrink: 0;
    background: var(--tki-prog-panel);
    box-sizing: border-box;
}
@keyframes tki-patch-spin {
    to { transform: rotate(360deg); }
}
#progressBar.is-hiding {
    opacity: 0;
    transition: opacity 0.35s ease;
    pointer-events: none;
}
</style>
HTML;
    }

    private function renderMarkup(
        string $kicker,
        string $title,
        string $description,
        string $phaseSchema,
        string $phaseIndexing,
        string $phasePatches,
        string $phaseScripts,
        string $statusActive,
        string $statusWaiting
    ): string {
        $h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        $iconSchema = '<svg class="tki-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>';
        $iconIndexing = '<svg class="tki-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>';
        $iconPatches = '<svg class="tki-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6.878V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 004.5 8.878v10.5A2.25 2.25 0 006.75 21.75h10.5A2.25 2.25 0 0019.5 19.378V8.878A2.25 2.25 0 0017.25 6.75H6.75z"/></svg>';
        $iconScripts = '<svg class="tki-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6.75 7.5l3 2.25-3 2.25m4.5 3h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z"/></svg>';

        return '<div id="progressBar" class="tki-install-progress" data-tki-theme="light" role="dialog" aria-modal="true" aria-labelledby="tki-install-progress-title">'
            . '<div class="tki-install-progress__panel">'
            . '<header class="tki-install-progress__brand">'
            . '<p class="tki-install-progress__kicker">' . $h($kicker) . '</p>'
            . '<h1 id="tki-install-progress-title" class="tki-install-progress__title">' . $h($title) . '</h1>'
            . '<p class="tki-install-progress__desc">' . $h($description) . '</p>'
            . '</header>'
            . '<ol class="tki-checklist">'
            . $this->renderCheckItem('schema', 'is-active', $phaseSchema, $statusActive, $iconSchema, 'schema_list', 'schema_current')
            . $this->renderCheckItem('indexing', 'is-pending', $phaseIndexing, $statusWaiting, $iconIndexing, 'indexing_list', 'indexing_current')
            . $this->renderCheckItem('patches', 'is-pending', $phasePatches, $statusWaiting, $iconPatches, 'patch_list', 'patch_current')
            . $this->renderCheckItem('scripts', 'is-pending', $phaseScripts, $statusWaiting, $iconScripts, 'script_list', 'script_current')
            . '</ol>'
            . '</div>'
            . '</div>';
    }

    private function renderCheckItem(
        string $phase,
        string $state,
        string $label,
        string $status,
        string $icon,
        string $listId,
        string $currentId
    ): string {
        $h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $detailId = $phase === 'patches' || $phase === 'scripts' ? '' : '<div class="tki-api-fill" id="phase_detail_' . $phase . '"></div>';

        return '<li class="tki-check-item ' . $state . '" data-phase="' . $phase . '">'
            . '<span class="tki-check-marker" aria-hidden="true">' . $icon . '</span>'
            . '<div class="tki-check-body">'
            . '<div class="tki-check-row">'
            . '<span class="tki-check-title">' . $h($label) . '</span>'
            . '<span class="tki-check-status">' . $h($status) . '</span>'
            . '</div>'
            . '<div class="tki-api-fill"><div class="tki-step-bar-fill" id="progress_' . $phase . '"></div></div>'
            . '<span class="tki-api-fill" id="progress_' . $phase . '_pct">0%</span>'
            . $detailId
            . '<div class="tki-log-branch">'
            . '<span class="tki-log-elbow" aria-hidden="true"></span>'
            . '<div class="tki-log-card">'
            . '<ul class="tki-log-list" id="' . $listId . '"></ul>'
            . '<div class="tki-log-current" id="' . $currentId . '"></div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</li>';
    }

    private function renderScript(string $statusJson): string
    {
        return <<<HTML
<script class="progress_bar_script">
(function () {
    var PHASES = ["schema", "indexing", "patches", "scripts"];
    var STATUS = {$statusJson};
    var checkSvg = '<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M13.485 3.515a.75.75 0 0 1 .102 1.007l-7.25 8a.75.75 0 0 1-1.088.02L2.41 9.53a.75.75 0 1 1 1.06-1.06l2.17 2.17 6.72-7.41a.75.75 0 0 1 1.035-.105z"/></svg>';
    var failSvg = '<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M3.22 3.22a.75.75 0 011.06 0L8 6.94l3.72-3.72a.75.75 0 111.06 1.06L9.06 8l3.72 3.72a.75.75 0 11-1.06 1.06L8 9.06l-3.72 3.72a.75.75 0 01-1.06-1.06L6.94 8 3.22 4.28a.75.75 0 010-1.06z"/></svg>';
    var lastDetail = { schema: "", indexing: "" };
    var LIST_IDS = {
        schema: "schema_list",
        indexing: "indexing_list",
        patches: "patch_list",
        scripts: "script_list"
    };
    var CURRENT_IDS = {
        schema: "schema_current",
        indexing: "indexing_current",
        patches: "patch_current",
        scripts: "script_current"
    };
    var LOG_PACE_MS = 320;
    var EMPTY_STEP_MS = 550;
    var logQueue = [];
    var logQueueTimer = null;
    var logLastFlush = 0;

    function doc() {
        if (document.getElementById("progressBar")) {
            return document;
        }
        if (window.parent && window.parent !== window && window.parent.document
            && window.parent.document.getElementById("progressBar")) {
            return window.parent.document;
        }
        return document;
    }

    function root() {
        return doc().getElementById("progressBar");
    }

    function syncLogBranch(phase) {
        var step = stepEl(phase);
        if (!step) { return; }
        var list = listEl(phase);
        var current = currentEl(phase);
        var hasContent = (list && list.children.length > 0)
            || (current && current.textContent.replace(/\s/g, "").length > 0);
        step.classList.toggle("has-log", !!hasContent);
    }

    function logCardEl(phase) {
        var step = stepEl(phase);
        return step ? step.querySelector(".tki-log-card") : null;
    }

    function scrollLogCard(phase) {
        var card = logCardEl(phase);
        if (!card) { return; }
        window.requestAnimationFrame(function () {
            card.scrollTop = card.scrollHeight;
        });
    }

    function scheduleLogQueue() {
        if (logQueueTimer || !logQueue.length) { return; }
        var wait = Math.max(0, LOG_PACE_MS - (Date.now() - logLastFlush));
        logQueueTimer = window.setTimeout(function () {
            logQueueTimer = null;
            if (!logQueue.length) { return; }
            var job = logQueue.shift();
            job.run();
            logLastFlush = Date.now();
            scrollLogCard(job.phase);
            scheduleLogQueue();
        }, wait);
    }

    function queueLogJob(phase, type, fn) {
        if (type === "current") {
            logQueue = logQueue.filter(function (job) {
                return !(job.phase === phase && job.type === "current");
            });
        }
        logQueue.push({ phase: phase, type: type, run: fn });
        scheduleLogQueue();
    }

    function flushLogQueue() {
        if (logQueueTimer) {
            window.clearTimeout(logQueueTimer);
            logQueueTimer = null;
        }
        while (logQueue.length) {
            var job = logQueue.shift();
            job.run();
            scrollLogCard(job.phase);
        }
        logLastFlush = Date.now();
    }

    function applyTheme() {
        var el = root();
        if (!el) { return; }
        var theme = document.documentElement.getAttribute("data-tki-theme");
        if (theme !== "dark" && theme !== "light") {
            theme = "light";
            try {
                var saved = localStorage.getItem("tki-theme");
                if (saved === "dark" || saved === "light") {
                    theme = saved;
                } else if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
                    theme = "dark";
                }
            } catch (e) {}
        }
        el.setAttribute("data-tki-theme", theme);
    }

    function stepEl(phase) {
        var r = root();
        return r ? r.querySelector('[data-phase="' + phase + '"]') : null;
    }

    function listEl(phase) {
        return doc().getElementById(LIST_IDS[phase]);
    }

    function currentEl(phase) {
        return doc().getElementById(CURRENT_IDS[phase]);
    }

    function phaseHasLogItems(phase) {
        var list = listEl(phase);
        var current = currentEl(phase);
        return (list && list.children.length > 0)
            || (current && current.textContent.replace(/\s/g, "").length > 0);
    }

    function showNoItemsMessage(phase) {
        var list = listEl(phase);
        if (!list || list.querySelector(".tki-log-item.is-empty")) { return; }
        var item = doc().createElement("li");
        item.className = "tki-log-item is-empty";
        item.innerHTML = '<span class="tki-log-item__name"></span>';
        item.querySelector(".tki-log-item__name").textContent = STATUS.noItems || "No items found";
        list.appendChild(item);
        syncLogBranch(phase);
        scrollLogCard(phase);
    }

    function ensurePhaseChecked(phase) {
        if (!phaseHasLogItems(phase)) {
            showNoItemsMessage(phase);
        }
    }

    function markPhaseDone(phase) {
        var step = stepEl(phase);
        if (!step || step.classList.contains("is-done")) { return; }
        settlePhaseLog(phase);
        ensurePhaseChecked(phase);
        setPhaseState(phase, "done");
        if (phase !== "patches" && phase !== "scripts") {
            var bar = doc().getElementById("progress_" + phase);
            var label = doc().getElementById("progress_" + phase + "_pct");
            if (bar) { bar.style.width = "100%"; }
            if (label) { label.textContent = "100%"; }
        }
    }

    function finishPendingPhases(phases, index, done) {
        if (index >= phases.length) {
            done();
            return;
        }
        var phase = phases[index];
        var step = stepEl(phase);
        if (!step || step.classList.contains("is-done")) {
            finishPendingPhases(phases, index + 1, done);
            return;
        }
        setPhaseState(phase, "active");
        showNoItemsMessage(phase);
        window.setTimeout(function () {
            setPhaseState(phase, "done");
            finishPendingPhases(phases, index + 1, done);
        }, EMPTY_STEP_MS);
    }

    function hideProgressOverlay() {
        var overlay = root();
        if (!overlay) { return; }
        var body = doc().body;
        if (body && overlay.parentNode !== body) {
            body.appendChild(overlay);
        }
        overlay.classList.add("is-complete", "is-hiding");
        window.setTimeout(function () {
            overlay.style.display = "none";
        }, 380);
    }

    function appendLogItemImmediate(phase, name, ok) {
        var list = listEl(phase);
        if (!list || !name) { return; }
        var names = list.querySelectorAll(".tki-log-item__name");
        for (var i = 0; i < names.length; i++) {
            if (names[i].textContent === name) { return; }
        }
        var failed = ok === false;
        var item = doc().createElement("li");
        item.className = "tki-log-item" + (failed ? " is-failed" : " is-ok");
        item.innerHTML = '<span class="tki-log-item__icon">' + (failed ? failSvg : checkSvg) + '</span><span class="tki-log-item__name"></span>';
        item.querySelector(".tki-log-item__name").textContent = name;
        list.appendChild(item);
        syncLogBranch(phase);
    }

    function appendLogItem(phase, name, ok) {
        queueLogJob(phase, "append", function () {
            appendLogItemImmediate(phase, name, ok);
        });
    }

    function setLogCurrentImmediate(phase, name) {
        var current = currentEl(phase);
        if (!current) { return; }
        if (!name) {
            current.innerHTML = "";
            syncLogBranch(phase);
            return;
        }
        current.innerHTML = '<span class="tki-log-spinner" aria-hidden="true"></span><span class="tki-log-item__name"></span>';
        current.querySelector(".tki-log-item__name").textContent = name;
        syncLogBranch(phase);
    }

    function setLogCurrent(phase, name) {
        if (!name) {
            logQueue = logQueue.filter(function (job) {
                return !(job.phase === phase && job.type === "current");
            });
            setLogCurrentImmediate(phase, name);
            scrollLogCard(phase);
            return;
        }
        queueLogJob(phase, "current", function () {
            setLogCurrentImmediate(phase, name);
        });
    }

    function completeLogItem(phase, name, ok) {
        setLogCurrent(phase, "");
        appendLogItem(phase, name, ok !== false);
    }

    function failLogItem(phase, name) {
        setLogCurrent(phase, "");
        if (lastDetail[phase] === name) {
            lastDetail[phase] = "";
        }
        appendLogItem(phase, name, false);
    }

    function settlePhaseLog(phase) {
        var pending = lastDetail[phase];
        if (pending) {
            completeLogItem(phase, pending);
            lastDetail[phase] = "";
        }
        setLogCurrent(phase, "");
    }

    function setPhaseState(phase, state) {
        var step = stepEl(phase);
        if (!step) { return; }
        step.classList.remove("is-active", "is-done", "is-pending", "is-skipped");
        step.classList.add("is-" + state);
        var status = step.querySelector(".tki-check-status");
        if (status) { status.textContent = STATUS[state] || state; }
    }

    function setActivePhase(phase) {
        flushLogQueue();
        var activeIndex = PHASES.indexOf(phase);
        PHASES.forEach(function (p, index) {
            if (index < activeIndex) {
                settlePhaseLog(p);
                var step = stepEl(p);
                if (step && !step.classList.contains("is-done") && !step.classList.contains("is-skipped")) {
                    ensurePhaseChecked(p);
                    setPhaseState(p, "done");
                    if (p !== "patches" && p !== "scripts") {
                        window.TikiInstallerProgress.setPhaseProgress(p, 100);
                    }
                }
            } else if (p === phase) {
                setPhaseState(p, "active");
            } else if (index > activeIndex) {
                setPhaseState(p, "pending");
            }
        });
    }

    window.TikiInstallerProgress = {
        setPhaseState: setPhaseState,
        setActivePhase: setActivePhase,
        setPhaseProgress: function (phase, percent) {
            var bar = doc().getElementById("progress_" + phase);
            var label = doc().getElementById("progress_" + phase + "_pct");
            var value = Math.max(0, Math.min(100, percent));
            if (bar) { bar.style.width = value + "%"; }
            if (label) { label.textContent = value + "%"; }
            if (percent >= 100 && phase !== "patches" && phase !== "scripts") {
                settlePhaseLog(phase);
                ensurePhaseChecked(phase);
                setPhaseState(phase, "done");
            }
        },
        setPhaseDetail: function (phase, text) {
            var hidden = doc().getElementById("phase_detail_" + phase);
            if (hidden) { hidden.textContent = text; }
            if (!text) { return; }
            var prev = lastDetail[phase];
            if (prev && prev !== text) {
                completeLogItem(phase, prev);
            }
            lastDetail[phase] = text;
            setLogCurrent(phase, text);
        },
        setCurrentPatch: function (name) {
            setLogCurrent("patches", name);
        },
        completePatch: function (name) {
            completeLogItem("patches", name);
        },
        failPatch: function (name) {
            failLogItem("patches", name);
        },
        failTask: failLogItem,
        setScriptCurrent: function (name) {
            setLogCurrent("scripts", name);
        },
        completeScript: function (name) {
            completeLogItem("scripts", name);
        },
        markEmptyPhase: function (phase) {
            var step = stepEl(phase);
            if (!step || step.classList.contains("is-done")) { return; }
            flushLogQueue();
            if (phaseHasLogItems(phase)) {
                markPhaseDone(phase);
                return;
            }
            setPhaseState(phase, "active");
            showNoItemsMessage(phase);
            window.setTimeout(function () {
                setPhaseState(phase, "done");
            }, EMPTY_STEP_MS);
        },
        finalize: function () {
            flushLogQueue();
            var pendingEmpty = [];
            PHASES.forEach(function (phase) {
                var step = stepEl(phase);
                if (!step) { return; }
                if (step.classList.contains("is-pending")) {
                    pendingEmpty.push(phase);
                } else if (step.classList.contains("is-active")) {
                    markPhaseDone(phase);
                } else {
                    settlePhaseLog(phase);
                }
            });
            if (pendingEmpty.length) {
                finishPendingPhases(pendingEmpty, 0, hideProgressOverlay);
            } else {
                hideProgressOverlay();
            }
        }
    };

    applyTheme();

    var body = doc().body;
    if (body) {
        body.style.margin = "0";
        body.style.padding = "0";
        body.style.minHeight = "100vh";
    }
})();
</script>
HTML;
    }
}
