<?php

require_once '../vendor/autoload.php';

$app = new \Silex\Application();

$app->get('/about', function() use ($app) {
    return new \Symfony\Component\HttpFoundation\Response("This is a super simple messaging service.");
});

$app->run();
