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
use Nocarrier\Hal;
use Silex\Application as SilexApplication;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

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
        $this->registerBeforeListeners($this);
        $this->registerViewListeners($this);
        $this->registerAfterListeners($this);
    }

    protected function registerAfterListeners(Application $app)
    {
        // Add caching headers as appropriate, including sending an HTTP 304
        // Not Modified if appropriate.  Note: This does mean that we generate
        // the whole response before deciding we don't need to send it, but
        // since there is dependent data in the response (_embedded resources)
        // we can't just use the primary resource's hash for the ETag.
        $app->after(function (Request $request, Response $response) use ($app) {
            // In debug mode, disable all caching.
            if ($app['debug']) {
                return;
            }

            $cache_lifetime = $app['cache.lifetime'] ?: 0;

            $response
              ->setTtl($cache_lifetime)
              ->setClientTtl($cache_lifetime);

            $expires = new \DateTime("now +{$cache_lifetime} seconds", new \DateTimeZone('UTC'));
            $response->setExpires($expires);

            $etag = sha1($response->getContent());
            $response->setEtag($etag);

            if ($response->isNotModified($request)) {
                $response->setNotModified();
            }
        }, 0);
    }

    protected function registerBeforeListeners(Application $app)
    {
        // Quick'n'dirty content negotiation.
        $app->before(function (Request $request, Application $app) {

            $request->setFormat('hal_json', 'application/hal+json');

            foreach ($request->getAcceptableContentTypes() as $mime_type) {
                if ($format = $request->getFormat($mime_type)) {
                    $request->setRequestFormat($format);

                    return;
                }
            }
            // Application-specific default.
            $request->setRequestFormat('json');
        });
    }

    protected function registerViewListeners(Application $app)
    {
        // Add a listener to convert a HAL object to a response.
        $app->on(KernelEvents::VIEW, function (GetResponseForControllerResultEvent $event) use ($app) {
            $result = $event->getControllerResult();

            //var_dump($event->getRequest()->attributes->all());

            if ($result instanceof Hal) {
                if (in_array($event->getRequest()->getRequestFormat(), ['json', 'hal_json'])) {
                    $response = new HalJsonResponse($result, Response::HTTP_OK);
                    $response->setPretty($app['debug']);
                } elseif ($app['debug']) {
                    // For debugging, default to returning JSON.
                    // Return application/json in dev mode because
                    // application/hal+json, while more precisely accurate,
                    // won't be rendered by most browsers.
                    $response = new Response($result->asJson(true),
                      Response::HTTP_OK,
                      ['Content-Type' => 'application/json']);
                } else {
                    // In production, require a proper accept header.
                    throw new NotAcceptableHttpException("Only media types application/hal+json and application/hal+xml are supported.");
                }

                $event->setResponse($response);
            }
        });
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

      $app->error(function (NotAcceptableHttpException $e, $code) {
          $problem = new ApiProblem('No acceptable format available', 'http://httpstatus.es/406');
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
