<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter\Users;


use Chatter\Application;
use Nocarrier\Hal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController
{

  /**
   * @var Application
   */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function createUser(Request $request) {
        $newUser = json_decode($request->getContent(), TRUE);
        $user = $this->app['users.repository']->create($newUser);
        $url = $this->app['url_generator']->generate(
          'users.view',
          ['user' => $user['username']]
        );

        return $this->app->redirect($url, Response::HTTP_CREATED);
    }

    public function getUser($user)
    {
        unset($user['id']);

        $self = $url = $this->app['url_generator']->generate(
          'users.view',
          ['user' => $user['username']]
        );
        $hal = new Hal($self, $user);

        return $hal;
    }

    public function putUser($user, Request $request)
    {
        $newUser = json_decode($request->getContent(), TRUE);
        $user['username'] = $newUser['username'];
        $user['age'] = $newUser['age'];
        $this->app['users.repository']->update($user);

        // Let's return the updated object.
        return $this->getUser($user);
    }
}
