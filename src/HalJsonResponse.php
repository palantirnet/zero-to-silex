<?php

/**
 * @copyright (c) Copyright 2013 Palantir.net
 */

namespace Chatter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Nocarrier\Hal;

/**
 * Response for JsonHal data.
 *
 * This response object may be called with a Hal object directly and will
 * render it to Json and set the appropriate headers.
 *
 */
class HalJsonResponse extends Response
{

    /**
     * The Hal response we are going to send.
     *
     * @var \Nocarrier\Hal
     */
    protected $hal;

    /**
     * Whether or not the Json object should be pretty-printed.
     *
     * @var boolean
     */
    protected $pretty = false;

    /**
     * {@inheritDoc}
     */
    public function __construct(Hal $hal, $status = 200, $headers = [])
    {
        $headers['Content-Type'] = 'application/hal+json';
        parent::__construct('', $status, $headers);
        $this->hal = $hal;
    }

    /**
     * {@inheritdoc}
     *
     * We need to render the HAL object before we actually prepare the response.
     */
    public function prepare(Request $request)
    {
        $this->setContent($this->hal->asJson($this->pretty));

        return parent::prepare($request);
    }

    public function setPretty($pretty)
    {
        $this->pretty = $pretty;

        return $this;
    }

}
