<?php

namespace QT\GraphQL;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use QT\GraphQL\Contracts\Context as ContextContract;

class Context implements ContextContract
{
    /**
     * Context constructor.
     * @param Request $request
     * @param Response $response
     * @param array $config
     */
    public function __construct(
        public Request $request,
        public Response $response,
        protected array $config = [],
    ) {
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @param string|int $key
     * @param mixed $default
     * @return mixed
     */
    public function setValue(string $key, mixed $value)
    {
        return Arr::set($this->config, $key, $value);
    }

    /**
     * @param string|int $key
     * @param mixed $default
     * @return mixed
     */
    public function getValue(string | int $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * @param string|int $key
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->request->input($key, $default);
    }

    /**
     * @param array $config
     * @return void
     */
    public function has(string | int $key)
    {
        return Arr::has($this->config, $key);
    }

    /**
     * @param array $config
     * @return void
     */
    public function merge(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }
}
