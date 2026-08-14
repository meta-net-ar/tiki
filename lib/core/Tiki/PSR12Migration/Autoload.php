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
        'ObjectList' => 'Tiki\\ObjectRenderer\\ObjectList',
        'ObjectRenderer' => 'Tiki\\ObjectRenderer\\AbstractObjectRenderer',
        'ObjectRenderer_TrackerItem' => 'Tiki\\ObjectRenderer\\TrackerItem',
        'ObjectRenderer_Wiki' => 'Tiki\\ObjectRenderer\\Wiki',
        'ObjectRenderer_MultilingualWiki' => 'Tiki\\ObjectRenderer\\MultilingualWiki',
        'ApiTokenException' => 'Tiki\\Lib\\Auth\\ApiTokenException',
        'ApiToken' => 'Tiki\\Lib\\Auth\\ApiToken',
        'TikiLdapLib' => 'Tiki\\Lib\\Auth\\LdapLib',
        'AuthTokens' => 'Tiki\\Lib\\Auth\\Tokens',
        'TikiPhpBBLib' => 'Tiki\\Lib\\Auth\\PhpBBLib',
        'Webhook' => 'Tiki\\Lib\\Auth\\Webhook',
        'ImporterParserException' => 'Tiki\\Lib\\Importer\\ParserException',
        'TikiImporter_Blog_Wordpress' => 'Tiki\\Lib\\Importer\\BlogWordpress',
        'TikiImporter_Wiki' => 'Tiki\\Lib\\Importer\\Wiki',
        'TikiImporter_Wiki_Mediawiki' => 'Tiki\\Lib\\Importer\\WikiMediawiki',
        'TikiImporter_Blog' => 'Tiki\\Lib\\Importer\\Blog',
        'TikiImporter' => 'Tiki\\Lib\\Importer\\Base',
        'TikiSheetDataFormat' => 'Tiki\\Lib\\Sheet\\DataFormat',
        'TikiSheet' => 'Tiki\\Lib\\Sheet\\Sheet',
        'TikiSheetDataHandler' => 'Tiki\\Lib\\Sheet\\DataHandler',
        'TikiSheetCSVHandler' => 'Tiki\\Lib\\Sheet\\CSVHandler',
        'TikiSheetTrackerHandler' => 'Tiki\\Lib\\Sheet\\TrackerHandler',
        'TikiSheetSimpleArrayHandler' => 'Tiki\\Lib\\Sheet\\SimpleArrayHandler',
        'TikiSheetCSVExcelHandler' => 'Tiki\\Lib\\Sheet\\CSVExcelHandler',
        'TikiSheetDatabaseHandler' => 'Tiki\\Lib\\Sheet\\DatabaseHandler',
        'TikiSheetOpenOfficeHandler' => 'Tiki\\Lib\\Sheet\\OpenOfficeHandler',
        'TikiSheetWikiTableHandler' => 'Tiki\\Lib\\Sheet\\WikiTableHandler',
        'TikiSheetOutputHandler' => 'Tiki\\Lib\\Sheet\\OutputHandler',
        'TikiSheetLabeledOutputHandler' => 'Tiki\\Lib\\Sheet\\LabeledOutputHandler',
        'TikiSheetHTMLTableHandler' => 'Tiki\\Lib\\Sheet\\HTMLTableHandler',
        'QuizLib' => 'Tiki\\Lib\\Quizzes\\QuizLib',
        'Quiz' => 'Tiki\\Lib\\Quizzes\\Quiz',
        'HW_QuizQuestionYesNo' => 'Tiki\\Lib\\Quizzes\\HWQuizQuestionYesNo',
        'HW_QuizQuestionMultipleChoice' => 'Tiki\\Lib\\Quizzes\\HWQuizQuestionMultipleChoice',
        'HW_QuizQuestion' => 'Tiki\\Lib\\Quizzes\\HWQuizQuestion',
        'OIntegrate_Converter' => 'Tiki\\Lib\\OIntegrate\\ConverterInterface',
        'OIntegrate_Engine' => 'Tiki\\Lib\\OIntegrate\\EngineInterface',
        'OIntegrate' => 'Tiki\\Lib\\OIntegrate\\OIntegrate',
        'OIntegrate_Response' => 'Tiki\\Lib\\OIntegrate\\Response',
        'OIntegrate_Engine_JavaScript' => 'Tiki\\Lib\\OIntegrate\\Engine\\JavaScript',
        'OIntegrate_Engine_Smarty' => 'Tiki\\Lib\\OIntegrate\\Engine\\Smarty',
        'OIntegrate_Engine_Index' => 'Tiki\\Lib\\OIntegrate\\Engine\\Index',
        'OIntegrate_Converter_Direct' => 'Tiki\\Lib\\OIntegrate\\Converter\\Direct',
        'OIntegrate_Converter_EncodeHtml' => 'Tiki\\Lib\\OIntegrate\\Converter\\EncodeHtml',
        'OIntegrate_Converter_HtmlToTiki' => 'Tiki\\Lib\\OIntegrate\\Converter\\HtmlToTiki',
        'OIntegrate_Converter_TikiToHtml' => 'Tiki\\Lib\\OIntegrate\\Converter\\TikiToHtml',
        'OIntegrate_Converter_Indexer' => 'Tiki\\Lib\\OIntegrate\\Converter\\Indexer',
        'FileIsNotSafeException' => 'Tiki\\Lib\\Filegals\\FileIsNotSafeException',
        'FilegalBatchLib' => 'Tiki\\Lib\\Filegals\\BatchLib',
        'FilegalCopyLib' => 'Tiki\\Lib\\Filegals\\CopyLib',
        'FilegalLib' => 'Tiki\\Lib\\Filegals\\FileGalLib',
        'H5PLib' => 'Tiki\\Lib\\Filegals\\H5PLib',
        'ScormLib' => 'Tiki\\Lib\\Filegals\\ScormLib',
        'VimeoLib' => 'Tiki\\Lib\\Filegals\\VimeoLib',
        'Iconset' => 'Tiki\\Lib\\Theme\\Iconset',
        'IconsetLib' => 'Tiki\\Lib\\Theme\\IconsetLib',
        'ThemeLib' => 'Tiki\\Lib\\Theme\\ThemeLib',
        'ThemeControlLib' => 'Tiki\\Lib\\Theme\\ThemeControlLib',
        'Text_Diff' => 'Tiki\\Lib\\Diff\\TextDiff',
        'Text_MappedDiff' => 'Tiki\\Lib\\Diff\\MappedDiff',
        'Text_Diff_Engine_xdiff' => 'Tiki\\Lib\\Diff\\EngineXdiff',
        'Text_Diff_Engine_native' => 'Tiki\\Lib\\Diff\\EngineNative',
        'Tiki_Text_Diff_Renderer' => 'Tiki\\Lib\\Diff\\Renderer\\TikiRenderer',
        'Text_Diff_Op' => 'Tiki\\Lib\\Diff\\Op\\Base',
        'Text_Diff_Op_copy' => 'Tiki\\Lib\\Diff\\Op\\Copy',
        'Text_Diff_Op_delete' => 'Tiki\\Lib\\Diff\\Op\\Delete',
        'Text_Diff_Op_add' => 'Tiki\\Lib\\Diff\\Op\\Add',
        'Text_Diff_Op_change' => 'Tiki\\Lib\\Diff\\Op\\Change',
        'Text_Diff_Renderer' => 'Tiki\\Lib\\Diff\\Renderer\\Base',
        'Text_Diff_Renderer_unified' => 'Tiki\\Lib\\Diff\\Renderer\\Unified',
        'Text_Diff_Renderer_sidebyside' => 'Tiki\\Lib\\Diff\\Renderer\\SideBySide',
        'Text_Diff_Renderer_inline' => 'Tiki\\Lib\\Diff\\Renderer\\Inline',
        'Text_Diff_Renderer_htmldiff' => 'Tiki\\Lib\\Diff\\Renderer\\HtmlDiff',
        'Text_Diff_Renderer_character' => 'Tiki\\Lib\\Diff\\Renderer\\Character',
        'Text_Diff_Renderer_character_inline' => 'Tiki\\Lib\\Diff\\Renderer\\CharacterInline',
        'Text_Diff_Renderer_bytes' => 'Tiki\\Lib\\Diff\\Renderer\\Bytes',
        'RegistrationError' => 'Tiki\\Lib\\Registration\\Error',
        'RegistrationLib' => 'Tiki\\Lib\\Registration\\RegistrationLib',
        'TikiRegistrationFields' => 'Tiki\\Lib\\Registration\\Fields',
        '_WikiDiffEngine' => 'Tiki\\Lib\\WikiDiff\\Engine',
        'WikiDiff' => 'Tiki\\Lib\\WikiDiff\\Base',
        'WikiDiffFormatter' => 'Tiki\\Lib\\WikiDiff\\Formatter',
        'WikiUnifiedDiffFormatter' => 'Tiki\\Lib\\WikiDiff\\UnifiedFormatter',
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
        'Tiki_Security_Policy' => 'Tiki\\Smarty\\SecurityPolicy',
        'SmartyTikiErrorHandler' => 'Tiki\\Smarty\\SmartyTikiErrorHandler',
        'ShippingProvider' => 'Tiki\\Lib\\Shipping\\Provider\\ShippingProviderInterface',
        'ShippingProvider_Fedex' => 'Tiki\\Lib\\Shipping\\Provider\\Fedex',
        'ShippingProvider_Ups' => 'Tiki\\Lib\\Shipping\\Provider\\Ups',
        'CustomShippingProvider' => 'Tiki\\Lib\\Shipping\\Provider\\AbstractCustomShippingProvider',
        'CustomShippingProvider_Example' => 'Tiki\\Lib\\Shipping\\Provider\\Custom\\Example',
        'ShippingLib' => 'Tiki\\Lib\\Shipping\\ShippingLib',
        'StyleType' => 'SmartyTiki\\Utils\\StyleType',
        'SmartyKint' => 'SmartyTiki\\Utils\\SmartyKint',
        'TikiAcceptanceTestDBRestorer' => 'Tiki\\Lib\\Test\\AcceptanceTests\\AbstractDBRestorer',
        'TikiAcceptanceTestDBRestorerSQLDumps' => 'Tiki\\Lib\\Test\\AcceptanceTests\\DBRestorerSQLDumps',
        'TikiAcceptanceTestDBRestorerBinaryDumps' => 'Tiki\\Lib\\Test\\AcceptanceTests\\DBRestorerBinaryDumps',
        'Memcachelib' => 'Tiki\\Cache\\MemcacheLib',
        'Cachelib' => 'Tiki\\Cache\\CacheLib',
        'CacheLibFileSystem' => 'Tiki\\Cache\\FileSystem',
        'CacheLibMemcache' => 'Tiki\\Cache\\Memcache',
        'CacheLibNoCache' => 'Tiki\\Cache\\NoCache',
        'CacheLibRedis' => 'Tiki\\Cache\\Redis',
        'page_Parser' => 'TIki\\Lib\\Wiki\\PageParser',
        'XmlLib' => 'TIki\\Lib\\Wiki\\XmlLib',
        'Tiki_Profile_SymbolLoader' => 'Tiki\\Profile\\SymbolLoader',
        'Tiki_Profile_SymbolLoader_Store' => 'Tiki\\Profile\\SymbolLoaderStore',
        'TikiDate' => 'Tiki\\Lib\\TikiDate',
        'Date_Calc' => 'Tiki\\Lib\\DateCalc',
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
