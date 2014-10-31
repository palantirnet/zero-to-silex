<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter\Messages;

use Chatter\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MessageController
{

  /**
   * @var Application
   */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function createMessage(Request $request)
    {
        $newMessage = json_decode($request->getContent(), true);
        $message = $this->app['messages.repository']->create($newMessage);
        $url = $this->app['url_generator']->generate(
          'messages.view',
          ['message' => $message['id']]
        );

        return $this->app->redirect($url, Response::HTTP_CREATED);
    }

    public function getMessage($message)
    {
        unset($message['id']);

        $message['author'] = $this->app['users.repository']->findByUsername($message['author'])['id'];

        return $this->app->json($message);
    }

    public function reply($message, Request $request)
    {
        $newMessage = json_decode($request->getContent(), true);
        $newMessage['parent'] = $message['id'];
        $message = $this->app['messages.repository']->create($newMessage);

        $url = $this->app['url_generator']->generate(
          'messages.view',
          ['message' => $message['id']]
        );

        return $this->app->redirect($url, Response::HTTP_CREATED);
    }
}
