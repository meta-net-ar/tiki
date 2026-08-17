<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * This sniff prohibits the use of Symfony\Component\Process\Process
 * Use Tiki\Process\Process instead
 */

namespace PHP_CodeSniffer\Standards\Tiki\Sniffs\Wrapper;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class DisallowSymfonyProcessSniff implements Sniff
{
    /**
     * @inheritdoc
     */
    public function register()
    {
        return [
            T_USE,
            T_NEW,
        ];
    }


    /**
     * @inheritdoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if ($this->shouldIgnore($phpcsFile)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] == T_USE) {
            $find = [T_SEMICOLON, T_AS];
        } else {
            $find = [T_OPEN_PARENTHESIS];
        }

        $end = $phpcsFile->findNext($find, ($stackPtr + 1));
        $name = $phpcsFile->getTokensAsString(($stackPtr + 1), ($end - $stackPtr - 1));
        $name = trim($name);

        $regex = '/Symfony\\\\Component\\\\Process\\\\Process( .+)$/';
        if (preg_match($regex, $name)) {
            $error = 'Symfony\Component\Process\Process in use. Tiki\Process\Process should be used instead.';
            $phpcsFile->addError($error, $stackPtr, 'Found');
        }
    }

    protected function shouldIgnore(File $phpcsFile)
    {
        $fileName = $phpcsFile->getFilename();
        return preg_match('/lib\/core\/Tiki\/Process\/Process.php$/', $fileName);
    }
}
