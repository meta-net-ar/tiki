<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// UPC-A: 12 digits (most common)
// UPC-E: 6 digits (compressed version of UPC-A)
// Check digit calculation per Wikipedia: https://en.wikipedia.org/wiki/Universal_Product_Code

function validator_upc($input, $parameter = '', $message = '')
{
    // Check for empty input (but allow "0" as it's a valid UPC character)
    if ($input === '' || $input === null) {
        return tra('UPC code cannot be empty');
    }
    if (! is_numeric($input)) {
        return tra("UPC Code should be a numeric value");
    }

    $inputStr = (string) $input;
    $length = strlen($inputStr);

    // UPC-A: 12 digits, UPC-E: 6 digits
    if ($length !== 12 && $length !== 6) {
        return tra("UPC Code should be 12 digits (UPC-A) or 6 digits (UPC-E)");
    }

    $strArray = str_split($inputStr);

    // Check digit calculation per Wikipedia specification:
    // 1. Sum the digits at odd-numbered positions (1st, 3rd, 5th, ..., 11th)
    // 2. Multiply the result by 3
    // 3. Add the digit sum at even-numbered positions (2nd, 4th, 6th, ..., 10th)
    // 4. Find the result modulo 10 (M)
    // 5. If M is zero, check digit is 0; otherwise check digit is 10 - M
    $dataLength = $length - 1;
    $oddSum = 0;
    $evenSum = 0;
    $checkSumValueFromInput = -1;

    foreach ($strArray as $key => $digit) {
        if ($key < $dataLength) {
            // Positions are 0-indexed: 0=1st (odd), 1=2nd (even), 2=3rd (odd), etc.
            if ($key % 2 == 0) {
                // Odd-numbered position (1st, 3rd, 5th, ...)
                $oddSum += intval($digit);
            } else {
                // Even-numbered position (2nd, 4th, 6th, ...)
                $evenSum += intval($digit);
            }
        } else {
            $checkSumValueFromInput = intval($digit);
        }
    }

    // Calculate expected check digit
    $sum = ($oddSum * 3) + $evenSum;
    $m = $sum % 10;
    $expectedCheckSum = ($m == 0) ? 0 : (10 - $m);

    if ($expectedCheckSum !== $checkSumValueFromInput) {
        return tra("last digit is not correct, UPC code invalid!");
    }

    return true;
}
