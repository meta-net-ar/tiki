<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function validator_barcode128($input, $parameter = '', $message = '')
{
    if (empty($input)) {
        return tra('Barcode128 code cannot be empty');
    }

    if (! is_numeric($input)) {
        return tra('Barcode128 Code should be a numeric value');
    }

    $length = strlen($input);
    if ($length < 12 || $length > 13) {
        return tra('Barcode128 Code should be 12 or 13 characters long');
    }

    return true;
}
