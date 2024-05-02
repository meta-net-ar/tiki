<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
include_once dirname(__DIR__) . '/entities/ClientEntity.php';

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    private const TABLE = 'tiki_oauthserver_clients';
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public static function build($data)
    {
        return new ClientEntity($data);
    }

    public function list()
    {
        $result = [];
        $sql = $this->database->query('SELECT * FROM ' . self::TABLE);

        if ($sql && $sql->result) {
            $result = array_map(function ($data) {
                return self::build($data);
            }, $sql->result);
        }

        return $result;
    }

    public function get($value, $key = 'client_id')
    {
        $result = null;
        $sql = 'SELECT * FROM `%s` WHERE %s=?';
        $sql = sprintf($sql, self::TABLE, $key);

        $query = $this->database->query($sql, [$value]);
        if ($query && $query->result) {
            $result = new ClientEntity($query->result[0]);
        }

        return $result;
    }

    public function update($entity)
    {
        if (! empty($this->validate($entity))) {
            throw new Exception(tra('Cannot save invalid client'));
        }

        $sql = 'UPDATE `%s` SET name=?, client_id=?, client_secret=?, redirect_uri=?, user=? WHERE id=?';
        $sql = sprintf($sql, self::TABLE);

        $query = $this->database->query($sql, [
            $entity->getName(),
            $entity->getClientId(),
            $entity->getClientSecret(),
            $entity->getRedirectUri(),
            $entity->getUser(),
            $entity->getId()
        ]);

        return $query;
    }

    public function create($entity)
    {
        if (! empty($this->validate($entity))) {
            throw new Exception(tra('Cannot save invalid client'));
        }

        $sql = 'INSERT INTO `%s`(name, client_id, client_secret, redirect_uri, user) VALUES(?, ?, ?, ?, ?)';
        $sql = sprintf($sql, self::TABLE);

        $query = $this->database->query($sql, [
            $entity->getName(),
            $entity->getClientId(),
            $entity->getClientSecret(),
            $entity->getRedirectUri(),
            $entity->getUser()
        ]);

        $id = (int) $this->database->lastInsertId();
        $entity->setId($id);

        return $entity;
    }

    public function save($entity)
    {
        if ($entity->getId()) {
            return $entity->update();
        }
        return $entity->create();
    }

    public function delete($entity)
    {
        $params = [];
        $sql = sprintf('DELETE FROM `%s` WHERE ', self::TABLE);

        if ($entity->getId()) {
            $sql .= 'id=?';
            $params[] = $entity->getId();
        } elseif ($entity->getClientId()) {
            $sql .= 'client_id=?';
            $params[] = $entity->getClientId();
        }
        $sql .= ';';

        if (empty($params)) {
            return false;
        }

        return $this->database->query($sql, $params);
    }

    public function exists($entity)
    {
        $params = [];
        $sql = sprintf('SELECT COUNT(1) AS count FROM `%s` WHERE ', self::TABLE);

        if ($entity->getId()) {
            $sql .= 'id=?';
            $params[] = $entity->getId();
        } elseif ($entity->getClientId()) {
            $sql .= 'client_id=?';
            $params[] = $entity->getClientId();
        }

        $sql .= ';';
        if (empty($params)) {
            return false;
        }

        $result = $this->database->getOne($sql, $params);
        $result = (int)$result;
        return $result > 0;
    }

    public function validate($entity)
    {
        $errors = [];

        if (empty($entity->getName())) {
            $errors['name'] = tra('Name cannot be empty');
        }

        if (empty($entity->getRedirectUri())) {
            $errors['redirect_uri'] = tra('Redirect URI cannot be empty');
        } elseif (! filter_var($entity->getRedirectUri(), FILTER_VALIDATE_URL)) {
            $errors['redirect_uri'] = tra('Invalid URL for redirect URI');
        }

        return $errors;
    }

    public function getClientEntity($clientId)
    {
        return $this->get($clientId);
    }

    /**
     * Validate a client's secret.
     *
     * @param string      $clientIdentifier The client's identifier
     * @param null|string $clientSecret     The client's secret (if sent)
     * @param null|string $grantType        The type of grant the client is using (if sent)
     *
     * @return bool
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        $client = $this->get($clientIdentifier);
        if (is_null($client)) {
            return false;
        }
        if ($client->isConfidential() && $clientSecret) {
            if ($client->getClientSecret() !== $clientSecret) {
                return false;
            }
        }
        return true;
    }

    public static function generateSecret($length = 32)
    {
        $random = \phpseclib3\Crypt\Random::string(ceil($length / 2));
        $random = bin2hex($random);
        $random = substr($random, 0, $length);
        return $random;
    }
}
