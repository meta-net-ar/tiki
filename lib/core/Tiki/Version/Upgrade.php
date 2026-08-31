<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Version_Upgrade
{
    // old actually means current
    private $old;
    private $new;
    private $messageType;
    private $eolDate;
    private $isLTS;
    private $detailsCalculated = false;

    /**
     * @param string $old The current version string.
     * @param string|null $new The new/target version string, or null if not applicable.
     * @param string $messageType The type of message ('error', 'warning', 'note').
    */
    public function __construct(string $old, ?string $new, string $messageType)
    {
        $this->old = Tiki_Version_Version::get($old);
        if ($new) {
            $this->new = Tiki_Version_Version::get($new);
        }
        $this->messageType = $messageType;
    }

    /**
    * Lazily calculates and caches the EoL information for the version.
    * @return void
    */
    private function calculateEolDetails(): void
    {
        if ($this->detailsCalculated) {
            return;
        }
        global $TWV;
        $ltsEolDates = $TWV->getLtsEolDates();
        $majorVersion = $this->old->getMajor();

        if (isset($ltsEolDates[$majorVersion])) {
            $this->eolDate = $ltsEolDates[$majorVersion];
            $this->isLTS = true;
        } else {
            $this->eolDate = tr("at least until Tiki %0.1 is released", ($majorVersion + 1));
            $this->isLTS = false;
        }

        $this->detailsCalculated = true;
    }

    /**
    * Returns the appropriate remarksbox type ('error', 'warning', or 'note')
    * based on the message urgency.
    * @return string
    */
    public function getType(): string
    {
        return $this->messageType;
    }

    /**
    * Returns the full, formatted message to be displayed to the user.
    * @return string
    */
    public function getMessage(): string
    {
        // 1. Check for Unstable/VCS version first (Logic from Other MR)
        if (! $this->old->isStable() && $this->new) {
            if ($this->new->isStableUpgradeTo($this->old)) {
                return tr(
                    'You are using a development version: %0. This version is intended for testing and development purposes. For stability, consider switching to the latest stable release: %1.',
                    (string) $this->old,
                    (string) $this->new
                );
            } else {
                return tr(
                    'You are using a development version: %0. This version is intended for testing and development purposes. The latest stable release (%1) is older than your current version, so downgrading is not recommended.',
                    (string) $this->old,
                    (string) $this->new
                );
            }
        }

        // 2. Calculate details for standard messages (Your Logic)
        $this->calculateEolDetails();

        // 3. Return message based on type
        switch ($this->getType()) {
            case 'error':
                return $this->getUnsupportedMessage();
            case 'warning':
                return $this->getEolSoonMessage();
            case 'note':
                return $this->getUpgradeAvailableMessage();
            default:
                return ''; // Should not be reached
        }
    }

    /**
    * Builds the message for versions approaching End-of-Life.
    * @return string
    */
    private function getEolSoonMessage(): string
    {
        $message = tr('This version is approaching its End of Life on %0.', $this->eolDate);
        $message .= ' ' . tra('To ensure your site remains secure, planning an upgrade is recommended.');
        return $message;
    }

    /**
    * Builds the message for unsupported versions.
    * @return string
    */
    private function getUnsupportedMessage(): string
    {
        global $TWV;
        $parts = [];
        $parts[] = '<strong>' . tr('Version %0 is no longer supported.', (string) $this->old) . '</strong>';
        if ($this->new) {
            $parts[] = $this->isMinor()
                ? tr('A minor update to %0 is strongly recommended.', (string) $this->new)
                : tr('A major upgrade to %0 is strongly recommended.', (string) $this->new);
        }
        $providers = $TWV->getExtendedSupportProviders();
        if (! empty($providers)) {
            $providerLinks = [];
            foreach ($providers as $provider) {
                $providerLinks[] = '<a href="' . htmlspecialchars($provider['url']) . '" target="_blank" class="alert-link">' . htmlspecialchars($provider['name']) . '</a>';
            }
            $providerListItems = '<li>' . implode('</li><li>', $providerLinks) . '</li>';
            $parts[] = tr('For organizations requiring Extended Security Maintenance, professional services are available from the following providers: <ul>%0</ul>', $providerListItems);
        }
        return implode(' ', $parts);
    }

    /**
    * Builds the message for supported versions where an upgrade is available.
    * @return string
    */
    private function getUpgradeAvailableMessage(): string
    {
        $eolNotice = '';
        if ($this->eolDate) {
            if (strtotime($this->eolDate)) {
                $eolNotice = ' ' . tr('(End of Life: %0)', $this->eolDate);
            } else {
                $eolNotice = ' (' . $this->eolDate . ')';
            }
        }

        if ($this->isLTS) {
            $current = (string) $this->old . " LTS";
            return tr('Version %0 is still supported%2. However, an upgrade to %1 is available.', $current, (string) $this->new, $eolNotice);
        } else {
            return tr('Version %0 is still supported%2. However, a major upgrade to %1 is available.', (string) $this->old, (string) $this->new, $eolNotice);
        }
    }

    /**
    * Allows the object to be used directly where a message string is expected,
    * e.g. in templates rendering data restored from an older session.
    * @return string
    */
    public function __toString(): string
    {
        return $this->getMessage();
    }

    private function isMinor()
    {
        return $this->old->getMajor() === $this->new->getMajor();
    }
}
