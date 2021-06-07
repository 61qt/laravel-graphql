<?php

namespace QT\GraphQL\Contracts;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface Context 
{
    public function getRequest(): Request;

    public function getResponse(): Response;

    public function getValue(string $key, mixed $default = null): mixed;
}