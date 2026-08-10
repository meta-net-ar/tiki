<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Version_Checker
{
    private $cycle;
    private $version;
    private $isSupportedInCycle;

    public function setCycle($cycle)
    {
        $this->cycle = $cycle;
    }

    public function setVersion($version)
    {
        $this->version = Tiki_Version_Version::get($version);
        $this->isSupportedInCycle = false;
    }

    public function check($callback)
    {
        global $prefs, $TWV;

        $upgrades = [];
        $branchupdate = null;
        $isVcsVersion = $this->version->isVcs();

        // Perform Original EoL and Upgrade Check from .cycle file
        $content = call_user_func($callback, "https://tiki.org/{$this->cycle}.cycle");
        $versions = $this->getSupportedVersions($content);
        $supportedInBranch = $this->findSupportedInBranch($versions);
        $this->isSupportedInCycle = (bool) $supportedInBranch;
        $latestOverall = $this->getLatestVersion($versions);

        // Do not show an upgrade notice for a non-trunk VCS checkout when the
        // tagged release has the same major and minor version (for example, 30.1vcs and 30.1).
        if ($supportedInBranch && (! $isVcsVersion || $this->isDevelopmentBranch($TWV) || ! $this->isSameBaseVersion($supportedInBranch))) {
            if ($supportedInBranch->isStableUpgradeTo($this->version)) {
                $messageType = $isVcsVersion ? 'note' : 'error';
                $upgrades[] = new Tiki_Version_Upgrade($this->version, $supportedInBranch, $messageType);
                $branchupdate = $supportedInBranch;
            }
        }
        if ($latestOverall && $latestOverall !== $branchupdate && (! $isVcsVersion || $this->isDevelopmentBranch($TWV) || ! $this->isSameBaseVersion($latestOverall))) {
            // If current is unstable OR max is a stable upgrade to current
            if (! $this->version->isStable() || $latestOverall->isStableUpgradeTo($this->version)) {
                $fromVersion = $this->isSupportedInCycle ? $supportedInBranch : $this->version;
                $messageType = ($this->isSupportedInCycle || ! $this->version->isStable()) ? 'note' : 'error';

                $upgrades[] = new Tiki_Version_Upgrade($fromVersion ?: $this->version, $latestOverall, $messageType);
            }
        }

        // Enhance with Approaching EoL Date Check (if feature enabled and version is supported)
        // NOTE: intentionally NOT gated by $isVcsVersion - a git checkout of a
        // supported branch approaching EoL should still be warned about its upcoming EoL date.
        if ($this->isSupportedInCycle && ($prefs['feature_eol_date_notifier'] ?? 'n') === 'y') {
            $eolMessages = $this->checkEolDates($TWV);
            $upgrades = array_merge($upgrades, $eolMessages);
        }

        return $upgrades;
    }

    private function isDevelopmentBranch(TWVersion $versionManager): bool
    {
        $branch = strtolower((string) ($versionManager->branch ?? ''));

        return $branch === 'trunk';
    }

    private function isSameBaseVersion(Tiki_Version_Version $otherVersion): bool
    {
        return $this->version->getMajor() === $otherVersion->getMajor()
            && $this->version->getMinor() === $otherVersion->getMinor();
    }

    /**
    * To check for approaching EoL dates for SUPPORTED versions.
    * @param TWVersion $versionManager The TWVersion object.
    * @return array An array of Tiki_Version_Upgrade objects.
    */
    private function checkEolDates(TWVersion $versionManager): array
    {
        $messages = [];
        $majorVersion = $this->version->getMajor();
        $ltsEolDates = $versionManager->getLtsEolDates();

        if (! isset($ltsEolDates[$majorVersion])) {
            return [];
        }

        $eolTimestamp = strtotime($ltsEolDates[$majorVersion]);

        if ($eolTimestamp === false) { // Check for invalid date format
            return [];
        }
        $now = time();
        $sixMonthsThreshold = strtotime('+6 months');

        // If the EoL is in the future AND is less than 6 months away
        if (($eolTimestamp > $now) && ($eolTimestamp < $sixMonthsThreshold)) {
            $messages[] = new Tiki_Version_Upgrade($this->version, null, 'warning');
        }

        return $messages;
    }

    private function getSupportedVersions($content)
    {
        return array_filter(array_map(['Tiki_Version_Version', 'get'], explode("\n", $content)));
    }

    private function findSupportedInBranch($versions)
    {
        foreach ($versions as $supported) {
            if ($supported->getMajor() == $this->version->getMajor()) {
                return $supported;
            }
        }

        return false;
    }

    private function getLatestVersion($versions)
    {
        $max = array_shift($versions);

        foreach ($versions as $candidate) {
            if ($candidate->isStableUpgradeTo($max)) {
                $max = $candidate;
            }
        }

        return $max;
    }
}
