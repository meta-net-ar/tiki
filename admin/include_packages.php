<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Package\ComposerManager;
use Tiki\Package\ComposerCli;
use Tiki\Package\ExtensionManager;
use Tiki\Process\PhpExecutableFinder;

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

global $tikipath;

$composerManager = new ComposerManager($tikipath);
$composerManagerBundled = new ComposerManager($tikipath, $tikipath . DIRECTORY_SEPARATOR . 'vendor_bundled');
$composerManagerCustom = new ComposerManager($tikipath, $tikipath . DIRECTORY_SEPARATOR . 'vendor_custom');

//Load package detail via parckigist API
if (isset($_REQUEST['package_name'])) {
    $data = file_get_contents("https://repo.packagist.org/p2/" . $_REQUEST['package_name'] . ".json");
    echo $data;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (! empty($_POST['auto-fix-missing-packages']) && $access->checkCsrf()) {
        $smarty->assign('composer_output', $composerManager->fixMissing());
    }
    if (! empty($_POST['auto-install-package']) && $access->checkCsrf()) {
        $smarty->assign('composer_output', $composerManager->installPackage($_POST['auto-install-package']));
    }
    if (! empty($_POST['auto-update-package']) && $access->checkCsrf()) {
        $smarty->assign('composer_output', $composerManager->updatePackage($_POST['auto-update-package']));
    }
    if (! empty($_POST['auto-remove-package']) && $access->checkCsrf()) {
        $smarty->assign('composer_output', $composerManager->removePackage($_POST['auto-remove-package']));
    }
    if (! empty($_POST['enable-extension-package']) && $access->checkCsrf()) {
        $packageName = $_POST['enable-extension-package'];
        $packagePath = ExtensionManager::locatePackage($packageName);
        $status = ExtensionManager::enableExtension($packageName, $packagePath);
        $smarty->assign('extensions_status', $status);
        $smarty->assign('extensions_output', implode(PHP_EOL, ExtensionManager::getMessages()));
    }
    if (! empty($_POST['disable-extension-package']) && $access->checkCsrf()) {
        $status = ExtensionManager::disableExtension($_POST['disable-extension-package']);
        $smarty->assign('extensions_status', $status);
        $smarty->assign('extensions_output', implode(PHP_EOL, ExtensionManager::getMessages()));
    }
    if (! empty($_POST['auto-run-diagnostics']) && $access->checkCsrf()) {
        if (! $composerManager->composerIsAvailable()) {
            $smarty->assign('diagnostic_composer_location', '');
            $smarty->assign('diagnostic_composer_output', '');
        } else {
            $smarty->assign('diagnostic_composer_location', $composerManager->composerPath());
            $smarty->assign('diagnostic_composer_output', $composerManager->getComposer()->execDiagnose());
        }
        if (! empty($_POST['remove-composer-locker'])) {
            $path = $tikipath . DIRECTORY_SEPARATOR . 'composer.lock';
            if (file_exists($path)) {
                if (is_writable($path)) {
                    unlink($path);
                    $smarty->assign('composer_management_success', tr('composer.lock file was removed'));
                } else {
                    $smarty->assign('composer_management_error', tr('composer.lock file is not writable, so it can not be removed'));
                }
            } else {
                $smarty->assign('composer_management_success', tr('composer.lock file do not exists'));
            }
        }
        if (! empty($_POST['clean-vendor-folder'])) {
            $dir = $tikipath . DIRECTORY_SEPARATOR . 'vendor/';
            if (file_exists($dir)) {
                if (is_writable($dir)) {
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($files as $file) {
                        if ($file->getFilename() === '.htaccess') {
                            continue;
                        }
                        if ($file->isDir()) {
                            rmdir($file->getRealPath());
                        } else {
                            unlink($file->getRealPath());
                        }
                    }
                    $smarty->assign('composer_management_success', tr('Vendor folder contents was removed'));
                } else {
                    $smarty->assign('composer_management_error', tr('Vendor folder is not writable'));
                }
            } else {
                $smarty->assign('composer_management_success', tr('Vendor folder do not exists'));
            }
        }
    }
    if (! empty($_POST['install-composer'])) {
        $composerWrapper = new ComposerCli($tikipath);
        list($composerResult, $composerResultMessage) = $composerWrapper->installComposer();
        if ($composerResult) {
            $smarty->assign('composer_management_success', $composerResultMessage);
        } else {
            $smarty->assign('composer_management_error', $composerResultMessage);
        }
    }
    if (! empty($_POST['update-composer'])) {
        $composerWrapper = new ComposerCli($tikipath);
        list($composerResult, $composerResultMessage) = $composerWrapper->updateComposer();
        if ($composerResult) {
            $smarty->assign('composer_management_success', $composerResultMessage);
        } else {
            $smarty->assign('composer_management_error', $composerResultMessage);
        }
    }
}

