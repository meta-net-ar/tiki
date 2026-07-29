<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TikiLib;
use WikiParser_PluginArgumentParser;
use WikiParser_PluginMatcher;

#[AsCommand(
    name: 'list:execute',
    description: 'Performs Plugin ListExecute command on a particular page'
)]
class ListExecuteCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'page',
                InputArgument::REQUIRED,
                'Page name where Plugin ListExecute is setup'
            )
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'Name of the action to be executed as defined on the target page'
            )
            ->addArgument(
                'input',
                InputArgument::OPTIONAL,
                'If action takes a variable input parameter, specify it here'
            )
            ->addOption(
                'request',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify query string defining the request variables to be used on the wiki page. E.g. "days=30&alert=2"'
            )
            ->addOption(
                'objects',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Execute the action only on the given objects instead of all the ones listed. Format: object_type:object_id'
                    . ' (you can use multiple times, once for each object). E.g. --objects="trackeritem:42" --objects="wiki page:HomePage"'
            )
            ->addOption(
                'results-page',
                null,
                InputOption::VALUE_REQUIRED,
                'Execute the action only on the given page of results, 1 being the first one, using the pagination size'
                    . ' set on the target page. When omitted, the action is executed on every object listed.'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;
        $page = $input->getArgument('page');
        $action = $input->getArgument('action');
        $io = new SymfonyStyle($input, $output);

        $tikilib = TikiLib::lib('tiki');
        if (empty($prefs['fallbackBaseUrl'])) {
            $io->warning(tr("Some commands may need to determine the URL of the website and will not be able to do so reliably because fallbackBaseUrl is not set in the admin."));
        }
        if (! $pageInfo = $tikilib->get_page_info($page)) {
            $io->error(tr("Page %0 not found.", $page));
            return Command::FAILURE;
        }

        $objects = $input->getOption('objects');
        $invalidObjects = array_filter($objects, fn($identifier) => ! $this->isValidObjectIdentifier($identifier));

        if ($invalidObjects) {
            $io->error(tr("Invalid object identifier(s): %0. Expected format is object_type:object_id.", implode(', ', $invalidObjects)));
            return Command::FAILURE;
        }

        $resultsPage = $input->getOption('results-page');

        if (! is_null($resultsPage) && ! (ctype_digit((string) $resultsPage) && $resultsPage > 0)) {
            $io->error(tr("Invalid results page: %0. Expected the number of a page of results, 1 being the first one.", $resultsPage));
            return Command::FAILURE;
        }

        if ($objects && ! is_null($resultsPage)) {
            $io->error(tr("The objects and results-page options cannot be combined, the given objects are already a selection."));
            return Command::FAILURE;
        }

        $selectedObjects = $objects ?: ['ALL'];

        if ($request = $input->getOption('request')) {
            parse_str($request, $_POST);
        }

        $_POST['list_action'] = $action;
        $_POST['list_results_page'] = $resultsPage;
        for ($i = 1; $i <= 10; $i++) {
            $_POST['objects' . $i] = $selectedObjects;
        }
        $_POST['list_input'] = $input->getArgument('input');

        $_GET = $_REQUEST = $_POST; // wiki_argvariable needs this

        $matches = WikiParser_PluginMatcher::match($pageInfo['data']);

        // Let's check if the plugin is approved
        if (! empty($matches)) {
            $argumentParser = new WikiParser_PluginArgumentParser();
            $parserLib = TikiLib::lib('parser');

            foreach ($matches as $match) {
                if ($match->getName() !== 'listexecute') {
                    continue;
                }

                $listExecuteMatches = WikiParser_PluginMatcher::match($match->getBody());

                foreach ($listExecuteMatches as $listExecuteMatch) {
                    $arguments = $argumentParser->parse($listExecuteMatch->getArguments());

                    // If the action of the list execute is not the requested one, move on
                    if ($listExecuteMatch->getName() !== 'action' || ! isset($arguments['name']) || $arguments['name'] != $action) {
                        continue;
                    }

                    $status = $parserLib->plugin_can_execute($match->getName(), $match->getBody(), $argumentParser->parse($match->getArguments()));

                    if ($status !== true) {
                        $outputMessage = $status == 'rejected' ?
                            tr("Action %0 failed on page %1. ListExecute plugin was rejected.", $action, $page) :
                            tr("Action %0 failed on page %1. ListExecute plugin is pending for approval.", $action, $page);

                        $io->error($outputMessage);
                        return Command::FAILURE;
                    }
                }
            }
        }

        TikiLib::lib('parser')->parse_data($pageInfo['data']);

        if ($objects) {
            $message = tr("Action %0 executed on page %1 for %2.", $action, $page, implode(', ', $objects));
        } elseif ($resultsPage) {
            $message = tr("Action %0 executed on page %1 for results page %2.", $action, $page, $resultsPage);
        } else {
            $message = tr("Action %0 executed on page %1.", $action, $page);
        }

        $io->success($message);
        return Command::SUCCESS;
    }

    private function isValidObjectIdentifier(string $identifier): bool
    {
        if (! str_contains($identifier, ':')) {
            return false;
        }

        [$type, $id] = explode(':', $identifier, 2);

        return $type !== '' && $id !== '';
    }
}
