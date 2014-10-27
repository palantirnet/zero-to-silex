<?php

require_once '../vendor/autoload.php';

ini_set('display_errors', 'On');

$app = new \Silex\Application();

$app['debug'] = true;

$app['rot_encode.count'] = 13;

$app['rot_encode'] = $app->share(function () use ($app) {
    return new Chatter\RotEncode($app['rot_encode.count']);
});

$app->register(new Silex\Provider\DoctrineServiceProvider(), [
    'db.options' => [
      'driver'   => 'pdo_sqlite',
      'path'     => __DIR__ . '/../app.db',
    ],
]);

$app->get('/about', function () use ($app) {
    $s = $app['rot_encode']->rot("This is a super simple messaging service.");

    return $s;
});

$app->get('/reinstall', function () use ($app) {

    /** @var \Doctrine\DBAL\Connection $conn */
    $conn = $app['db'];

    /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager $sm */
    $sm = $conn->getSchemaManager();

    $schema = new \Doctrine\DBAL\Schema\Schema();

    $table = $schema->createTable('users');
    $table->addColumn("id", "integer", ["unsigned" => true]);
    $table->addColumn("username", "string", ["length" => 32]);
    $table->addColumn("age", "integer", ["unsigned" => true]);
    $table->setPrimaryKey(["id"]);
    $table->addUniqueIndex(["username"]);
    $schema->createSequence("users_seq");
    $sm->dropAndCreateTable($table);

    $table = $schema->createTable('messages');
    $table->addColumn("id", "integer", ["unsigned" => true]);
    $table->addColumn("author", "string", ["length" => 32]);
    $table->addColumn("parent", "integer", ["unsigned" => true]);
    $table->addColumn("message", "string", ["length" => 256]);
    $table->setPrimaryKey(["id"]);
    $sm->dropAndCreateTable($table);

    return 'DB installed';
});

$app->run();
