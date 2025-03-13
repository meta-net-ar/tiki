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
use Symfony\Component\Console\Output\OutputInterface;
use TikiLib;

#[AsCommand(
    name: 'preferences:set',
    description: 'Set a preference',
)]
class PreferencesSetCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Preference name'
            )
            ->addArgument(
                'value',
                InputArgument::REQUIRED,
                'Preference value. In case of multiple values, for multilist/array preferences, use comma separated values, for others (type text), use the appropriate separator.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logslib = TikiLib::lib('logs');
        $preference = $input->getArgument('name');
        $value = $input->getArgument('value');

        $tikilib = TikiLib::lib('tiki');
        $prefslib = TikiLib::lib('prefs');

        $preferenceInfo = $prefslib->getPreference($preference);

        if (empty($preferenceInfo)) {
            $output->write('<error>Preference not found.</error>');
            return Command::FAILURE;
        }

        if (count($preferenceInfo['conflicts'])) {
            if ($value == 'y') {
                foreach ($preferenceInfo['conflicts']['active'] as $conflict) {
                    $output->write(sprintf("<error>[CONFLICT]: the preference %s must be disabled first.</error>", $conflict['name']));
                }
                return Command::FAILURE;
            }
        }

        if ($preferenceInfo['type'] == 'flag' && ! in_array($value, ['y', 'n'])) {
            $output->writeln(sprintf('Preference %s is of type flag, allowed values are "y" or "n", you used %s.', $preference, $value));
            return Command::INVALID;
        }

        if (! empty($preferenceInfo['separator']) && ! is_array($value)) {
            $value = explode($preferenceInfo['separator'], $value);
        }

        if ($preferenceInfo['type'] == 'multilist' && ! is_array($value)) {
            $value = explode(',', $value);
        }

        if ($tikilib->set_preference($preference, $value)) {
            $output->writeln(sprintf('Preference %s was set.', $preference));
        } else {
            $output->writeln('<error>Unable to set preference.</error>');
        }

        if ($value === 'y') {
            $logslib->add_action('feature', $preference, 'system', 'enabled');
        } elseif ($value === 'n') {
            $logslib->add_action('feature', $preference, 'system', 'disabled');
        } else {
            $logslib->add_action('feature', $preference, 'system', is_array($value) ? implode(',', $value) : $value);
        }
        return Command::SUCCESS;
    }
}
