<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\TikiDb\PdoDb;

class TikiDb_Initializer_Pdo
{
    public function isSupported()
    {
        return extension_loaded("pdo") && in_array('mysql', PDO::getAvailableDrivers());
    }

    /**
     * Prefers Pdo\Mysql::* (PHP 8.4+) over the deprecated PDO::MYSQL_* constants.
     *
     * @param string $name Constant name without the MYSQL_ prefix, e.g. "ATTR_INIT_COMMAND"
     * @return int|null
     */
    private function mysqlAttrConstant(string $name)
    {
        if (class_exists('Pdo\\Mysql') && defined('Pdo\\Mysql::' . $name)) {
            return constant('Pdo\\Mysql::' . $name);
        }

        if (defined('PDO::MYSQL_' . $name)) {
            return constant('PDO::MYSQL_' . $name);
        }

        return null;
    }

    public function getConnection(array $credentials)
    {
        // Set the host string for PDO dsn.
        $db_hoststring = "host={$credentials['host']}";

        // If using mysql and it is set to use sockets instead of hostname,
        // you can only use one method to connect, not both.  If $socket_tiki
        // is set in local.php, then it will override the hostname method
        // of connecting to the database.
        if (! empty($credentials['socket'])) {
            $db_hoststring = "unix_socket={$credentials['socket']}";
        }

        $conn = false;
        $pdo_options = [];
        $pdo_post_queries = [];

        if ($credentials['charset']) {
            $charset_query = "SET NAMES {$credentials['charset']}";
            $initCommandAttr = $this->mysqlAttrConstant('ATTR_INIT_COMMAND');

            if ($initCommandAttr !== null) {
                $pdo_options[$initCommandAttr] = $charset_query;
            } else {
                $pdo_post_queries[] = $charset_query;
            }

            unset($charset_query);
        }

        // Setup SSL, if activated
        $this->setupSSL($pdo_options);

        try {
            $db_dbname_string = empty($credentials['dbs']) ? '' : ";dbname={$credentials['dbs']}";
            $dbTiki = new PDO("mysql:$db_hoststring$db_dbname_string", $credentials['user'], $credentials['pass'], $pdo_options);

            $dbTiki->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
            $dbTiki->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
            //  $dbTiki->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);

            $db = new PdoDb($dbTiki);

            foreach ($pdo_post_queries as $query) {
                $db->query($query);
            }

            return $db;
        } catch (Exception $e) {
            return null;
        }
    }

    public function setupSSL(&$pdo_options)
    {
        global $tikipath;

        if (! extension_loaded('openssl')) {
            return;
        }

        $fileroot = $tikipath . 'db/cert/';

        // Look for the key files in the certroot
        //  Client key ends with: -key.pem
        //  Client cert ends with -cert.pem
        //  CA cert ends with -ca.pem
        // It is assumed that the folder only contains 1 set of keys
        $keyFiles = glob($fileroot . "*.pem");
        if (! empty($keyFiles)) {
            foreach ($keyFiles as $filename) {
                if (str_contains($filename, '-key.pem')) {
                    $clientKey = basename($filename);
                    continue;
                }
                if (str_contains($filename, '-cert.pem')) {
                    $clientCert = basename($filename);
                    continue;
                }
                if (str_contains($filename, '-ca.pem')) {
                    $caCert = basename($filename);
                    continue;
                }
            }

            // Activate SSL, if the key files are found
            $isSSL_verify_ca = ! empty($caCert);
            $isSSL_verify_identity = $isSSL_verify_ca && ! empty($clientKey) && ! empty($clientCert);

            if ($isSSL_verify_ca) {
                // Using 1 .pem file (CA) ie --ssl-mode=VERIFY_CA
                $pdo_options[$this->mysqlAttrConstant('ATTR_SSL_CA')] = $fileroot . $caCert;
                $pdo_options[$this->mysqlAttrConstant('ATTR_SSL_VERIFY_SERVER_CERT')] = false;
            } elseif ($isSSL_verify_identity) {
                // Using 3 .pem files (CA, Client cert and Client Key) ie --ssl-mode=VERIFY_IDENTITY
                $pdo_options[$this->mysqlAttrConstant('ATTR_SSL_KEY')] = $fileroot . $clientKey;
                $pdo_options[$this->mysqlAttrConstant('ATTR_SSL_CERT')] = $fileroot . $clientCert;
                $pdo_options[$this->mysqlAttrConstant('ATTR_SSL_CA')] = $fileroot . $caCert;
            }
        }
    }
}
