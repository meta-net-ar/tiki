<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TikiLib;

#[AsCommand(
    name: 'markdown:convert',
    description: 'Convert wiki pages between Tiki syntax and Markdown'
)]
class MarkdownConvertCommand extends Command
{
    protected function configure()
    {
        $this
            ->setHelp(
                tra('Use this command to convert Tiki wiki syntax stored in one or more pages to Markdown or vice-versa.')
            )
            ->addOption(
                'page',
                null,
                InputOption::VALUE_OPTIONAL,
                tra('The page name to check. Leave empty to attempt conversion of all available pages')
            )
            ->addOption(
                'markdown',
                null,
                InputOption::VALUE_NONE,
                tra('Convert to Markdown syntax')
            )
            ->addOption(
                'tiki',
                null,
                InputOption::VALUE_NONE,
                tra('Convert to Tiki wiki syntax')
            )
            ->addOption(
                'save',
                null,
                InputOption::VALUE_NONE,
                tra('Save converted content back to the database. Important: this will overwrite your existing content, proceed with caution!')
            )
            ->addOption(
                'contents',
                null,
                InputOption::VALUE_NONE,
                tra('Convert contents of specified pages to the target syntax')
            )
            ->addArgument(
                'exclude',
                InputArgument::IS_ARRAY,
                tra('Pages to exclude when converting all pages')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs, $page;

        $io = new SymfonyStyle($input, $output);

        if ($prefs['markdown_enabled'] !== 'y') {
            $io->error(tr('Markdown is not enabled in Editing settings.'));
            return Command::FAILURE;
        }

        if (! $input->getOption('markdown') && ! $input->getOption('tiki')) {
            $io->error(tr('You should specify either --markdown or --tiki option.'));
            return Command::FAILURE;
        }

        $tikilib = TikiLib::lib('tiki');

        if ($pageInfo = $input->getOption('page')) {
            $pageInfo = $tikilib->get_page_info($pageInfo) ?: null;
            if (empty($pageInfo)) {
                $io->error(tr('Page not found!'));
                return Command::FAILURE;
            }
            $pages = [$pageInfo];
        } else {
            $excluded = $input->getArgument('exclude');
            $allPages = $tikilib->list_pages(exclude_pages: $excluded, sort_mode: 'pageName_asc');
            $pages = $allPages['data'] ?: [];
        }

        if (! $pages) {
            $io->writeln(tr('There are no wiki pages to convert.'));
            return Command::FAILURE;
        }

        $syntax = $input->getOption('markdown') ? 'markdown' : 'tiki';
        $is_wysiwyg = false;
        foreach ($pages as $pageInfo) {
            $io->note(tr("Processing page %0", $pageInfo['pageName']));

            try {
                $page = $pageInfo['pageName'];
                $converted = TikiLib::lib('edit')->convertWikiSyntax($pageInfo['data'], $syntax);
            } catch (Exception $e) {
                $io->warning($e->getMessage() . ' ' . tr('Skipping...'));
                continue;
            }

            if ($input->getOption('save')) {
                $editor = '';
                if ($syntax == 'markdown') {
                    $editor = ' editor="wysiwyg"';
                    $is_wysiwyg = true;
                }
                $converted = '{syntax type="' . $syntax . '" ' . $editor . '} ' . $converted;
                $tikilib->update_page($pageInfo['pageName'], $converted, tra('automatic conversion'), 'admin', '127.0.0.1', null, 0, '', null, null, null, '', '', true);
            } else {
                $io->note("Converted:");
                $io->writeln($converted);
            }
        }
        if ($syntax == 'markdown' && $is_wysiwyg) {
            $features = [];
            if ($prefs['feature_wysiwyg'] !== 'y') {
                $features[] = 'feature_wysiwyg';
            }
            if ($prefs['wysiwyg_default'] !== 'y') {
                $features[] = 'wysiwyg_default';
            }
            if (count($features) > 0) {
                $msg = tr('Required features: %0 should be activate to edit pages with WYSIWYG Editor for markdown.', implode(', ', $features));
                $io->warning($msg);
            }
        }

        if ($input->getOption('save') && $input->getOption('contents')) {
            $dcslib = TikiLib::lib('dcs');
            if ($input->getOption('page')) {
                $contents = [];
                $idsArray = [];
                $contentPlugins = TikiLib::lib('parser')->find_plugins($pages[0]['data'], 'content');
                foreach ($contentPlugins as $plugin) {
                    $arguments = $plugin['arguments'];
                    if (isset($arguments['id'])) {
                        $id = $arguments['id'];
                    } elseif (isset($arguments['label'])) {
                        $contentInfo = $dcslib->get_content($arguments['label'], 'contentLabel');
                        $id = $contentInfo['contentId'];
                    }
                    if (isset($id) && ! in_array($id, $idsArray)) {
                        $list = $dcslib->list_programmed_content($id);
                        $contents = array_merge($contents, array_values($list['data']));
                        $idsArray[] = $id;
                    }
                }
            } else {
                $list = $dcslib->listAllProgrammedContent();
                $contents = $list['data'];
            }
            foreach ($contents as $content) {
                TikiLib::lib('edit')->convertContentPlugins($content, $syntax);
            }
        }

        return Command::SUCCESS;
    }
}
