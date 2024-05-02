<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'objects:export-jsonl',
    description: 'Export objects in JSONL format, typically to ingest in a machine learning system'
)]
class ObjectsExportCommand extends Command
{
    protected function configure()
    {
        $this
        ->addOption(
            'file',
            null,
            InputOption::VALUE_REQUIRED,
            "The relative or absolute path of the JSONL file to export.  Default is objects.jsonl",
            'objects.jsonl'
        )
        ->addOption(
            'format',
            null,
            InputOption::VALUE_REQUIRED,
            "The format to export into.  Supported formats:
                    jsonl: JSONL format, typically to ingest in a machine learning system"
        )
        ->addOption(
            'filterCategories',
            null,
            InputOption::VALUE_REQUIRED,
            "Comma-separated array of category ids the exported objects must be (recursively) sorted into.",
            ''
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $objectlib = \TikiLib::lib('object');
        $supportedTypes = ['wiki page', 'trackeritem'];
        $categlib = \TikiLib::lib('categ');

        //$listCategories = $categlib->getCategories();

        /*foreach ($listCategories as $categoryRow) {
        $output->writeln(print_r($categoryRow, true));
        }*/

        $supportedFormats = ['jsonl'];
        $format = $input->getOption('format');
        if (array_search($format, $supportedFormats) === false) {
            if (empty($format)) {
                    $output->writeln("<error>Missing --format argument</error>");
            } else {
                $output->writeln("<error>Unsupported --format argument: $format </error>");
            }
            return Command::INVALID;
        }
        switch ($format) {
            case 'jsonl':
                $output->writeln('<comment>Exporting in JSONL format</comment>');
                break;
            default:
        }

        $categoryIds = explode(',', $input->getOption('filterCategories'));
        //Filter on categories
        //$categoryIds = [11, 31, 28, 15, 30];
        //$categoryIds = [22];

        $objects = $categlib->getCategoryObjectsRawRows($categoryIds, deep: true);
        $candidateObjectCount = count($objects);
        $exportedObjectCount = 0;
        $output->writeln("$candidateObjectCount objects to be exported");
        $wikiSource = new \Search_ContentSource_WikiSource();
        $trackerItemSource = new \Search_ContentSource_TrackerItemSource();
        $factory = new \Search_Type_Factory_Direct();

        //$factory = new \Search\Manticore\TypeFactory();

        $fileName = $input->getOption('file');

        if ($outputFile = fopen($fileName, "w")) {
            $output->writeln("Starting export");
        } else {
            $output->writeln("<error>Cannot open file ($fileName)</error>");
            return Command::FAILURE;
        }
        $exportedTypeCounts = [];
        $skippedTypeCounts = [];
        $exportedObjectsIds = [];
        $progressBar = new ProgressBar($output, count($objects));
        $progressBar->start();
        foreach ($objects as $objectRow) {
            $type = $objectRow['type'];
            $id = $objectRow['itemId'];
            if (! isset($exportedObjectsIds[$id])) {
                $exportedObjectsIds[$id] = 1;
            } else {
                $output->writeln("<error>duplicate object id $id skipped, there is a bug somewhere...</error>");
                continue;
            }

            $output->writeln("Processing {$objectRow['type']}:{$objectRow['itemId']} {$objectRow['name']}", OutputInterface::VERBOSITY_VERBOSE);
            switch ($type) {
                case $objectlib::TYPE_WIKI_PAGE:
                    $source = $wikiSource;
                    break;
                case $objectlib::TYPE_TRACKER_ITEM:
                    $source = $trackerItemSource;
                    break;
                default:
                    $source = null;
                    $output->writeln("Object with unsupported type:{$objectRow['type']} skipped: {$objectRow['itemId']} {$objectRow['name']}\n", OutputInterface::VERBOSITY_VERBOSE);
            }
            if ($source) {
                $outputArray = [
                'url' => null,
                'title' => null,
                'text' => null,
                'section' => null,
                'license' => null
                ];
                $outputArray['url'] = $objectRow['href'];
                $data = $source->getDocument($id, $factory);
                $outputArray['title'] = $data['title']->getValue();//Should correspond to $objectRow['name'];, but objectRow may be out of date
                //$output->writeln(print_r($data, true));
                //$output->writeln(print_r($data['wiki_content'], true));
                $globalFields = $source->getGlobalFields();

                $globalContent = \Search_Indexer::getGlobalContent($data, $globalFields);
                // A bit of a hack.  We add the title fiels at the end so it's available to indexing systems.
                // But textualization uses both title and text during chunting (prepends the title).  So we remove the title we know the universal index addad at the end of content.
                $content =
                str_replace($data['title']->getValue(), '', $globalContent);

                $outputArray['text'] = $content;
                $outputJson = json_encode($outputArray);
                //$output->writeln(print_r($data, true));
                //$output->writeln(print_r($content, true));
                //$output->writeln(print_r($objectRow, true));
                //$output->writeln(print_r($outputJson, true));

                if (fwrite($outputFile, "$outputJson\n") === false) {
                    echo "Cannot write to file ($fileName)";
                    return Command::FAILURE;
                }
                $exportedObjectCount++;
                $typeCountArrayRef = &$exportedTypeCounts;
            } else {
                $typeCountArrayRef = &$skippedTypeCounts;
            }
            $typeCountArrayRef[$type] = ($typeCountArrayRef[$type] ?? 0) + 1;
            $progressBar->advance();
        }
        $progressBar->finish();

        $io->text("Exported objects");
        $io->table(
            ['Type', '#'],
            array_map(
                null,
                array_keys($exportedTypeCounts),
                array_values($exportedTypeCounts)
            )
        );
        $io->text("Skipped objects");
        $io->table(
            ['Type', '#'],
            array_map(
                null,
                array_keys($skippedTypeCounts),
                array_values($skippedTypeCounts)
            )
        );
        $absPath = realpath($fileName);
        $output->writeln("$exportedObjectCount/$candidateObjectCount objects were exported to file $absPath");
        fclose($outputFile);
        return Command::SUCCESS;
    }
}
