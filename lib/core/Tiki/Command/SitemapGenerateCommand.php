<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Tiki\Sitemap\Generator as SiteMapGenerator;

#[AsCommand(
    name: 'sitemap:generate',
    description: 'Generate sitemap'
)]
class SitemapGenerateCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                tra('URL of the website. Example') . ' http://www.example.com'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;

        if (! isset($prefs['sitemap_enable']) || $prefs['sitemap_enable'] != 'y') {
            $output->writeln('<error>' . tra('Preference "sitemap_enable" is not enabled.') . '</error>');
            return Command::FAILURE;
        }

        $url = $input->getArgument('url');

        $sitemap = new SiteMapGenerator();

        $sitemap->generate($url);

        $output->writeln('<info>' . tra('New sitemap created.') . '</info>');
        $output->writeln('<info>' . $sitemap->getSitemapPath() . '</info>');
        return Command::SUCCESS;
    }
}
