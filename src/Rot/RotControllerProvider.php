<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter\Rot;

use Silex\ControllerProviderInterface;
use Silex\Application;

class RotControllerProvider implements ControllerProviderInterface
{
  public function connect(Application $app)
  {
    $controllers = $app['controllers_factory'];

    $controllers->get('/about', function () use ($app) {
        $s = $app['rot_encode']->rot("This is a super simple messaging service.");

        return $s;
    });

    return $controllers;
  }
}
