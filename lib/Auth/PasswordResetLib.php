<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Auth;

/**
 * Library for secure password reset functionality
 */
class PasswordResetLib extends \TikiLib
{
    /**
     * Generate a secure password reset token and store it in the database
     * @param string $user Username
     * @return array Array containing token and expiration time
     */
    public function generateSecurePasswordResetToken($user)
    {
        global $prefs;

        // Clean up expired tokens first
        $this->cleanupExpiredPasswordResetTokens();

        // Generate a cryptographically secure random token
        $token = bin2hex(random_bytes(32)); // 32 bytes = 64 hex characters

        // Set expiration time (default to 1 hour if not configured)
        $expiry_time = ($prefs['resetpasswordlink_expiry'] ?? 60) * 60;
        $expires = time() + $expiry_time;

        // Store the token in the database
        $query = 'INSERT INTO `tiki_password_reset_tokens` (`user`, `token`, `created`, `expires`) VALUES (?, ?, ?, ?)';
        $result = $this->query($query, [$user, $token, time(), $expires]);

        if ($result) {
            return [
                'token' => $token,
                'expires' => $expires,
                'expiry_time' => $expiry_time
            ];
        }

        return false;
    }

    /**
     * Validate a password reset token
     * @param string $user Username
     * @param string $token Reset token
     * @return array|false Array with token info if valid, false otherwise
     */
    public function validatePasswordResetToken($user, $token)
    {
        // Clean up expired tokens first
        $this->cleanupExpiredPasswordResetTokens();

        $query = 'SELECT * FROM `tiki_password_reset_tokens` WHERE `user` = ? AND `token` = ? AND `expires` > ? AND `used` = 0';
        $result = $this->query($query, [$user, $token, time()]);

        if ($result && $result->numRows() > 0) {
            return $result->fetchRow();
        }

        return false;
    }

    /**
     * Mark a password reset token as used
     * @param string $user Username
     * @param string $token Reset token
     * @return bool Success status
     */
    public function markPasswordResetTokenUsed($user, $token)
    {
        $query = 'UPDATE `tiki_password_reset_tokens` SET `used` = 1 WHERE `user` = ? AND `token` = ?';
        $result = $this->query($query, [$user, $token]);

        return $result !== false;
    }

    /**
     * Clean up expired password reset tokens
     */
    private function cleanupExpiredPasswordResetTokens()
    {
        $query = 'DELETE FROM `tiki_password_reset_tokens` WHERE `expires` < ?';
        $this->query($query, [time()]);
    }
}
