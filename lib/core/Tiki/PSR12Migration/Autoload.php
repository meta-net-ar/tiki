<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
declare(strict_types=1);

namespace Tiki\PSR12Migration;

use Composer\Autoload\ClassLoader;

class Autoload
{
    /**
     * @var string[] Map of classes to register / handle
     */
    protected static $aliasMap = [
        'TikiInit' => 'Tiki\\TikiInit',
        'Patch' => 'Tiki\\Installer\\Patch',
        'ProgressBar' => 'Tiki\\Installer\\ProgressBar',
        'Installer' => 'Tiki\\Installer\\Installer',
        'InstallerDatabaseErrorHandler' => 'Tiki\\Installer\\InstallerDatabaseErrorHandler',
        'LogsLib' => 'Tiki\\Lib\\Logs\\LogsLib',
        'LogsQueryLib' => 'Tiki\\Lib\\Logs\\LogsQueryLib',
        'FixtureTable' => 'Tiki\\WikiPlugin\\Fixture\\Table',
        'FixtureMockTrackerField' => 'Tiki\\WikiPlugin\\Fixture\\MockTrackerField',
        'WikipluginDBReport' => 'Tiki\\WikiPlugin\\DBReport\\Base',
        'WikipluginDBReportToken' => 'Tiki\\WikiPlugin\\DBReport\\Token',
        'WikipluginDBReportCell' => 'Tiki\\WikiPlugin\\DBReport\\Cell',
        'WikipluginDBReportContent' => 'Tiki\\WikiPlugin\\DBReport\\Content',
        'WikipluginDBReportFail' => 'Tiki\\WikiPlugin\\DBReport\\Fail',
        'WikipluginDBReportField' => 'Tiki\\WikiPlugin\\DBReport\\Field',
        'WikipluginDBReportParameter' => 'Tiki\\WikiPlugin\\DBReport\\Parameter',
        'WikipluginDBReportGroup' => 'Tiki\\WikiPlugin\\DBReport\\Group',
        'WikipluginDBReportTable' => 'Tiki\\WikiPlugin\\DBReport\\Table',
        'WikipluginDBReportLine' => 'Tiki\\WikiPlugin\\DBReport\\Line',
        'WikipluginDBReportLink' => 'Tiki\\WikiPlugin\\DBReport\\Link',
        'WikipluginDBReportStyle' => 'Tiki\\WikiPlugin\\DBReport\\Style',
        'WikipluginDBReportText' => 'Tiki\\WikiPlugin\\DBReport\\Text',
        'WikipluginDBReportString' => 'Tiki\\WikiPlugin\\DBReport\\Strings',
        'Tiki_WebServices' => 'Tiki\\Webservices\\Base',
        'Tiki_ComplexType' => 'Tiki\\Webservices\\ComplexType',
        'PieChartGraphic' => 'Tiki\\Lib\\GraphEngine\\PieChartGraphic',
        'PDFLib_GRenderer' => 'Tiki\\Lib\\GraphEngine\\PDFLibGRenderer',
        'PS_GRenderer' => 'Tiki\\Lib\\GraphEngine\\PSGRenderer',
        'GD_GRenderer' => 'Tiki\\Lib\\GraphEngine\\GDGRenderer',
        'MultilineGraphic' => 'Tiki\\Lib\\GraphEngine\\MultilineGraphic',
        'BarBasedGraphic' => 'Tiki\\Lib\\GraphEngine\\BarBasedGraphic',
        'MultibarGraphic' => 'Tiki\\Lib\\GraphEngine\\MultibarGraphic',
        'BarStackGraphic' => 'Tiki\\Lib\\GraphEngine\\BarStackGraphic',
        'StaticGridScale' => 'Tiki\\Lib\\GraphEngine\\StaticGridScale',
        'LinearGridScale' => 'Tiki\\Lib\\GraphEngine\\LinearGridScale',
        'GridScale' => 'Tiki\\Lib\\GraphEngine\\GridScale',
        'Fake_GRenderer' => 'Tiki\\Lib\\GraphEngine\\FakeGRenderer',
        'DataHandler' => 'Tiki\\Lib\\GraphEngine\\DataHandler',
        'Graphic' => 'Tiki\\Lib\\GraphEngine\\Graphic',
        'GridBasedGraphic' => 'Tiki\\Lib\\GraphEngine\\GridBasedGraphic',
        'GRenderer' => 'Tiki\\Lib\\GraphEngine\\GRenderer',
        'TikiDb_Pdo' => 'Tiki\\TikiDb\\PdoDb',
        'TikiDb_Pdo_Result' => 'Tiki\\TikiDb\\PdoResult',
        'PluginsLibUtil' => 'Tiki\\Lib\\Wiki\\PluginsLibUtil',
        'PluginsLib' => 'Tiki\\Lib\\Wiki\\PluginsLib',
        'Tiki_Webservice_Template' => 'Tiki\\Lib\\TikiWebServiceTemplate',
        'WikiParser_PluginMatcher_Match' => 'Tiki\\WikiParser\\PluginMatcherMatch',
        'WikiLibOutput' => 'Tiki\\Lib\\Wiki\\WikiLibOutput',
        'convertToTiki9' => 'Tiki\\Lib\\Wiki\\ConvertToTiki9',
        'Search_Formatter_Transform_DynamicLoaderWrapper' => 'Tiki\\Search\\Formatter\\Transform\\DynamicLoaderWrapper',
        'Search_Formatter_Transform_DynamicLoader' => 'Tiki\\Search\\Formatter\\Transform\\DynamicLoader',
        'Document' => 'Tiki\\Lib\\Wiki\\Document',
    ];

    /**
     * @var ClassLoader pointer to composer autoloader
     */
    protected static $composerAutoloader = null;

    /**
     * Entry point to the autoload
     * Will try to use class_alias to map the class requested to the right class.
     *
     * @param string $class the name of the class to be autoloaded
     */
    public static function autoloadAlias($class): ?bool
    {
        // check if we can handle this class
        if (! isset(static::$aliasMap[$class])) {
            return null;
        }

        $realClass = static::$aliasMap[$class];

        $classExists = class_exists($realClass);

        // check if we need to autoload the real class, class_alias expect the real class to be autoload already
        if (! $classExists) {
            static::getComposerAutoloader()->loadClass($realClass);
            $classExists = class_exists($realClass);
        }

        if ($classExists) {
            Report::classAlias($class, $realClass);
            class_alias($realClass, $class);
            return true;
        }

        return null;
    }

    protected static function getComposerAutoloader(): ?ClassLoader
    {
        if (static::$composerAutoloader !== null) {
            return static::$composerAutoloader;
        }

        static::$composerAutoloader = require TIKI_PATH . '/vendor_bundled/vendor/autoload.php';
        return static::$composerAutoloader;
    }
}
