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

    /**
     * Enforce a valid CSRF ticket on state-changing POST requests.
     *
     * The mutating admin actions (save_key, delete_key) are reached only via
     * POST from the SSS admin Vue front-end. On a web POST we require a matching
     * origin and a valid ticket; access->checkCsrf() throws Services_Exception
     * with a 401 code when the ticket is missing or invalid.
     *
     * The guard is limited to genuine web POST requests. TIKI_API is defined by
     * tiki-setup.php on every web entry point (false for normal requests, true
     * for the token-authenticated API where CSRF does not apply), so its absence
     * means we are outside the web bootstrap — e.g. the CLI/unit-test harness,
     * which carries no CSRF material. Skipping there also avoids the
     * confirmation-dialog/die() branch of checkCsrf() outside a browser POST.
     *
     * unsetTicket is false so the stable per-session ticket stays reusable across
     * the SPA's successive admin actions even when short-lived CSRF tokens are on.
     */
    private function enforceCsrfTicket(): void
    {
        if (! defined('TIKI_API') || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }
        TikiLib::lib('access')->checkCsrf(null, null, null, false, null, 'services');
    }

    /**
     * Return the current session CSRF ticket so the SSS admin Vue front-end can
     * include it in its mutating POST requests. Admin-gated like the other read
     * actions on this controller; the ticket is session-bound and same-origin
     * policy prevents a cross-origin attacker from reading the response body.
     */
    public function action_get_ticket()
    {
        Services_Exception_Denied::checkGlobal('tiki_p_admin');
        $access = TikiLib::lib('access');
        $access->setTicket();
        return ['ticket' => $access->getTicket()];
    }

    public function action_save_key($input)
    {
        global $user, $prefs;

        Services_Exception_Denied::checkGlobal('tiki_p_admin');
        $this->enforceCsrfTicket();

        $keyId = $input->keyId->int();
        $name = $input->name->text();
        $users = TikiLib::lib('user')->extract_users($input->users->text(), $prefs['user_show_realnames'] == 'y');
        $key = null;
        $revokedUsers = [];

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
            $data = [];
            // Only overwrite metadata the caller actually sent, so a partial
            // update (e.g. posting only keyId + users) cannot wipe the stored
            // name, description or algorithm.
            if ($name !== '') {
                $data['name'] = $name;
            }
            if (isset($input['description'])) {
                $data['description'] = $input->description->text();
            }
            if ($input->regenerate->int()) {
                if (isset($input['algo']) && $input->algo->text() !== '') {
                    $data['algo'] = $input->algo->text();
                }
                $data['shares'] = $input->shares->int();
                $data['users'] = TikiLib::lib('tiki')->str_putcsv($users);
                if ($users) {
                    $data['shares'] = count($users);
                }
                $existingKeyRow = $this->encryptionlib->get_key($keyId);
                $revokedUsers = array_diff(
                    TikiLib::lib('user')->extract_users($existingKeyRow['users'] ?? '', $prefs['user_show_realnames'] == 'y'),
                    $users
                );
                $key = $this->action_decrypt_key(new JitFilter(['keyId' => $keyId, 'existing' => $input->old_share->text()]));
                $data['verificationCanary'] = $this->generateVerificationCanary($key);
                $shares = $this->share($key, $data);
            } else {
                $shares = null;
            }
        }

        $keyId = $this->encryptionlib->set_key($keyId, $data);

        // $shares is null on metadata-only updates (no regeneration). Skip the
        // distribution loop entirely in that case: writing $shares[$i] would
        // overwrite every posted user's stored share with null.
        if ($shares) {
            foreach ($users as $i => $auser) {
                if ($auser == $user) {
                    // Current user: store encrypted under their login phrase
                    // (falls back to a plaintext pending pref if their user
                    // encryption is not unlocked).
                    $this->storeCurrentUserShare($keyId, $shares[$i]);
                } else {
                    TikiLib::lib('tiki')->set_user_preference($auser, 'pe.sk.' . $keyId, $shares[$i]);
                }
            }

            // Remove the stored shares of users whose access was revoked by
            // this regeneration. Their old shares no longer match the
            // redistributed key, and the share management UI documents that
            // revocation removes the share from the user's account. Covers
            // both the plaintext pref (pe.sk.N) and the user-encryption
            // variant ({prefix}.sk.N).
            if (! empty($revokedUsers)) {
                $userPrefs = TikiLib::lib('tiki')->table('tiki_user_preferences');
                foreach ($revokedUsers as $revokedUser) {
                    $userPrefs->deleteMultiple([
                        'user' => $revokedUser,
                        'prefName' => $userPrefs->expr('$$ LIKE ?', ['%.sk.' . $keyId]),
                    ]);
                }
            }
        }

        return [
            'keyId' => $keyId,
            'shares' => $shares,
            // Raw binary key is intentionally omitted from the JSON response.
            // The frontend only needs keyId + shares. PHP unit tests access the
            // key via action_decrypt_key() on the returned keyId.
        ];
    }

    public function action_get_key($input)
    {
        global $prefs;

        $keyId = $input->keyId->int();

        $encryption_key = $this->projectKeyForClient($this->encryptionlib->get_key($keyId));
        if (! $encryption_key) {
            // Keep the key explicitly null when it does not exist, so callers
            // (e.g. Tiki\Encryption\Key) can distinguish a deleted key from an
            // accessible one and render the "No Access" state.
            return [
                'key' => null,
            ];
        }
        // The authorized-user list is admin-only data. Non-admin callers reach
        // this action through Tiki\Encryption\Key when rendering an encrypted
        // field and only need name/algo/accessibility — never the share holders.
        // The share-management admin UI is the sole consumer of users/users_array.
        if (Perms::get()->admin) {
            $encryption_key['users_array'] = TikiLib::lib('user')->extract_users($encryption_key['users'], $prefs['user_show_realnames'] == 'y');
        } else {
            unset($encryption_key['users']);
        }

        return [
            'key' => $encryption_key,
        ];
    }

    public function action_get_keys()
    {
        Services_Exception_Denied::checkGlobal('tiki_p_admin');

        $encryption_keys = $this->encryptionlib->get_keys();

        return array_map([$this, 'projectKeyForClient'], $encryption_keys);
    }

    /**
     * Projects an encryption key row to the fields that are safe to serialize
     * into service responses. The secret (Shamir share[0]) and verificationCanary
     * columns are key material and must never leave the server.
     *
     * @param array|bool $key Full key row from encryptionlib, or a falsy value when not found.
     * @return array|bool The projected row, or the original falsy value.
     */
    private function projectKeyForClient($key)
    {
        if (empty($key)) {
            return $key;
        }
        return array_intersect_key($key, array_flip(['keyId', 'name', 'description', 'algo', 'shares', 'users']));
    }

    public function action_get_algos($input)
    {
        Services_Exception_Denied::checkGlobal('tiki_p_admin');
        return TikiLib::lib('crypt')->algorithms();
    }

    public function action_get_users($input)
    {
        Services_Exception_Denied::checkGlobal('tiki_p_admin');
        $userlib = TikiLib::lib('user');
        $find = $input->find->text();
        // Without a search term, cap at 50 to avoid large payloads on big installations.
        // When searching, return all matches so the admin can find any specific user.
        $limit = $find ? -1 : 50;
        $result = $userlib->get_users(0, $limit, 'login_asc', $find);
        $logins = array_column($result['data'] ?? [], 'login');
        sort($logins);
        return $logins;
    }

    public function action_delete_key($input)
    {
        Services_Exception_Denied::checkGlobal('tiki_p_admin');
        $this->enforceCsrfTicket();
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
                // Self-heal: persist the verified share under the user's current
                // login phrase. Repairs a stale/orphaned stored share (e.g. after
                // a password change that bypassed the encryption rehash) and lets
                // "Use my stored share" work on subsequent visits.
                $this->storeCurrentUserShare($encryption_key['keyId'], $shared_key);
                Feedback::success(tr('Encryption key entered.'));
                return Services_Utilities::closeModal();
            }
        }
        // GET path: kept for backward compatibility with any non-Vue callers.
        // The Vue EnterKeyModal component always calls this endpoint via POST only.
        // This action is not admin-gated (any holder enters their own key), so the
        // key row must be projected before it is serialized — the raw secret
        // (Shamir share[0]) and verificationCanary must never leave the server.
        return [
            'title' => tr('Enter key'),
            'encryption_key' => $this->projectKeyForClient($encryption_key),
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

        // get_item_info() returns only item metadata, not field values, so its
        // handler would read an empty value and decryption would always "fail".
        // Load the full item (field values keyed by fieldId) for the handler.
        $fullItem = $trklib->get_tracker_item($itemId);
        $handler  = $trklib->get_field_handler($field, $fullItem);
        $raw      = $handler->getValue();
        $encKey  = new \Tiki\Encryption\Key($keyId);

        try {
            $decrypted = $encKey->decryptData($raw);
            if ($decrypted === false || $decrypted === null) {
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
     * Transitional: currently called only by the legacy jQuery tracker-field-unlock.js
     * for new-item (itemId=0) unlock. The Vue EnterKeyModal component performs the
     * same verification internally via action_enter_key's canary check, so it does not
     * call this endpoint directly.
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
        // Ensure the CryptLib class is loaded. It is normally pulled in as a side
        // effect of TikiLib::lib('crypt') elsewhere in the request, but the key
        // recovery path (save_key with old_share) reconstructs the key without
        // touching the user-encryption store, so the class may not be loaded yet.
        TikiLib::lib('crypt');
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

    /**
     * Persists the current user's share for a key under their account, encrypted
     * with their current login phrase. Used both when distributing freshly
     * generated shares and to heal an orphaned/stale stored share when a correct
     * share is entered manually. Falls back to a plaintext pending pref
     * (pe.sk.{keyId}) when user encryption is not unlocked; that pref is migrated
     * to the encrypted form on the user's next login.
     *
     * @param int    $keyId The encryption key id.
     * @param string $share The user's raw key share to store.
     */
    private function storeCurrentUserShare(int $keyId, string $share): void
    {
        global $user;
        if (empty($user) || $share === '') {
            return;
        }
        try {
            TikiLib::lib('crypt')->init();
            TikiLib::lib('crypt')->setUserData('sk', $share, $keyId);
        } catch (Exception $e) {
            TikiLib::lib('tiki')->set_user_preference($user, 'pe.sk.' . $keyId, $share);
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
