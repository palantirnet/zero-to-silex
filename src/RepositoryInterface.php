<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */
namespace Chatter;


interface RepositoryInterface {
  public function find($id);

  public function update($user);

  public function create($user);
}
