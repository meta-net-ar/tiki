<?php

function testDebugPhp($data)
{
    var_dump($data);

    print_r([
        'status' => 'ok',
        'value'  => $data,
    ]);

    error_log('Debug message from PHP test file');

    return true;
}

testDebugPhp(['a' => 1, 'b' => 2]);
