<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter\Messages;

use Silex\ServiceProviderInterface;
use Silex\Application;

class MessageServiceProvider implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['messages.repository'] = $app->share(function () use ($app) {
        return new MessageRepository($app['db']);
      });

    $app['messages.controller'] = $app->share(function () use ($app) {
        return new MessageController($app);
      });
  }

  public function boot(Application $app)
  {

  }
}
