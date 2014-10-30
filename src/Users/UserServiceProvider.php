<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter\Users;

use Silex\Application;

class UserServiceProvider implements \Silex\ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['users.repository'] = $app->share(function () use ($app) {
            return new UserRepository($app['db']);
        });

        $app['users.controller'] = $app->share(function() use ($app) {
            return new UserController($app);
        });
    }

    public function boot(Application $app)
    {

    }
}
