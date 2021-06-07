<?php

namespace QT\GraphQL\Context;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use QT\GraphQL\Contracts\Context as ContextContract;

class Context extends Collection implements ContextContract
{
    /**
     * @var Request
     */
    public $request;

    /**
     * @var Response
     */
    public $response;

    /**
     * Context constructor.
     * @param Request $request
     * @param Response $response
     */
    public function __construct(
        Request $request,
        Response $response,
        array $config = [],
    ) {
        $this->request  = $request;
        $this->response = $response;

        parent::__construct($config);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }
}
