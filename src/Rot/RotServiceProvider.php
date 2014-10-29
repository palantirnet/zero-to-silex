<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter\Rot;

use Silex\ServiceProviderInterface;
use Silex\Application;

class RotServiceProvider implements ServiceProviderInterface
{

  public function register(Application $app)
  {
    $app['rot_encode.count'] = 13;

    $app['rot_encode'] = $app->share(function () use ($app) {
        return new RotEncode($app['rot_encode.count']);
      });
  }

  public function boot(Application $app)
  {

  }
}
