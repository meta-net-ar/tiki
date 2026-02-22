<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// Wikipedia specs : https://en.wikipedia.org/wiki/Code_128

function validator_barcode128($input, $parameter = '', $message = '')
{
    // Check for empty input (but allow "0" as it's a valid Code 128 character)
    if ($input === '' || $input === null) {
        return tra('Barcode128 code cannot be empty');
    }

    // Code 128 can encode all 128 ASCII characters (0-127)
    // Code sets A and B cover all 128 ASCII characters
    // Code set C is optimized for numeric data (two digits per symbol)
    // Extended ASCII (128-255) can be encoded via FNC4, but is not commonly used
    $length = strlen($input);

    // Validate that all characters are within ASCII range (0-127)
    for ($i = 0; $i < $length; $i++) {
        $charCode = ord($input[$i]);
        if ($charCode > 127) {
            return tra('Barcode128 Code contains invalid characters. Only ASCII characters (0-127) are allowed');
        }
    }

    // Code 128 length limits:
    // - A single Code 128 symbol can encode up to 48 data characters
    // - Multiple symbols can be used for longer data
    // - Practical limit depends on barcode reader capabilities
    // We set a reasonable maximum to prevent excessively long barcodes
    if ($length > 255) {
        return tra('Barcode128 Code is too long. Maximum length is 255 characters');
    }

    // Minimum length: Code 128 requires at least one data character
    // (start symbol, data, check digit, stop symbol are added during encoding)
    if ($length < 1) {
        return tra('Barcode128 Code must contain at least one character');
    }

    return true;
}