$installableList = $composerManager->getInstalled();
$lastResult = $composerManager->getComposer()->getLastResult();
if ($lastResult !== null && ! empty($lastResult['errors'])) {
    $smarty->assign('composer_installed_errors', $lastResult['errors']);
}

if ($installableList === false) {
    $packagesMissing = false;
    $installableList = [];
} else {
    $packagesMissing = array_reduce(
        $installableList,
        function ($carry, $item) {
            return $carry || $item['status'] === ComposerManager::STATUS_MISSING;
        },
        false
    );
}

$packageprefs = TikiLib::lib('prefs')->getPackagePrefs();
asort($packageprefs);
$smarty->assign('packageprefs', $packageprefs);

$smarty->assign('composer_environment_warning', $composerManager->checkThatCanInstallPackages());
$smarty->assign('composer_available', $composerManager->composerIsAvailable());
$smarty->assign('composer_packages_installed', $installableList);
$smarty->assign('composer_packages_missing', $packagesMissing);
$smarty->assign('composer_packages_available', $composerManager->getAvailable(true, true));
$smarty->assign('composer_bundled_packages_installed', $composerManagerBundled->getInstalled());
$smarty->assign('composer_custom_packages_installed', $composerManagerCustom->getCustomPackages());
$smarty->assign('composer_phar_exists', $composerManager->getComposer()->composerPharExists());

$deprecatedPackagesFromYml = $composerManager->getListOfDeprecatedPackages();
$deprecatedPackageNames = array_column($deprecatedPackagesFromYml, 'name');
$installedPackagesNames = array_column($installableList, 'name');
$installedDeprecatedPackages = array_values(array_intersect($installedPackagesNames, $deprecatedPackageNames));
$smarty->assign('installedDeprecatedPackages', $installedDeprecatedPackages);

$finder = new PhpExecutableFinder();
$phpCli = $finder->find($phpCliVersion);
$majorMinorOffset = strpos(PHP_VERSION, '.', 2);
if (empty($phpCli)) { // No cli detected
    $phpCliList = $finder->findAll(false, true);
    if (count($phpCliList) == 0) {
        $phpCliListAsString = tr('None detected');
    } else {
        $phpCliListAsString = implode(', ', array_map(
            function ($item): string {
                return $item['command'] . '(' . $item['version'] . ')';
            },
            $phpCliList
        ));
    }
    $smarty->assign(
        'composer_php_version_mismatch',
        tr(
            'No suitable php command line binary detected, it’s recommended that you define the PATH <br>to the right PHP command line version using the preference %0 <br>',
            '<a class="lm_result label label-default" href="tiki-admin.php?page=general&cookietab=2&highlight=php_cli_path">php_cli_path</a>'
        )
        . '&nbsp; <br>'
        . tr(
            'Current version reported by the webserver (%0), current minimal version required (%1) <br>PHP command line binaries detected: %2',
            PHP_VERSION,
            $finder->getMinimalVersionSupported(),
            $phpCliListAsString
        )
    );
} elseif (strncmp(PHP_VERSION, $phpCliVersion, $majorMinorOffset) !== 0) {
    $smarty->assign(
        'composer_php_version_mismatch',
        tr(
            'There is a mismatch between the PHP version (%0) reported by the webserver and the version <br>'
            . 'reported by the command line binary (%1: %2), it’s recommended that you define the PATH to the right <br>'
            . 'PHP command line version using the preference %3',
            PHP_VERSION,
            $phpCli,
            $phpCliVersion,
            '<a class="lm_result label label-default" href="tiki-admin.php?page=general&cookietab=2&highlight=php_cli_path">php_cli_path</a>'
        )
    );
} elseif (! $finder->isVersionSupported($phpCliVersion)) {
    $smarty->assign(
        'composer_php_version_mismatch',
        tr(
            'The version reported by the PHP command line binary (%0: %1) is older than the <br>'
            . 'minimal version recommended (%2), it’s recommended that you define the PATH to the right <br>'
            . 'PHP command line version using the preference %3',
            $phpCli,
            $phpCliVersion,
            $finder->getMinimalVersionSupported(),
            '<a class="lm_result label label-default" href="tiki-admin.php?page=general&cookietab=2&highlight=php_cli_path">php_cli_path</a>'
        )
    );
}
