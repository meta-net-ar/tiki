<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\HelpCommand;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

#[AsCommand(
    name: 'ocr:file',
    description: 'Attempt to OCR a file. Defaults to queued OCR job'
)]
class OCRFileCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'File ID',
                InputArgument::OPTIONAL,
                tra('File ID of the file to OCR.')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ocrLib = \TikiLib::lib('ocr');
        $outputStyle = new OutputFormatterStyle('red');
        $output->getFormatter()->setStyle('error', $outputStyle);

        try {
            $ocrLib->checkOCRDependencies();
        } catch (Exception $e) {
            $output->writeln(
                '<error>' . $e->getMessage() . '</error>'
            );
            return Command::FAILURE;
        }

        // Set $nextOCRFile with the fileid of the next file scheduled to be processed by the OCR engine.
        $ocrLib->nextOCRFile = $ocrLib->table('tiki_files')->fetchOne('fileId', ['ocr_state' => $ocrLib::OCR_STATUS_PENDING]);

        $fgalId = $input->getArgument('File ID');
        if ($fgalId) {
            if (preg_match('/^\d+$/', $fgalId)) {
                $ocrLib->nextOCRFile = (int)$fgalId;
            } else {
                $help = new HelpCommand();
                $help->setCommand($this);
                $help->run($input, $output);
                $output->writeln(
                    "<error>File ID must be an int, $fgalId is an illegal value."
                );
                return Command::FAILURE;
            }
        }

        if (! $ocrLib->nextOCRFile) {
            $output->writeln('<comment>No files to OCR</comment>');
            return Command::SUCCESS;
        }

        try {
            $ocrLib->checkFileGalID();
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        try {
            $ocrLib->OCRfile();
            $output->writeln('<comment>Finished OCR of file</comment>');
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
