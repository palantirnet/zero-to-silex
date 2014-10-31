<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter\Messages;

use Chatter\RepositoryInterface;
use Doctrine\DBAL\Connection;
use Chatter\ObjectNotFoundException;

class MessageRepository implements RepositoryInterface
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
      $record = $this->conn->fetchAssoc("SELECT id, message, author, parent FROM messages WHERE id = :id", [':id' => $id]);
      if (!$record) {
        throw new ObjectNotFoundException("Message not found for ID: {$id}");
      }

      return $record;
  }

  public function update($message)
  {
      $message = $this->addDefaults($message);
      $this->conn->update('messages', [
          'message' => $message['message'],
          'author' => $message['author'],
          'parent' => $message['parent'],
        ], ['id' => $message['id']]
      );
  }

  public function create($message)
  {
    try {
      $message = $this->addDefaults($message);
      $rows = $this->conn->insert('messages', [
          'message' => $message['message'],
          'author' => $message['author'],
          'parent' => $message['parent'],
        ]);

      if ($rows) {
        return $this->find($this->conn->lastInsertId());
      } else {
        throw new \InvalidArgumentException("Could not create message.");
      }
    } catch (\PDOException $e) {
      throw new \InvalidArgumentException($e->getMessage());
    }
  }

  public function addDefaults($message)
  {
    return $message += [
      'message' => '',
      'author' => 0,
      'parent' => 0,
    ];
  }
}
