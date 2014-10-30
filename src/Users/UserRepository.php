<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter\Users;

use Doctrine\DBAL\Connection;

class UserRepository
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    public function __construct(Connection $conn)
    {
      $this->conn = $conn;
    }

    public function find($id)
    {
        $record = $this->conn->fetchAssoc("SELECT id, username, age FROM users WHERE id = :id", [':id' => $id]);
        if (!$record) {
            throw new \InvalidArgumentException("User not found for ID: {$id}");
        }

      return $record;
    }

    public function findByUsername($username)
    {
        $record = $this->conn->fetchAssoc("SELECT id, username, age FROM users WHERE username = :username", [':username' => $username]);
        if (!$record) {
          throw new \InvalidArgumentException("User not found for name: {$username}");
        }

        return $record;
    }

    public function update($user)
    {
        $this->conn->update('users', ['username' => $user['username'], 'age' => $user['age']], ['id' => $user['id']]);
    }

    public function create($user)
    {
        try {
            $rows = $this->conn->insert('users', [
                'username' => $user['username'],
                'age' => $user['age'],
            ]);

            if ($rows) {
              return $this->find($this->conn->lastInsertId());
            } else {
              throw new \InvalidArgumentException("Could not create user: {$user['username']}");
            }
        } catch (\PDOException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        }
    }
}
