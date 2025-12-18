<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use JitFilter;
use Services_File_Utilities;
use Services_Tracker_Utilities;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use TikiLib;
use Tracker_Definition;
use Tracker_Item;

#[AsCommand(
    name: 'tracker:convert-attachments',
    description: 'Convert tracker attachments'
)]
class TrackerConvertAttachmentsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setHelp('Convert from tracker attachments (the ones that are global for the tracker) to attachments as a tracker file field type and then remove the tracker attachments')
            ->addArgument(
                'trackerId',
                InputArgument::REQUIRED,
                'ID of the tracker'
            )
            ->addArgument(
                'fieldId',
                InputArgument::REQUIRED,
                'ID of the attachment field'
            )
            ->addArgument(
                'galleryId',
                InputArgument::OPTIONAL,
                'The gallery ID where the file will be uploaded (If not specify, root gallery will be used)'
            )
            ->addOption(
                'if-exist',
                null,
                InputOption::VALUE_REQUIRED,
                'Action to do if the attachment already exist in the files field (skip or duplicate)'
            )
            ->addOption(
                'preview',
                null,
                InputOption::VALUE_NONE,
                'Preview the result of the command'
            )
            ->addOption(
                'not-remove',
                null,
                InputOption::VALUE_NONE,
                'Do not delete tracker attachments'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;

        $trklib = TikiLib::lib('trk');
        $trackerId = $input->getArgument('trackerId');
        $fieldId = $input->getArgument('fieldId');
        $galleryId = $input->getArgument('galleryId');
        $notremove = $input->getOption('not-remove');
        $preview = $input->getOption('preview');
        $if_exist = $input->getOption('if-exist');
        $savedAnswer = "";

        if ($notremove) {
            $remove = false;
        } else {
            $remove = true;
        }

        if ($preview) {
            $remove = false;
        }

        if (! empty($if_exist)) {
            if ($if_exist == "skip") {
                $savedAnswer = "4";
            } elseif ($if_exist == "duplicate") {
                $savedAnswer = "5";
            } else {
                $output->writeln("<error>Error: Invalid value for the option --if-exist (must be 'skip' or 'duplicate').</error>");
                return Command::INVALID;
            }
        }

        $trackerUtilities = new Services_Tracker_Utilities();
        $definition = Tracker_Definition::get($trackerId);

        // Check if trackerId and fieldId are valid
        if (! $definition) {
            $output->writeln("<error>Error: Invalid trackerId \"$trackerId\"</error>");
            return Command::INVALID;
        }

        try {
            $fgField = $trackerUtilities->getFieldsFromIds($definition, [$fieldId]);
        } catch (\Exception) {
            $output->writeln("<error>Error: Field \"$fieldId\" does not exist in the tracker \"$trackerId\"</error>");
            return Command::INVALID;
        }

        if ($fgField[0]['type'] !== 'FG') {
            $output->writeln("<error>Error: The field \"$fieldId\" must be a \"file\" field type</error>");
            return Command::INVALID;
        }

        $fgField = $fgField[0];

        if (! isset($galleryId) && isset($fgField['options_map']['galleryId'])) {
            $galleryId = $fgField['options_map']['galleryId'];
        }

        if (! $galleryId) {
            $galleryId = $prefs['fgal_root_id'];
        }

        // Check if its a valid file gallery
        try {
            $fileUtilities = new Services_File_Utilities();
            $galInfo = $fileUtilities->checkTargetGallery($galleryId);
        } catch (\Exception) {
            $output->writeln("<error>Error: Invalid galleryId \"$galleryId\"</error>");
            return Command::INVALID;
        }

        $items = $trackerUtilities->getItems(['trackerId' => $trackerId], fields : [$fgField['permName']]);
        $failedAttIds = [];
        $itemsFailed = 0;
        $itemsProcessed = 0;
        $attachmentsProcessed = 0;
        $attachmentsSkipped = 0;

        $output->writeln('<info>Copy attachment files from field "' . $fgField['permName'] . '" tracker "' . $trackerId . '" to filegal "' . $galleryId . '"</info>');

        foreach ($items as $item) {
            $itemId = $item['itemId'];

            $itemObject = Tracker_Item::fromInfo($item);

            if (! $itemObject || $itemObject->getDefinition() !== $definition) {
                continue;
            }

            $atts = $trklib->list_item_attachments($itemId, 0, -1, 'comment_asc', '');
            $fileIdList = [];

            $numAttachments = sizeof($atts['data']);

            if ($numAttachments === 0) {
                $output->writeln('<info>Tracker Item "' . $itemId . '" skipped (no attachments)</info>');
                continue;
            } else {
                $ess = $numAttachments > 1 ? 's' : '';
                $output->writeln('<info>Updating tracker item ' . $itemId . ': ' . $numAttachments . ' attachment' . $ess . '</info>');
                $itemsProcessed++;
            }

            foreach ($atts['data'] as $attachment) {
                $attachment = $trklib->get_item_attachment($attachment['attId']);

                if (! $attachment) {
                    $output->writeln('<error>Warning: Unable to get item attachment with attId "' . $attachment['attId'] . '"</error>');
                    continue;
                }

                $name = $attachment['filename'];
                $size = $attachment['filesize'];
                $type = $attachment['filetype'];
                $created = $attachment['created'];
                $auser = $attachment['user'];
                $description = $attachment['longdesc'];
                if ($attachment['comment']) {
                    $description .= "\nComment\n" . $attachment['comment'];
                }
                if ($attachment['version']) {
                    $description .= "\nVersion\n" . $attachment['version'];
                }
                if (file_exists($prefs['t_use_dir'] . $attachment['path'])) {
                    $data = file_get_contents($prefs['t_use_dir'] . $attachment['path']);
                } else {
                    $data = $attachment['data'];
                }

                $existingFiles = $itemObject->prepareFieldInput($fgField, [])['files'];
                $answer = "";
                foreach ($existingFiles as $file) {
                    if (! empty($file) && $file['filename'] == $name && $file['filetype'] == $type) {
                        if ($savedAnswer == "4") {
                            $answer = "1";
                            break;
                        } elseif ($savedAnswer == "5") {
                            $answer = "2";
                            break;
                        }
                        $warning = '<error>Warning: The field "' . $fieldId . '" already contains the attachment "' . $attachment['attId'] . '"</error>';
                        $question = new Question($warning . "\n" . "\t1. Skip\n\t2. Duplicate\n\t3. Abort all\n\t4. Always skip\n\t5. Always duplicate\nYour choice [4]: ", "4");
                        $question->setValidator(function ($answer) {
                            if (! in_array($answer, ["1", "2", "3", "4", "5"])) {
                                throw new \RuntimeException(
                                    'Invalid choice.'
                                );
                            }
                            return $answer;
                        });

                        $helper = $this->getHelper('question');
                        $answer = $helper->ask($input, $output, $question);
                        if ($answer == "4") {
                            $savedAnswer = $answer;
                            $answer = "1";
                        } elseif ($answer == "5") {
                            $savedAnswer = $answer;
                            $answer = "2";
                        }
                        break;
                    }
                }

                if ($answer == "1") {
                    $attachmentsSkipped++;
                    continue;
                } elseif ($answer == "3") {
                    return Command::FAILURE;
                }

                $actualSize = strlen($data);

                if ((int) $size !== $actualSize) {
                    $output->writeln('<error>Warning, size difference: ' . $size . ' !== ' . $actualSize . '</error>');
                }

                if (! $preview) {
                    try {
                        $fileId = $fileUtilities->uploadFile($galInfo, $name, $size, $type, $data, $auser, null, null, $description, $created);
                    } catch (\Exception $e) {
                        $fileId = false;
                        $output->writeln('<error>Error: File "' . $attachment['filename'] . '" on item "' . $itemId . '" could not be saved</error>');
                        $output->writeln('<error>' . $e->getMessage() . '</error>');
                    }

                    if ($fileId !== false) {
                        $fileIdList[] = $fileId;
                        $output->writeln('<info>Attachment "' . $attachment['filename'] . '" uploaded to file gallery (' . ' bytes)</info>');
                        $attachmentsProcessed++;
                    } else {
                        $output->writeln('<error>Failed to upload attachment "' . $attachment['filename'] . '" to file gallery</error>');
                        $failedAttIds[] = $attachment['attId'];
                    }
                } else {
                    $output->writeln('<info>Attachment "' . $attachment['filename'] . '" uploaded to file gallery (' . ' bytes)</info>');
                    $attachmentsProcessed++;
                }
            }

            if (empty($fileIdList) && ! $preview) {
                $output->writeln('<info>No files were uploaded to the file gallery (Item  "' . $itemId . '" skipped)</info>');
                continue;
            }

            if (! $preview) {
                $handler = $definition->getFieldFactory()->getHandler($fgField, $item);
                $files = $handler->bindFiles(implode(',', $fileIdList), true);

                $fields[$fgField['permName']] = $files;

                $fieldData = [];
                $fieldData[] = array_merge(
                    $trklib->get_field_info($fieldId),
                    [
                        'value' => $files,
                    ]
                );

                $result = $trklib->replace_item($trackerId, $itemId, ['data' => $fieldData]);

                if ($result == $itemId) {
                    if ($remove) {
                        foreach ($atts['data'] as $attachment) {
                            if (! in_array($attachment['attId'], $failedAttIds)) {
                                $trklib->remove_item_attachment($attachment['attId'], $itemId);
                            } else {
                                $output->writeln('<info>(Attachment ' . $attachment['attId'] . ' ' . $attachment['filename'] . ' not removed)</info>');
                                $numAttachments--;
                            }
                        }
                        $output->writeln('<info>Tracker item ' . $itemId . ' updated successfully and "' . $numAttachments . '" attachment' . $ess . ' removed</info>');
                    } else {
                        $output->writeln('<info>[tracker item ' . $itemId . ' updated successfully]</info>');
                    }
                } else {
                    $output->writeln('<info>Tracker item ' . $itemId . ' update failed</info>');
                    $itemsFailed++;
                }
            } else {
                $output->writeln('<info>[tracker item ' . $itemId . ' updated successfully]</info>');
            }
        }

        $failCount = count($failedAttIds);
        $op = $remove ? "moved" : "copied";
        $previewText = $preview ? '(Preview mode)' : '';

        $output->writeln("<info>Convert completed $previewText:</info>");
        $output->writeln("<info>$itemsProcessed item processed ($itemsFailed failed) and</info>");
        $output->writeln("<info>{$attachmentsProcessed} attachments $op ($failCount failed)</info>");
        $output->writeln("<info>{$attachmentsSkipped} attachments skipped ($failCount failed)</info>");

        return Command::SUCCESS;
    }
}
