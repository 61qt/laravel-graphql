<?php

declare(strict_types=1);

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
     * 设置值
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setValue(string $key, mixed $value);

    /**
     * 获取字段值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getValue(string $key, mixed $default = null): mixed;

    /**
     * 传参的input
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed;
}
