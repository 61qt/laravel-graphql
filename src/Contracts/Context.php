<?php

declare (strict_types = 1);

namespace QT\GraphQL\Contracts;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface Context 
{
    /**
     * @return Request
     */
    public function getRequest(): Request;

    /**
     * @return Response
     */
    public function getResponse(): Response;

    /**
     * @param string|int $key
     * @param mixed $value
     */
    public function setVale(string $key, mixed $value);

    /**
     * @param string|int $key
     * @param mixed $default
     * @return mixed
     */
    public function getValue(string $key, mixed $default = null): mixed;
}