<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter\Messages;

use Silex\ControllerProviderInterface;
use Silex\Application;

class MessageControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        /** @var \Silex\ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/messages', 'messages.controller:createMessage')
          ->bind('messages.create');

        $controllers->get("/messages/{message}", 'messages.controller:getMessage')
          ->bind('messages.view')
          ->convert('message', function ($id) use ($app) {
              return $app['messages.repository']->find($id);
            });

        $controllers->post("/messages/{message}/reply", 'messages.controller:reply')
          ->bind('messages.reply')
          ->convert('message', function ($id) use ($app) {
              return $app['messages.repository']->find($id);
            });

        return $controllers;
    }
}
