<?php

declare (strict_types = 1);

namespace QT\GraphQL\Definition;

/**
 * JsonType
 *
 * @package QT\GraphQL\Definition
 */
class JsonType extends MixedType
{
    /**
     * @var string
     */
    public $name = Type::JSON;

    /**
     * @var string
     */
    public $description = 'json类型';

    /**
     * {@inheritDoc}
     *
     * @param  mixed $value
     * @return mixed
     */
    public function serialize($value)
    {
        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @param  mixed $value
     * @return mixed|null
     */
    public function parseValue($value)
    {
        return $value;
    }
}
