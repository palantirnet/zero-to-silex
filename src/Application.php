<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter;

use Silex\Application as SilexApplication;
use Silex\Provider\DoctrineServiceProvider;

class Application extends SilexApplication
{

  public function __construct()
  {
    parent::__construct();

    $this->registerServices($this);
    $this->registerProviders($this);
    $this->createRoutes($this);
  }

  protected function registerServices(Application $app)
  {
    $app['rot_encode.count'] = 13;

    $app['rot_encode'] = $app->share(function () use ($app) {
        return new RotEncode($app['rot_encode.count']);
      });
  }

  protected function registerProviders(Application $app)
  {
    // Load the installation-specific configuration file. This should never be in Git.
    $app->register(new \Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/settings.json"));

    // Load environment-specific configuration.
    $app->register(new \Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/{$app['environment']}.json"));

    $app->register(new DoctrineServiceProvider(), [
        'db.options' => [
          'driver'   => 'pdo_sqlite',
          'path'     => __DIR__ . '/../' . $app['database']['path'],
        ],
      ]);
  }

  protected function createRoutes(Application $app)
  {
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

  }

}
