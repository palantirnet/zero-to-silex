<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter;

use Chatter\Messages\MessageControllerProvider;
use Chatter\Messages\MessageServiceProvider;
use Chatter\Rot\RotControllerProvider;
use Chatter\Rot\RotServiceProvider;
use Chatter\Users\UserControllerProvider;
use Chatter\Users\UserServiceProvider;
use Crell\ApiProblem\ApiProblem;
use Silex\Application as SilexApplication;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Application extends SilexApplication
{

  public function __construct()
  {
    parent::__construct();

    $this->registerServices($this);
    $this->registerProviders($this);
    $this->registerRoutes($this);
    $this->createRoutes($this);
    $this->registerErrorListeners($this);
  }

  protected function registerErrorListeners(Application $app)
  {
      $app->error(function (\Exception $e, $code) {
          $problem = new ApiProblem('Unknown error');
          $problem->setDetail($e->getMessage());

          return new JsonResponse($problem->asArray(), $code);
      });

      $app->error(function (ObjectNotFoundException $e) {
          $problem = new ApiProblem('Object not found', 'http://httpstatus.es/404');
          $problem->setDetail($e->getMessage());

          return new JsonResponse($problem->asArray(), Response::HTTP_NOT_FOUND);
       }, 10);

      $app->error(function (NotFoundHttpException $e, $code) {
          $problem = new ApiProblem('Resource not found', 'http://httpstatus.es/404');
          $problem->setDetail($e->getMessage());

          return new JsonResponse($problem->asArray(), $code);
       }, 10);

  }

  protected function registerServices(Application $app)
  {
  }

  protected function registerProviders(Application $app)
  {
    // Load our Rot encoding service provider bundle-like module thingie.
    $app->register(new RotServiceProvider());

    // Load user services.
    $app->register(new UserServiceProvider());

    // Load message services.
    $app->register(new MessageServiceProvider());

    // Load the Generator service. Nothing is there by default, remember?
    $app->register(new UrlGeneratorServiceProvider());

    // Service controllers FTW!
    $app->register(new ServiceControllerServiceProvider());

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

  protected function registerRoutes(Application $app)
  {
    $app->mount('/', new RotControllerProvider());
    $app->mount('/', new UserControllerProvider());
    $app->mount('/', new MessageControllerProvider());
  }

  protected function createRoutes(Application $app)
  {

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
