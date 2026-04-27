<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use TQ\Shamir\Secret;

class Services_Encryption_Controller
{
    private $encryptionlib;

    public function setUp()
    {
        $this->encryptionlib = TikiLib::lib('encryption');
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'security';
    }

    public function action_save_key($input)
    {
        global $user, $prefs;

        $keyId = $input->keyId->int();
        $name = $input->name->text();
        $users = TikiLib::lib('user')->extract_users($input->users->text(), $prefs['user_show_realnames'] == 'y');
        $key = null;

        foreach ($this->encryptionlib->get_keys() as $existingKey) {
            if (strtolower($existingKey['name']) === strtolower($name) && $existingKey['keyId'] != $keyId) {
                throw new Services_Exception_DuplicateValue('name', tr('An encryption key named %0 already exists.', $name));
            }
        }

        if (empty($keyId)) {
            $data = [
                'name' => $name,
                'description' => $input->description->text(),
                'algo' => $input->algo->text(),
                'shares' => $input->shares->int(),
                'users' => TikiLib::lib('tiki')->str_putcsv($users),
            ];
            if ($users) {
                $data['shares'] = count($users);
            }
            $key = TikiLib::lib('crypt')->generateKey($data['algo']);
            $data['verificationCanary'] = $this->generateVerificationCanary($key);
            $shares = $this->share($key, $data);
        } else {
            $data = [
                'name' => $name,
                'description' => $input->description->text(),
            ];
            if ($input->regenerate->int()) {
                $data['algo'] = $input->algo->text();
                $data['shares'] = $input->shares->int();
                $data['users'] = TikiLib::lib('tiki')->str_putcsv($users);
                if ($users) {
                    $data['shares'] = count($users);
                }
                $key = $this->action_decrypt_key(new JitFilter(['keyId' => $keyId, 'existing' => $input->old_share->text()]));
                $data['verificationCanary'] = $this->generateVerificationCanary($key);
                $shares = $this->share($key, $data);
            } else {
                $shares = null;
            }
        }

        $keyId = $this->encryptionlib->set_key($keyId, $data);

        foreach ($users as $i => $auser) {
            if ($auser == $user) {
                try {
                    TikiLib::lib('crypt')->init();
                    TikiLib::lib('crypt')->setUserData('sk', $shares[$i], $keyId);
                } catch (Exception $e) {
                    TikiLib::lib('tiki')->set_user_preference($auser, 'pe.sk.' . $keyId, $shares[$i]);
                }
            } else {
                TikiLib::lib('tiki')->set_user_preference($auser, 'pe.sk.' . $keyId, $shares[$i]);
            }
        }

        return [
            'keyId' => $keyId,
            'shares' => $shares,
            'key' => $key,
        ];
    }

    public function action_get_key($input)
    {
        global $prefs;

        $keyId = $input->keyId->int();

        $encryption_key = $this->encryptionlib->get_key($keyId);
        $encryption_key['users_array'] = TikiLib::lib('user')->extract_users($encryption_key['users'], $prefs['user_show_realnames'] == 'y');

        return [
            'key' => $encryption_key,
        ];
    }

    public function action_get_keys()
    {
        $encryption_keys = $this->encryptionlib->get_keys();

        return $encryption_keys;
    }

    public function action_delete_key($input)
    {
        return $this->encryptionlib->delete_key($input->keyId->int());
    }

    public function action_get_share_for_key($input)
    {
        $crypt = TikiLib::lib('crypt');
        try {
            $crypt->init();
        } catch (Exception $e) {
            // anonymous users don't have encryption data and thus - no shared key for them
            return null;
        }
        try {
            $share = $crypt->getUserData('sk.' . $input->keyId->int());
        } catch (Exception $e) {
            throw new Services_Exception_Denied($e->getMessage());
        }
        return $share;
    }

