<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Tiki\Lib\Diff\DiffUtils;
use TikiLib;

#[AsCommand(
    name: 'index:compare-engines',
    description: 'Compare search engine results in wikiplugins'
)]
class IndexCompareEnginesCommand extends Command
{
    /**
     * Add or remove plugins to this array to be considered when checking the results
     */
    private const PLUGINS_TO_CHECK = ['list', 'listexecute', 'pivottable'];

    protected function configure()
    {
        $this
            ->setHelp(
                'Check unified search plugin results inside wiki pages by comparing different search index results. Only plugins that use the unified search results are verified.'
            )
            ->addOption(
                'page',
                null,
                InputOption::VALUE_REQUIRED,
                'The page name to check',
            )->addOption(
                'engine',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Search engines to compare: specify exactly two engines, should be one of: elastic, mysql or manticore'
            )
            ->addOption(
                'html',
                null,
                InputOption::VALUE_NONE,
                'Export the differences found in a well formatted HTML file'
            )
            ->addOption(
                'reindex',
                null,
                InputOption::VALUE_NONE,
                'Reindex search engines before running this script'
            )
            ->addOption(
                'log',
                null,
                InputOption::VALUE_NONE,
                'Generate a log of the indexed documents, useful to track down failures or memory issues'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs, $tikidomainslash;

        ini_set('max_execution_time', 0);

        $orig_prefs = $prefs;

        $io = new SymfonyStyle($input, $output);

        $engines = $input->getOption('engine');
        if (count($engines) < 2) {
            $io->error(
                'To execute this script you need to specify at least two engines to compare.'
            );
            return Command::FAILURE;
        }

        if (count($engines) == 3 && $input->getOption('html')) {
            $io->error(
                'Comparing all three engines works in text-mode only, you cannot specify the --html option.'
            );
            return Command::FAILURE;
        }

        if ($input->getOption('log')) {
            $log = 2;
        } else {
            $log = 0;
        }

        $tikiLib = TikiLib::lib('tiki');
        $unifiedSearchLib = TikiLib::lib('unifiedsearch');

        if ($page = $input->getOption('page')) {
            $pageInfo = $tikiLib->get_page_info($page) ?: null;
            $pages = [$pageInfo];
        } else {
            $allPages = $tikiLib->list_pages();
            $pages = $allPages['data'] ?: [];
        }

        if (! $pages) {
            $io->writeln('There are no wiki pages to check.');
            return Command::SUCCESS;
        }

        $reindex = $input->getOption('reindex');

        foreach ($engines as $engine) {
            switch ($engine) {
                case 'elastic':
                    $prefs['unified_engine'] = 'elastic';
                    $elasticStatus = $unifiedSearchLib->checkElasticsearch();
                    if ($elasticStatus['error']) {
                        $io->error('Elasticsearch Error' . PHP_EOL . $elasticStatus['feedback']);
                        exit(1);
                    }
                    if (! $reindex && ! $unifiedSearchLib->getIndex()->exists()) {
                        $io->error('Elasticsearch index not found. Use --reindex to rebuild the index.');
                        exit(1);
                    }
                    break;
                case 'mysql':
                    $prefs['unified_engine'] = 'mysql';
                    $mysqlStatus = $unifiedSearchLib->checkMySql();
                    if ($mysqlStatus['error'] && ! $reindex) {
                        $io->error($mysqlStatus['feedback']);
                        exit(1);
                    }
                    break;
                case 'manticore':
                    $prefs['unified_engine'] = 'manticore';
                    $manticoreStatus = $unifiedSearchLib->checkManticore();
                    if ($manticoreStatus['error']) {
                        $io->error('Manticore Error' . PHP_EOL . $manticoreStatus['feedback']);
                        exit(1);
                    }
                    if (! $reindex && ! $unifiedSearchLib->getIndex()->exists()) {
                        $io->error('Manticore index not found. Use --reindex to rebuild the index.');
                        exit(1);
                    }
                    break;
            }
        }

        $prefs = $orig_prefs;

        $indices = [];

        if ($input->getOption('reindex')) {
            $io->writeln('Rebuilding index, please wait...');
            foreach ($engines as $engine) {
                $io->writeln('Rebuilding ' . $engine);
                // change prefs
                $prefs['unified_engine'] = $engine;
                $indices[$engine] = $unifiedSearchLib->getIndexLocation('ondemand');
                // destroy existing index
                $unifiedSearchLib->invalidateIndicesCache();
                $index = $unifiedSearchLib->getIndex('ondemand');
                $index->destroy();
                // rebuild a new one
                $unifiedSearchLib->invalidateIndicesCache();
                $index = $unifiedSearchLib->getIndex('ondemand');
                $indexer = $unifiedSearchLib->buildIndexer($index, $log);
                $indexer->rebuild();
                $index->endUpdate();
                unset($indexer);
                unset($index);
            }
            $io->writeln('Index rebuild finished.');
            $io->newLine(2);
        } else {
            foreach ($engines as $engine) {
                $prefs['unified_engine'] = $engine;
                $indices[$engine] = $unifiedSearchLib->getIndexLocation('ondemand');
            }
        }

        $prefs = $orig_prefs;

        $parserLib = TikiLib::lib('parser');
        $differentOutputs = [];

        foreach ($pages as $page) {
            $plugins = \WikiParser_PluginMatcher::match($page['data']);

            if (! $plugins->count()) {
                continue;
            }

            foreach ($plugins as $plugin) {
                $rawPlugin = strval($plugin);

                if (! in_array($plugin->getName(), static::PLUGINS_TO_CHECK)) {
                    continue;
                }

                $pluginName = $plugin->getName();

                $output = [];
                foreach ($engines as $engine) {
                    $prefs['unified_engine'] = $engine;
                    if ($indices) {
                        $prefs['unified_' . $engine . '_index_current'] = $indices[$engine];
                    }
                    $unifiedSearchLib->invalidateIndicesCache();
                    global $indexComparisonInProgess;
                    $indexComparisonInProgess = true;

                    \Search_Formatter_Factory::$counter = 0; // Reset counter index
                    $output[$engine] = @$parserLib->parse_data($rawPlugin);

                    // Remove static $id usage to avoid differences used by pivottable plugin
                    if ($pluginName == 'pivottable') {
                        $regex = '/pivottable.?\d+/';
                        $output[$engine] = preg_replace($regex, 'pivottable', $output[$engine]);
                    }

                    // Remove static $id usage to avoid differences used by listexecute plugin
                    if ($pluginName == 'listexecute') {
                        $regex = '/listexecute.?\d+/';
                        $output[$engine] = preg_replace($regex, 'listexecute', $output[$engine]);

                        $regex = '/listexecute-download-\d+/';
                        $output[$engine] = preg_replace($regex, 'listexecute-download', $output[$engine]);

                        $regex = '/objects\d+\[\]/';
                        $output[$engine] = preg_replace($regex, 'objects[]', $output[$engine]);
                    }

                    // Remove static $id usage to avoid differences used by list plugin
                    if ($pluginName == 'list') {
                        $regex = '/list.?(\d+)/';

                        $output[$engine] = preg_replace($regex, 'list', $output[$engine]);
                    }

                    // Remove currency output id/class names
                    $regex = '/currency_output_[a-z0-9]+/';
                    $output[$engine] = preg_replace($regex, 'currency_output', $output[$engine]);

                    // Remove object selector ids
                    $regex = '/object_selector(_multi)?_\d+/';
                    $output[$engine] = preg_replace($regex, 'object_selector', $output[$engine]);

                    // Remove list filter ids
                    $regex = '/list_filter\d+/';
                    $output[$engine] = preg_replace($regex, 'list_filter', $output[$engine]);

                    // remove calendar selector uids
                    $regex = '/uiCal_[a-z0-9]+/';
                    $output[$engine] = preg_replace($regex, 'uiCal', $output[$engine]);
                }

                if ($output[$engines[0]] !== $output[$engines[1]]) {
                    $differentOutputs[] = [
                        'page'     => $page['pageName'],
                        'plugin'   => $rawPlugin,
                        'output'   => $output,
                    ];
                }
            }
        }

        $prefs = $orig_prefs;

        if (empty($differentOutputs)) {
            $io->writeln('Plugin outputs using selected engines are identical.');
            return Command::SUCCESS;
        }

        if (count($engines) == 3) {
            foreach ($engines as $i => $engine) {
                $io->writeln(($i + 1) . ': ' . $engine);
            }
            foreach ($differentOutputs as $output) {
                $io->section('Tiki Page - ' . $output['page']);
                $io->writeln('Plugin Declaration:');
                $io->writeln($output['plugin']);
                $io->newLine(2);

                $filenames = [];
                foreach ($engines as $engine) {
                    $fname = tempnam(TIKI_PATH . 'temp', 'indexcompare');
                    file_put_contents($fname, $output['output'][$engine]);
                    $filenames[] = $fname;
                }

                $io->writeln(shell_exec("diff3 $filenames[0] $filenames[1] $filenames[2]"));

                foreach ($filenames as $fname) {
                    unlink($fname);
                }
            }
        } elseif ($input->getOption('html')) {
            include_once 'lib/wiki-plugins/wikiplugin_code.php';

            $htmlOutput = "";

            foreach ($differentOutputs as $output) {
                $pageName = $output['page'];
                $pluginCode = TikiLib::lib('parser')->invokePlugin('code', $output['plugin'], ['colors' => 'tiki']);
                $diff = DiffUtils::diff2($output['output'][$engines[0]], $output['output'][$engines[1]]);
                $htmlOutput .= <<<HTML
<table class='table table-striped' style='margin-top: 40px'>
    <tbody>
        <tr>
            <td>Wiki page</td>
            <td>{$pageName}</td>
        </tr>
        <tr>
            <td>Plugin Declaration</td>
            <td>{$pluginCode}</td>
        </tr>
        <tr>
            <td>Output diff ({$engines[0]}/{$engines[1]})</td>
            <td><table style='width:100%'>{$diff}</table></td>
        </tr>
    </tbody>
</table>
HTML;
            }

            // Inject the CSS, so the output file can be used as standalone HTML file
            $tikiBaseCSS = file_get_contents('themes/base_files/css/tiki_base.css');
            $defaultCSS = file_get_contents('themes/default/css/default.css');
            $htmlOutput = <<<HTML
<!DOCTYPE html>
    <html>
        <head>
            <style>{$tikiBaseCSS}</style>
            <style>{$defaultCSS}</style>
        </head>
        <body style='margin-left: 20%; margin-right: 20%; margin-top: 20px'>
            <h4>Check unified search script results</h4>
            {$htmlOutput}
        </body>
    </html>
HTML;

            $filename = sprintf('index-compare-engines_results_%s.html', date('YmdHi'));

            $finalPath = 'temp/' . $tikidomainslash . $filename;

            if (file_exists($finalPath)) {
                unlink($finalPath);
            }

            file_put_contents($finalPath, $htmlOutput);

            $io->writeln("Plugin differences found. Please check the file '$finalPath' for more details.");
        } else {
            $builder = new UnifiedDiffOutputBuilder("--- {$engines[0]}\n+++ {$engines[1]}\n");
            $differ = new Differ($builder);

            foreach ($differentOutputs as $output) {
                $io->section('Tiki Page - ' . $output['page']);
                $io->writeln('Plugin Declaration:');
                $io->writeln($output['plugin']);
                $io->newLine(2);

                $diff = $differ->diff($output['output'][$engines[0]], $output['output'][$engines[1]]);
                $io->writeln($diff);
            }
        }

        $prefs = $orig_prefs;

        return Command::FAILURE;
    }
}
