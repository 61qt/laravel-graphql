<?php

declare(strict_types=1);

namespace QT\GraphQL;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use QT\GraphQL\Contracts\Context as ContextContract;

/**
 * Graphql runtime context
 *
 * @package QT\GraphQL
 */
class Context implements ContextContract
{
    /**
     * dataloaders pool
     *
     * @var Collection
     */
    public Collection $loaders;

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
        $this->loaders = new Collection();
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
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->request->input($key, $default);
    }

    /**
     * @param string|int $key
     * @param mixed $value
     * @return void
     */
    public function setValue(string | int $key, mixed $value)
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
     * @param string | int $key
     * @return bool
     */
    public function has(string | int $key): bool
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
