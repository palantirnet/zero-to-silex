<?php
/**
 * @file
 *
 * @copyright Copyright (c) 2014 Palantir.net
 */

namespace Chatter\Messages;

use Chatter\Application;
use Nocarrier\Hal;
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

    public function createMessage(Request $request) {
        $newMessage = json_decode($request->getContent(), TRUE);
        $message = $this->app['messages.repository']->create($newMessage);
        $url = $this->app['url_generator']->generate(
          'messages.view',
          ['message' => $message['id']]
        );

        return $this->app->redirect($url, Response::HTTP_CREATED);
    }

    public function getMessage($message)
    {
        // Make the HAL object.
        $self = $url = $this->app['url_generator']->generate(
          'messages.view',
          ['message' => $message['id']]
        );
        $hal = new Hal($self);

        // Add a link to the author.
        $author_id = $this->app['users.repository']->findByUsername($message['author'])['id'];
        $author = $url = $this->app['url_generator']->generate(
          'users.view',
          ['user' => $author_id]
        );
        $hal->addLink('author', $author);

        // Add a link to the parent, if any.
        if ($message['parent']) {
            $up = $url = $this->app['url_generator']->generate(
              'messages.view',
              ['message' => $message['parent']]
            );
            $hal->addLink('up', $up);
        }

        // Strip the now-irrelevant data from the object and send it.
        unset($message['id'], $message['parent'], $message['author']);
        $hal->setData($message);

        return $hal;
    }

    public function reply($message, Request $request)
    {
        $newMessage = json_decode($request->getContent(), TRUE);
        $newMessage['parent'] = $message['id'];
        $message = $this->app['messages.repository']->create($newMessage);

        $url = $this->app['url_generator']->generate(
          'messages.view',
          ['message' => $message['id']]
        );

        return $this->app->redirect($url, Response::HTTP_CREATED);
    }
}
