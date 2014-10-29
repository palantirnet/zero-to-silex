<?php

require_once '../vendor/autoload.php';

ini_set('display_errors', 'On');

$app = new \Chatter\Application();

$app['debug'] = true;

$app->run();
