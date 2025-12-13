<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function validator_eanOneThreeCode($input, $parameter = '', $message = '')
{
    if (empty($input)) {
        return tra('EAN_13 code cannot be empty');
    }
    if (! is_numeric($input)) {
        return tra("EAN_13 Code should be a numeric value");
    }
    if (strlen((string) $input) !== 13) {
        return tra("EAN13 Code should be 13 characters long");
    }
    $strArray = str_split($input);
    $weightCoefficientMap = [1,3,1,3,1,3,1,3,1,3,1,3]; //1 up to 12
    $sumAccumulator = 0;
    $checkSumValueFromInput = -1;
    foreach ($strArray as $key => $digit) {
        if ($key < 12) {
            $sumAccumulator += intval($digit) * $weightCoefficientMap[$key];
        } else {
            $checkSumValueFromInput = intval($digit);
        }
    }
    // we need to get the difference between the number and the first upper multiple of 10,
    // eg.74. 74 + 10 = 84 then 84 mod 10 = 4 then 10 - 4 = 6 and finally 6 % 10 to make sure 10 return 0 as the difference
    $expectedCheckSum = (10 - (($sumAccumulator + 10) % 10)) % 10;
    if ($expectedCheckSum !== $checkSumValueFromInput) {
        return tra("last digit is not correct, EAN_13 code invalid!");
    }
    return true;
}
