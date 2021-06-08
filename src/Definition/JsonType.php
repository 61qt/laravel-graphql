<?php

declare(strict_types=1);

namespace QT\GraphQL\Definition;

/**
 * Class JsonType
 *
 * @package QT\GraphQL\Definition
 */
class JsonType extends MixedType
{
    /**
     * @var string
     */
    public $name = 'json';

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
