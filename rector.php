<?php

declare(strict_types=1);

/*

To run rector (https://getrector.com/documentation) on a file or directory, run:

php vendor_bundled/vendor/rector/rector/bin/rector process --memory-limit=4G --dry-run

Obviously, always run --dry-run first.

Normally, you should commit changes to this file only if you apply a rector globally.

Since rector deprecated setlists, you should commit "One shot" sets with the last one applied, commented out, such as:
SymfonySetList::SYMFONY_64 //Applied from SYMFONY_60 to SYMFONY_64 2024-02-17

Recurrent (code quality sets) should be committed uncommented once applied.  Eventually, the CI will run this file and error out if there would be changes.

If you apply something with rector, you commits should be something like:

For global commits (in this case the changes to rector.php would be commited):

[REF] Rector:  Update rules to apply SymfonyLevelSetList::UP_TO_SYMFONY_54 globally

One shot, or partial application (in which case your changes in rector.php should not be commited, or commited commented-out):

[REF] Rector:  Apply ReturnTypeFromStrictNativeCallRector::class to path lib/core


Articles to read:
* https://getrector.com/blog/5-common-mistakes-in-rector-config-and-how-to-avoid-them (about running rector for upgrades vs on an ongoing basis)
* https://symfonycasts.com/screencast/symfony6-upgrade/rector (about upgrading symfony)
*/

use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\CodeQuality\Rector\ClassMethod\ReturnTypeFromStrictScalarReturnExprRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    //TO debug these paths, add --debug to your rector commands, and you will see every file processed.
    $rectorConfig->paths([
        __DIR__ . '/' . ADMIN_PATH,
        __DIR__ . '/' . TIKI_CONFIG_PATH,
        __DIR__ . '/' . DEPRECATED_DEVTOOLS_PATH,
        __DIR__ . '/' . INSTALLER_PATH,
        //__DIR__ . '/' . LANG_SRC_PATH,
        __DIR__ . '/' . LIB_PATH,
        __DIR__ . '/' . PHP_SOURCES_PATH,
        __DIR__ . '/' . LISTS_PATH,
        __DIR__ . '/' . MODULES_PATH,
        __DIR__ . '/' . PERMISSIONCHECK_PATH,
        __DIR__ . '/' . PROFILES_PATH,
        __DIR__ . '/' . BASE_THEMES_SRC_PATH,
    ]);
    $rectorConfig->skip([
        // __DIR__ . '/src/SingleFile.php',
        // __DIR__ . '/src/WholeDirectory',

        // or use fnmatch
        __DIR__ . '*/vendor/*',
    ]);

    /* Register sets of rules.

    They are not documented in a single place in rector doc unfortunately.
    Some can be found in
    https://github.com/rectorphp/rector/blob/main/packages/Set/ValueObject/SetList.php
    */
    $rectorConfig->sets([
        //Code quality sets we want to reach
        //SetList::TYPE_DECLARATION,

        //PHP version sets.  do NOT set higher than our lowest supported php version
        //LevelSetList::UP_TO_PHP_81,
        //PHPUnitSetList::PHPUNIT_100,
        //Symfony upgrades
        //Documentation: https://github.com/rectorphp/rector-symfony
        //https://github.com/rectorphp/rector-symfony/tree/main/config/sets/symfony
        //Applied globally SYMFONY_60 to SYMFONY_64 on 2023-05-05
        //SymfonySetList::SYMFONY_64
    ]);

    /* Register individial rules.

    Available rules for rector are found and documented here: https://getrector.com/documentation/rules-overview
    */
    $rectorConfig->rules([
        //Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector::class,
        //Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector::class,
        //ReturnTypeFromStrictNativeCallRector::class,
        //ReturnTypeFromStrictScalarReturnExprRector::class,
    ]);
};
