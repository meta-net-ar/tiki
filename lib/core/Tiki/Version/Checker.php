<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Version_Checker
{
    private $cycle;
    private $version;

    public function setCycle($cycle)
    {
        $this->cycle = $cycle;
    }

    public function setVersion($version)
    {
        $this->version = Tiki_Version_Version::get($version);
    }

    public function check($callback)
    {
        $upgrades = [];
        $branchupdate = null;

        $content = call_user_func($callback, "http://tiki.org/{$this->cycle}.cycle");
        $versions = $this->getSupportedVersions($content);

        if ($supported = $this->findSupportedInBranch($versions)) {
            if ($supported->isStableUpgradeTo($this->version)) {
                $upgrades[] = new Tiki_Version_Upgrade($this->version, $supported, true);
                $branchupdate = $supported;
            }
        }

        $max = $this->getLatestVersion($versions);

        if ($max !== $branchupdate && (! $this->version->isStable() || $max->isStableUpgradeTo($this->version))) {
            $upgrades[] = new Tiki_Version_Upgrade($supported ?: $this->version, $max, $supported === false);
        }

        return $upgrades;
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
