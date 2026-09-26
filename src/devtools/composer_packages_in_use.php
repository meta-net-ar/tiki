<?php

/*
 * Find a list of all the vendor_bundled pagages in use in all (relevant) versions
 * in order to delete unused files from composer.tiki.org (which is full!)
 */

if (PHP_SAPI !== 'cli') {
    die("Please run from a shell");
}

require __DIR__ . '/../../path_constants.php';

class ComposerGetPackages
{
    public $jsonFile;
    private string $repoUri;

    private string $lockFile;
    private \GitLib $gitLib;
    private int $minimumVersion;

    public function __construct()
    {
        $this->minimumVersion = 11;

        $this->repoUri = 'https://gitlab.com/tikiwiki/tiki/-/raw/%branch%/';
        $this->lockFile = TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH . '/composer.lock';
        $this->jsonFile = TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH . '/composer.json';

        $this->gitLib = \TikiLib::lib('git');
    }

    public function execute(array $args)
    {
        if (count($args) > 1) {
            $mode = $args[1];
        } else {
            $mode = '';
        }

        if ($mode === 'current') {
            $file = $this->lockFile;
        } elseif (! $mode || $mode === 'all') {
            $file = $this->jsonFile;
        }

        echo 'Getting branches...';
        $branches = $this->getBranches($this->minimumVersion);
        echo ' done (' . count($branches) . " found)\n";
        ob_flush();

        $packagesInUse = [];
        $outputData = [];

        echo 'Reading';
        foreach ($branches as $branch) {
            $json = $this->loadComposerFile($branch, $file);
            echo '.';
            ob_flush();

            if ($json) {
                $jsonData = $this->parseComposerLock($json);
                if ($mode === 'curent') {
                    foreach ($jsonData->packages as $package) {
                        if (! isset($packagesInUse[$package->name])) {
                            $packagesInUse[$package->name] = [];
                        }
                        if (! in_array($package->version, $packagesInUse[$package->name])) {
                            $packagesInUse[$package->name][] = $package->version;
                        }
                    }
                } else {
                    if (empty($outputData)) {
                        $outputData = $jsonData;
                        $outputData->require = new \stdClass();
                        $outputData->{'require-dev'} = new \stdClass();
                    }
                    foreach ([$jsonData->require, $jsonData->{'require-dev'}] as $requires) {
                        foreach ($requires as $package => $versionString) {
                            //$versions = Composer\Semver\Semver::satisfiedBy([$versionString]);
                            if (! isset($packagesInUse[$package])) {
                                $packagesInUse[$package] = [];
                            }
                            if (! in_array($versionString, $packagesInUse[$package])) {
                                $packagesInUse[$package][] = $versionString;
                            }
                        }
                    }
                }
            }
        }
        echo "\nDone\n\n";
        ob_flush();

        foreach ($packagesInUse as $packageName => $versions) {
            sort($versions);
            if ($mode === 'current') {
                foreach ($versions as $version) {
                    echo "$packageName/$version\n";
                }
            } else {
                $outputData->require->{$packageName} = implode('|', array_unique($versions));
            }
        }

        if ($mode !== 'current') {
            echo json_encode($outputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    private function getBranches(int $minimumVersion)
    {

        $output = $this->gitLib->run_git(['ls-remote']);
        $r = preg_match_all('/.*refs\/heads\/(.*)$/m', $output, $remotes);
        $branches = ['master'];
        if ($r) {
            foreach ($remotes[1] as $branchName) {
                preg_match('/\d+/', $branchName, $matches);
                if ($matches) {
                    if ($matches[0] >= $minimumVersion) {
                        $branches[] = $branchName;
                    }
                } else {
                    $branches[] = $branchName;
                }
            }
        }
        return array_unique($branches);
    }

    private function loadComposerFile(string $branch, $file): string
    {
        $repoUri = $this->repoUri . $file;
        $uri = str_replace('%branch%', $branch, $repoUri);
        $json = @file_get_contents($uri);
        return $json;
    }

    private function parseComposerLock(string $json): ?object
    {
        $data = json_decode($json);

        return $data;
    }
}


require_once('tiki-setup.php');

$purger = new ComposerGetPackages();
$purger->execute($argv);
