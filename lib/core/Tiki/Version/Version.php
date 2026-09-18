<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Version_Version
{
    private const SUB_VCS = 'vcs';
    private const SUB_SUFFIXES = ['alpha', 'beta', 'rc', 'pre', self::SUB_VCS];

    private $major;
    private $minor;
    private $extra;
    private $sub;
    private $number;

    public function __construct($major, $minor, $extra = 0, $sub = 0, $number = 0)
    {
        $this->major = (int) $major;
        $this->minor = (int) $minor;
        $this->extra = $extra;
        $this->sub = $sub;
        $this->number = (int) $number;
    }

    public static function get($version)
    {
        if ($version instanceof self) {
            return $version;
        } else {
            $suffixes = implode('|', self::SUB_SUFFIXES);
            preg_match('/^(\d+)\.(\d+)?(\.([\d\.]+))?((' . $suffixes . ')(\d*))?$/i', $version, $parts);
            for ($i = 0; 8 > $i; ++$i) {
                if (! isset($parts[$i])) {
                    $parts[$i] = null;
                }
            }

            return new self($parts[1], $parts[2], $parts[4], $parts[6], $parts[7]);
        }
    }

    public function getMajor()
    {
        return $this->major;
    }

    public function getMinor()
    {
        return $this->minor;
    }

    public function isStable()
    {
        return empty($this->sub);
    }

    /**
     * Whether this version string's sub-segment is "vcs" (e.g. "30.1vcs"),
     * marking it as a VCS/git checkout rather than a tagged release.
     * Distinct from Checker::isDevelopmentBranch(), which checks the
     * running install's branch instead of a parsed version string.
     */
    public function isVcs(): bool
    {
        return strtolower((string) $this->sub) === self::SUB_VCS;
    }

    public function isUpgradeTo($version)
    {
        // Note that this does not cover all cases, upgrades are only official releases
        // and the 'extra' portion is ignored as only used by legacy versions, which anything
        // is an upgrade to

        if ($this->major > $version->major) {
            return true;
        } elseif ($this->major == $version->major && $this->minor > $version->minor) {
            return true;
        } elseif ($this->major == $version->major && $this->minor == $version->minor) {
            return empty($this->sub) && ! empty($version->sub);
        } else {
            return false;
        }
    }

    public function isStableUpgradeTo($version)
    {
        if (! $this->isStable()) {
            return false;
        }

        return $this->isUpgradeTo($version);
    }

    public function __toString()
    {
        $string = "{$this->major}.{$this->minor}";

        if ($this->extra) {
            $string .= ".{$this->extra}";
        }

        if ($this->sub) {
            $string .= $this->sub;

            if ($this->number) {
                $string .= $this->number;
            }
        }

        return $string;
    }
}
