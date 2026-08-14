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

#[AsCommand(
    name: 'mcp:token:create',
    description: 'Create an API token for MCP server authentication'
)]
class McpTokenCreateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('user', InputArgument::OPTIONAL, 'Tiki username the token authenticates as', 'admin')
            ->addOption('label', 'l', InputOption::VALUE_REQUIRED, 'Human-readable label for the token', 'MCP Server')
            ->addOption('expire', 'e', InputOption::VALUE_REQUIRED, 'Expiration in days (0 = never)', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $input->getArgument('user');
        $label = $input->getOption('label');
        $expireDays = (int) $input->getOption('expire');

        // Validate user exists
        if (! \TikiLib::lib('user')->user_exists($user)) {
            $output->writeln("<error>User '$user' does not exist.</error>");
            return Command::FAILURE;
        }

        // Use cryptographically secure random bytes for token generation.
        $secureToken = bin2hex(random_bytes(32));

        $tokenData = [
            'type' => 'manual',
            'user' => $user,
            'label' => $label,
            'token' => $secureToken,
        ];

        if ($expireDays > 0) {
            $tokenData['expireAfter'] = time() + ($expireDays * 86400);
        }

        try {
            $token = \TikiLib::lib('api_token')->createToken($tokenData);
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>API token created successfully.</info>');
        $output->writeln('');
        $output->writeln('  Token:   <comment>' . $token['token'] . '</comment>');
        $output->writeln('  User:    ' . $token['user']);
        $output->writeln('  Label:   ' . $token['label']);
        if (! empty($token['expireAfter'])) {
            $output->writeln('  Expires: ' . date('Y-m-d H:i:s', $token['expireAfter']));
        } else {
            $output->writeln('  Expires: never');
        }
        $output->writeln('');
        $output->writeln('Add to your .mcp.json:');
        $output->writeln('  "headers": { "Authorization": "Bearer ' . $token['token'] . '" }');
        $output->writeln('');
        $output->writeln('Or use an environment variable:');
        $output->writeln('  export TIKI_MCP_TOKEN=' . $token['token']);
        $output->writeln('  "headers": { "Authorization": "Bearer ${TIKI_MCP_TOKEN}" }');

        return Command::SUCCESS;
    }
}
