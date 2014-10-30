<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter\Users;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserControllerProvider implements ControllerProviderInterface
{
  public function connect(Application $app)
  {
    /** @var \Silex\ControllerCollection $controllers */
    $controllers = $app['controllers_factory'];

    $controllers->post('/users', function (Request $request) use ($app) {
        $newUser = json_decode($request->getContent(), true);

        $user = $app['users.repository']->create($newUser);

        $url = $app['url_generator']->generate('users.view', ['user' => $user['username']]);

        return $app->redirect($url, Response::HTTP_CREATED);
    });

    $controllers->get("/users/{user}", function ($user) use ($app) {
        $user = $app['users.repository']->findByUsername($user);
        unset($user['id']);

        return $app->json($user);
    })->bind('users.view');

    return $controllers;
  }
}