    public function action_decrypt_key($input)
    {
        $encryption_key = $this->encryptionlib->get_key($input->keyId->int());
        if (empty($encryption_key)) {
            throw new Services_Exception_NotFound(tr("Key not found."));
        }
        $existing = $input->existing->text();
        if (! $existing) {
            $existing = $this->action_get_share_for_key(new JitFilter(['keyId' => $encryption_key['keyId']]));
        }
        if (! $existing) {
            $existing = @$_SESSION['encryption_shared_keys'][$encryption_key['keyId']];
        }
        if (empty($existing)) {
            throw new Services_Exception_Denied(tr('Shared key not found for your user.'));
        }
        try {
            $key = Secret::recover([$encryption_key['secret'], $existing]);
        } catch (\Throwable $e) {
            throw new Services_Exception_Denied($e->getMessage());
        }
        return $key;
    }

    public function action_get_encrypted_fields()
    {
        return $this->encryptionlib->get_encrypted_fields();
    }

    public function action_enter_key($input)
    {
        $encryption_key = $this->encryptionlib->get_key($input->keyId->int());
        if (empty($encryption_key)) {
            throw new Services_Exception_NotFound(tr("Key not found."));
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $shared_key = $input->shared_key->text();
            if (! empty($shared_key)) {
                $_SESSION['encryption_shared_keys'][$encryption_key['keyId']] = $shared_key;
                $keyObj = new \Tiki\Encryption\Key($encryption_key['keyId']);
                if (! empty($encryption_key['verificationCanary'])) {
                    try {
                        $verified = $keyObj->decryptData($encryption_key['verificationCanary']);
                    } catch (\Exception $e) {
                        $verified = false;
                    }
                    if ($verified !== 'tiki-key-verify-v1') {
                        unset($_SESSION['encryption_shared_keys'][$encryption_key['keyId']]);
                        throw new Services_Exception_FieldError('shared_key', tr('The entered key is incorrect for this encryption key.'));
                    }
                } else {
                    try {
                        $this->action_decrypt_key(new JitFilter(['keyId' => $encryption_key['keyId']]));
                    } catch (Services_Exception_Denied $e) {
                        unset($_SESSION['encryption_shared_keys'][$encryption_key['keyId']]);
                        throw new Services_Exception_FieldError('shared_key', tr('The entered key is incorrect for this encryption key.'));
                    }
                    $this->backfillVerificationCanary($encryption_key);
                }
                Feedback::success(tr('Encryption key entered.'));
                return Services_Utilities::closeModal();
            }
        }
        return [
            'title' => tr('Enter key'),
            'encryption_key' => $encryption_key,
            'shared_key' => @$_SESSION['encryption_shared_keys'][$encryption_key['keyId']],
        ];
    }

    /**
     * Returns the decrypted plaintext value of an encrypted tracker field for the current user.
     *
     * Requires the encryption key share to already be available in session (stored by
     * {@see action_enter_key()}). Validates that the requested field is actually encrypted
     * with the given key before attempting decryption, preventing cross-field access.
     *
     * @param JitFilter $input  POST parameters: keyId (int), fieldId (int), itemId (int)
     * @return array{value: string}  Decrypted value, or empty string if decryption fails
     * @throws Services_Exception_MissingValue  If any required parameter is absent
     * @throws Services_Exception_NotFound      If the tracker item does not exist
     * @throws Services_Exception_Denied        If the field is not encrypted with the given key
     */
    public function action_get_decrypted_value($input)
    {
        $keyId   = $input->keyId->int();
        $fieldId = $input->fieldId->int();
        $itemId  = $input->itemId->int();

        if (! $keyId || ! $itemId || ! $fieldId) {
            throw new Services_Exception_MissingValue('keyId, fieldId, itemId');
        }

        $trklib = TikiLib::lib('trk');
        $item   = $trklib->get_item_info($itemId);
        if (! $item) {
            throw new Services_Exception_NotFound('item');
        }

        $trackerItem = Tracker_Item::fromInfo($item);
        if (! $trackerItem->canView()) {
            throw new Services_Exception_Denied(tr('Permission denied'));
        }

        $field = $trklib->get_tracker_field($fieldId);
        if (! $field || (int)($field['encryptionKeyId'] ?? 0) !== $keyId) {
            throw new Services_Exception_Denied(tr('Field is not encrypted with this key.'));
        }

        if (! $trackerItem->canViewField($fieldId)) {
            throw new Services_Exception_Denied(tr('Permission denied'));
        }

        $handler = $trklib->get_field_handler($field, $item);
        $raw     = $handler->getValue();
        $encKey  = new \Tiki\Encryption\Key($keyId);

        try {
            $decrypted = $encKey->decryptData($raw);
            if ($decrypted === false || $decrypted === null || $decrypted === '') {
                return ['value' => null, 'error' => tr('Decryption failed: key may be incorrect or data may be corrupt.')];
            }
            return ['value' => $decrypted];
        } catch (\Tiki\Encryption\Exception $e) {
            return ['value' => null, 'error' => tr('Decryption failed: %0', $e->getMessage())];
        }
    }

