<?php

// TC-11 setup: creates an encryption key whose holders are admin and Test1.
//
// admin has to be a holder: the sss-admin edit form posts `regenerate` without an
// `old_share`, so the controller reconstructs the key from the acting user's stored
// share. An admin holding no share cannot regenerate through the UI at all.
//
// Output: JSON {keyId, adminShare, testUserShare}

return function (array $input): array {
    delete_keys_by_name('TC11-EncKey');

    $key = create_encryption_key('TC11-EncKey', 'admin,Test1');

    return [
        'keyId'         => $key['keyId'],
        'adminShare'    => $key['shares'][0],
        'testUserShare' => $key['shares'][1],
    ];
};
