<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;
use TikiLib;
use Tiki\FileGallery\File as TikiFile;
use WikiParser_PluginMatcher;
use WikiParser_PluginArgumentParser;

class AttachmentsMigrateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('attachments:migrate')
            ->setDescription(tra('Convert legacy wiki attachment storage to file galleries or vice versa depending on settings.'))
            ->addOption(
                'remove-orphans',
                null,
                InputOption::VALUE_NONE,
                'Remove wiki attachments to pages that no longer exist.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;

        if ($prefs['feature_wiki_attachments'] != 'y') {
            throw new Exception(tra('Feature wiki attachments not set up'));
        }

        $wikilib = TikiLib::lib('wiki');
        $tikilib = TikiLib::lib('tiki');
        $filegallib = TikiLib::lib('filegal');

        if ($prefs['feature_use_fgal_for_wiki_attachments'] === 'y') {
            // check for legacy attachments
            $count = $wikilib->attachmentsCount();
        } else {
            // check for fgal attachments
            $count = $filegallib->fileGalleryAttachmentsCount();
        }

        if (! ($count > 0)) {
            $output->writeln('<comment>' . tr('No attachments found to migrate.') . '</comment>');
            return Command::SUCCESS;
        }

        // Display migration direction confirmation
        if ($prefs['feature_use_fgal_for_wiki_attachments'] === 'y') {
            $output->writeln('');
            $output->writeln('<comment>' . tr('WARNING: This migration will convert %0 attachments. From legacy attachments to File galleries.', $count) . '</comment>');
            $output->writeln('<comment>' . tr('Do you want to proceed? (yes/no)') . '</comment>');
            $output->writeln('');
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
        } else {
            $output->writeln('');
            $output->writeln('<comment>' . tr('WARNING: This migration will convert %0 attachments. From File galleries to legacy attachments.', $count) . '</comment>');
            $output->writeln('<comment>' . tr('Target storage: %0', $prefs['w_use_db'] === 'y' ? 'Database' : 'Filesystem (' . $prefs['w_use_dir'] . ')') . '</comment>');
            $output->writeln('<comment>' . tr('Do you want to proceed? (yes/no)') . '</comment>');
            $output->writeln('');
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
        }

        if (trim(strtolower($line)) != 'y' && trim(strtolower($line)) != 'yes') {
            $output->writeln('<comment>' . tr('Migration cancelled.') . '</comment>');
            return Command::SUCCESS;
        }

        $remove_orphans = $input->getOption('remove-orphans');
        $missingFiles = [];
        $migratedCount = 0;
        $skippedCount = 0;
        $orphanCount = 0;
        $errorCount = 0;

        if ($prefs['feature_use_fgal_for_wiki_attachments'] === 'y') {
            $result = $wikilib->list_all_attachments();
            foreach ($result['data'] as $att) {
                $output->writeln(tr('Processing page %0, attachment %1 %2...', $att['page'], $att['attId'], $att['filename']));

                // find or create attachments gallery for the corresponding wiki page
                $galleryId = $filegallib->get_attachment_gallery($att['page'], 'wiki page', true);
                if (! $galleryId) {
                    if ($remove_orphans) {
                        $output->writeln(tr('Wiki page not found, removing attachment...'));
                        $wikilib->remove_wiki_attachment($att['attId']);
                        $orphanCount++;
                    } else {
                        $output->writeln('<error>' . tr('File gallery for page %0 could not be found or created. Does the page exist?', $att['page']) . '</error>');
                        $output->writeln(tr('Hint: run this command with --remove-orphans to delete these attachments.'));
                        $skippedCount++;
                    }
                    continue;
                }

                // Check if file exists (when not stored in database)
                if ($att['path']) {
                    $filePath = $prefs['w_use_dir'] . $att['path'];
                    if (! file_exists($filePath)) {
                        $output->writeln('<comment>' . tr('Skipping: File not found at %0', $filePath) . '</comment>');
                        $missingFiles[] = [
                            'page' => $att['page'],
                            'attId' => $att['attId'],
                            'filename' => $att['filename'],
                            'path' => $filePath,
                            'hash' => $att['path']
                        ];
                        $skippedCount++;
                        continue;
                    }
                }

                // create file and replace its contents
                $file = new TikiFile([
                    'galleryId' => $galleryId,
                    'description' => $att['comment'],
                    'user' => $att['user'],
                    'comment' => mb_substr($att['comment'], 0, 200),
                    'hits' => $att['hits'],
                ]);
                $file->setParam('created', $att['created']);

                try {
                    $data = $wikilib->get_item_attachement_data($att);
                } catch (\Throwable $e) {
                    $output->writeln('<error>' . tr('Failed to read attachment data: %0', $e->getMessage()) . '</error>');
                    $missingFiles[] = [
                        'page' => $att['page'],
                        'attId' => $att['attId'],
                        'filename' => $att['filename'],
                        'path' => $att['path'] ? ($prefs['w_use_dir'] . $att['path']) : 'database',
                        'hash' => $att['path'] ?? '',
                        'error' => $e->getMessage()
                    ];
                    $errorCount++;
                    continue;
                }

                $name = $att['filename'];
                if (strlen($name) > 40) {
                    $name = substr($name, 0, 18) . '...' . substr($name, -18);
                }
                try {
                    $fileId = $file->replace($data, $att['filetype'], $name, $att['filename']);
                } catch (\Throwable $e) {
                    $output->writeln('<error>' . tr('Failed converting attachment to a file: %0 in %1:%2', $e->getMessage(), $e->getFile(), $e->getLine()) . '</error>');
                    $errorCount++;
                    continue;
                }
                // remove wiki attachment row
                $wikilib->remove_wiki_attachment($att['attId']);
                // replace attachment usage in wiki page
                $pageInfo = $tikilib->get_page_info($att['page']);
                if ($pageInfo) {
                    $updated = false;
                    $matches = WikiParser_PluginMatcher::match($pageInfo['data']);
                    $argumentParser = new WikiParser_PluginArgumentParser();
                    foreach ($matches as $match) {
                        $pluginName = $match->getName();
                        if ($pluginName == 'img') {
                            $arguments = $argumentParser->parse($match->getArguments());
                            $newArgs = [];
                            $modified = false;
                            $change_source_type = false;
                            foreach ($arguments as $key => $val) {
                                if ($key == 'attId' && $val == $att['attId']) {
                                    $newArgs[] = "fileId=$fileId";
                                    $modified = true;
                                } elseif ($key == 'src' && preg_match('/tiki-download_wiki_attachment\.php\?attId=(\d+)/', $val, $m) && $m[1] == $att['attId']) {
                                    $newArgs[] = "fileId=$fileId";
                                    $modified = true;
                                    $change_source_type = true;
                                } elseif ($key == 'type' && $val == 'attId') {
                                    $newArgs[] = "type=fileId";
                                } else {
                                    $newArgs[] = "$key=\"$val\"";
                                }
                            }
                            if ($change_source_type) {
                                foreach ($newArgs as $key => $val) {
                                    if (preg_match('/type\s*=\s*[\'"]*src[\'"]*/', $val)) {
                                        $newArgs[$key] = 'type=fileId';
                                    }
                                }
                            }
                            if ($modified) {
                                $match->replaceWith('{img ' . implode(' ', $newArgs) . '}');
                                $updated = true;
                            }
                        } elseif ($pluginName == 'file') {
                            $arguments = $argumentParser->parse($match->getArguments());
                            $newArgs = [];
                            $modified = false;
                            foreach ($arguments as $key => $val) {
                                if ($key == 'name' && $val == $att['filename']) {
                                    $newArgs[] = "fileId=$fileId";
                                    $modified = true;
                                } else {
                                    $newArgs[] = "$key=\"$val\"";
                                }
                            }
                            if ($modified) {
                                $match->replaceWith('{file ' . implode(' ', $newArgs) . '}');
                                $updated = true;
                            }
                        }
                    }
                    if ($updated) {
                        $tikilib->update_page($pageInfo['pageName'], $matches->getText(), tra('attachment conversion'), 'admin', '127.0.0.1', null, 0, '', null, null, null, '', '', true);
                    }
                }
                $migratedCount++;
            }

            // Generate migration report
            $output->writeln('');
            $output->writeln('<info>MIGRATION REPORT' . '</info>');
            $output->writeln('<info>----------------' . '</info>');
            $output->writeln('<info>' . tr('Successfully migrated: %0', $migratedCount) . '</info>');
            $output->writeln('<comment>' . tr('Skipped (missing files): %0', $skippedCount) . '</comment>');
            if ($orphanCount > 0) {
                $output->writeln('<comment>' . tr('Removed (orphans): %0', $orphanCount) . '</comment>');
            }
            if ($errorCount > 0) {
                $output->writeln('<error>' . tr('Failed with errors: %0', $errorCount) . '</error>');
            }
            $output->writeln('<info>' . tr('Total processed: %0', $migratedCount + $skippedCount + $orphanCount + $errorCount) . '</info>');

            // Report missing files in detail
            if (! empty($missingFiles)) {
                $output->writeln('');
                $output->writeln('<error>MISSING FILES DETAILS' . '</error>');
                $output->writeln('<comment>' . tr('The following attachments could not be migrated because their files are missing:') . '</comment>');
                $output->writeln('');

                $table = new Table($output);
                $table->setHeaders(['Page', 'AttId', 'Filename', 'Hash', 'Path']);
                foreach ($missingFiles as $missing) {
                    $table->addRow([
                        $missing['page'],
                        $missing['attId'],
                        $missing['filename'],
                        $missing['hash'],
                        $missing['path']
                    ]);
                }
                $table->render();

                $output->writeln('');
                $output->writeln('<error>RECOMMENDED ACTIONS' . '</error>');
                $output->writeln('1. ' . tr('Verify your w_use_dir preference value is correct:'));
                $output->writeln('   ' . tr('Current value: %0', $prefs['w_use_dir']));
                $output->writeln('   ' . tr('Check if this directory exists and contains the attachment files'));
                $output->writeln('');
            }
        } else {
            // Migrating from file galleries to attachments
            $mapping = [];
            $reverseErrors = [];
            $reverseMigratedCount = 0;
            $reverseSkippedCount = 0;
            $reverseErrorCount = 0;

            $result = $filegallib->list_file_galleries(0, -1, 'galleryId', '', '', $prefs['fgal_root_wiki_attachments_id']);
            foreach ($result['data'] as $gal_info) {
                $output->writeln(tr('Processing file gallery %0 %1...', $gal_info['id'], $gal_info['name']));

                // Verify gallery info exists before proceeding
                $fullGalInfo = $filegallib->get_file_gallery_info($gal_info['id']);
                if (! $fullGalInfo) {
                    $output->writeln('<comment>' . tr('Skipping: Gallery info not found for gallery %0', $gal_info['id']) . '</comment>');
                    $reverseSkippedCount++;
                    continue;
                }

                // Check if corresponding wiki page exists
                $pageInfo = $tikilib->get_page_info($gal_info['name']);
                if (! $pageInfo) {
                    $output->writeln('<comment>' . tr('Skipping: Wiki page "%0" does not exist', $gal_info['name']) . '</comment>');
                    $reverseSkippedCount++;
                    // Don't delete the gallery if page doesn't exist - let admin decide
                    continue;
                }

                $files = $filegallib->get_files(0, -1, 'fileId', '', $gal_info['id']);
                $galleryHasErrors = false;

                foreach ($files['data'] as $file_info) {
                    $output->writeln(tr('Processing file %0 %1...', $file_info['id'], $file_info['name']));

                    try {
                        // create wiki attachment and store data or path
                        $file = TikiFile::id($file_info['id']);
                        $data = $file->getContents();

                        if ($prefs['w_use_db'] === 'y') {
                            $fhash = '';
                        } else {
                            // Verify w_use_dir is writable
                            if (! is_dir($prefs['w_use_dir'])) {
                                throw new \Exception(tr('Directory does not exist: %0', $prefs['w_use_dir']));
                            }
                            if (! is_writable($prefs['w_use_dir'])) {
                                throw new \Exception(tr('Directory is not writable: %0', $prefs['w_use_dir']));
                            }

                            $fhash = $tikilib->get_attach_hash_file_name($file->filename);
                            $targetPath = $prefs['w_use_dir'] . $fhash;

                            $fp = fopen($targetPath, "wb");
                            if (! $fp) {
                                throw new \Exception(tr('Failed to open file for writing: %0', $targetPath));
                            }
                            fwrite($fp, $data);
                            fclose($fp);
                            $data = '';
                        }

                        $attId = $wikilib->wiki_attach_file($gal_info['name'], $file->filename, $file->filetype, $file->filesize, $data, $file->description, $file->user, $fhash);

                        // remove from file galleries
                        $file->delete();

                        $mapping[] = [$file->fileId, $attId, $file->filename];
                        $reverseMigratedCount++;
                    } catch (\Throwable $e) {
                        $output->writeln('<error>' . tr('Failed to migrate file %0: %1', $file_info['name'], $e->getMessage()) . '</error>');
                        $reverseErrors[] = [
                            'gallery' => $gal_info['name'],
                            'fileId' => $file_info['id'],
                            'filename' => $file_info['name'],
                            'error' => $e->getMessage()
                        ];
                        $reverseErrorCount++;
                        $galleryHasErrors = true;
                    }
                }

                // Only remove gallery if all files were successfully migrated
                if (! $galleryHasErrors && count($files['data']) > 0) {
                    try {
                        $filegallib->remove_file_gallery($gal_info['id']);
                    } catch (\Throwable $e) {
                        $output->writeln('<comment>' . tr('Note: Gallery %0 could not be removed: %1', $gal_info['name'], $e->getMessage()) . '</comment>');
                    }
                } elseif ($galleryHasErrors) {
                    $output->writeln('<comment>' . tr('Gallery %0 was not removed due to migration errors', $gal_info['name']) . '</comment>');
                }
            }

            // Generate reverse migration report
            $output->writeln('');
            $output->writeln('<info>MIGRATION REPORT' . '</info>');
            $output->writeln('<info>----------------' . '</info>');
            $output->writeln('<info>' . tr('Successfully migrated: %0', $reverseMigratedCount) . '</info>');
            $output->writeln('<comment>' . tr('Skipped: %0', $reverseSkippedCount) . '</comment>');
            if ($reverseErrorCount > 0) {
                $output->writeln('<error>' . tr('Failed with errors: %0', $reverseErrorCount) . '</error>');
            }
            $output->writeln('<info>' . tr('Total processed: %0', $reverseMigratedCount + $reverseSkippedCount + $reverseErrorCount) . '</info>');
            $output->writeln('');

            // Show error details if any
            if (! empty($reverseErrors)) {
                $output->writeln('<error>ERROR DETAILS' . '</error>');
                $table = new Table($output);
                $table->setHeaders(['Gallery/Page', 'File ID', 'Filename', 'Error']);
                foreach ($reverseErrors as $error) {
                    $table->addRow([
                        $error['gallery'],
                        $error['fileId'],
                        $error['filename'],
                        $error['error']
                    ]);
                }
                $table->render();
                $output->writeln('');

                $output->writeln('<error>RECOMMENDED ACTIONS' . '</error>');
                $output->writeln('1. ' . tr('Verify your w_use_dir preference value is correct:'));
                $output->writeln('   ' . tr('Current value: %0', $prefs['w_use_dir']));
                $output->writeln('   ' . tr('Ensure directory exists and is writable'));
                $output->writeln('');
                $output->writeln('2. ' . tr('Fix any reported errors and run the migration again'));
                $output->writeln('');
            }

            if (! empty($mapping)) {
                $output->writeln('<comment>' . tr('Replacing file references with attachment references in wiki pages must be done manually. Here\'s a table with ID mapping:') . '</comment>');
                $table = new Table($output);
                $table->setHeaders(['File ID', 'Attachment ID', 'File Name']);
                $table->setRows($mapping);
                $table->render();
            }
        }

        return Command::SUCCESS;
    }
}