    /**
     * Verifies that the session key for the given encryption key is correct,
     * using the verificationCanary stored on the key record.
     *
     * Intended for new-item unlock: when there is no existing ciphertext to
     * decrypt, this action provides a canary-based proof that the entered
     * key share is correct.
     *
     * If no verificationCanary exists (legacy key), verification is skipped
     * and the action returns {verified: true} — a conservative fallback.
     *
     * @param JitFilter $input  POST parameters: keyId (int)
     * @return array{verified: bool}|array{error: string}
     */
    public function action_verify_key($input)
    {
        global $user;
        if (empty($user)) {
            throw new Services_Exception_Denied(tr('Permission denied'));
        }

        $keyId = $input->keyId->int();
        $encryption_key = $this->encryptionlib->get_key($keyId);

        if (empty($encryption_key)) {
            return ['error' => tr('Key not found.')];
        }

        if (empty($encryption_key['verificationCanary'])) {
            // Legacy key without a canary: cannot perform cryptographic
            // verification. Accept optimistically; enter_key is the only gate.
            return ['verified' => true];
        }

        $keyObj = new \Tiki\Encryption\Key($encryption_key['keyId']);
        try {
            $verified = $keyObj->decryptData($encryption_key['verificationCanary']);
        } catch (\Exception $e) {
            $verified = false;
        }

        if ($verified !== 'tiki-key-verify-v1') {
            return ['error' => tr('The entered key is incorrect for this encryption key.')];
        }

        return ['verified' => true];
    }

    /**
     * Generates a verification canary for a given key.
     *
     * @param string $key The key to generate a canary for.
     * @return string The encrypted verification canary.
     */
    private function generateVerificationCanary(string $key): string
    {
        $crypt = new \CryptLib();
        $crypt->initSeed($key);
        return $crypt->encryptData('tiki-key-verify-v1');
    }

    /**
     * Opportunistically stores a verificationCanary on a legacy key the first time a user with a stored account share enters it. Non-critical: any failure is silently swallowed so it never blocks key entry.
     *
     * @param array $encryptionKey The encryption key to backfill the verification canary for.
     */
    private function backfillVerificationCanary(array $encryptionKey): void
    {
        if (! empty($encryptionKey['verificationCanary'])) {
            return;
        }
        try {
            $userShare = $this->action_get_share_for_key(new JitFilter(['keyId' => $encryptionKey['keyId']]));
            if (! empty($userShare)) {
                $correctKey = $this->action_decrypt_key(new JitFilter([
                    'keyId'    => $encryptionKey['keyId'],
                    'existing' => $userShare,
                ]));
                $this->encryptionlib->set_key(
                    $encryptionKey['keyId'],
                    ['verificationCanary' => $this->generateVerificationCanary($correctKey)]
                );
            }
        } catch (\Throwable $e) {
            // Non-critical: canary generation failure does not block key entry
        }
    }

    private function share($key, &$data)
    {
        if ($data['shares'] + 1 < 2) {
            throw new Services_Exception_Denied(tr('Key must be shared with minimum of one user.'));
        }
        $shares = Secret::share($key, $data['shares'] + 1, 2);
        $data['secret'] = $shares[0];
        return array_slice($shares, 1, count($shares) - 1);
    }
}
