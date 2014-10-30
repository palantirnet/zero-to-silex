<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter\Users;

use Silex\Application;
use Silex\ControllerProviderInterface;

class UserControllerProvider implements ControllerProviderInterface
{
  public function connect(Application $app)
  {
    /** @var \Silex\ControllerCollection $controllers */
    $controllers = $app['controllers_factory'];

    $controllers->post('/users', 'users.controller:createUser')
      ->bind('users.create');

    $controllers->get("/users/{user}", 'users.controller:getUser')
      ->bind('users.view')
      ->convert('user', function($username) use ($app) {
          return $app['users.repository']->findByUsername($username);
      });

    return $controllers;
  }
}
