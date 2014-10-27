<?php

require_once '../vendor/autoload.php';

$app = new \Silex\Application();

$app['rot_encode.count'] = 13;

$app['rot_encode'] = $app->share(function () use ($app) {
    return new Chatter\RotEncode($app['rot_encode.count']);
});

$app->get('/about', function () use ($app) {
    $s = $app['rot_encode']->rot("This is a super simple messaging service.");

    return $s;
});

$app->run();
