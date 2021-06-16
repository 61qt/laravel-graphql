<?php

declare (strict_types = 1);

namespace QT\GraphQL\Definition;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ScalarType;

/**
 * 要求一定返回type,但是type又没有具体含义时使用
 * 
 * NilType
 *
 * @package QT\GraphQL\Definition
 */
class NilType extends ScalarType
{
    /**
     * @var string
     */
    public $name = Type::NIL;

    /**
     * @var string
     */
    public $description = '占位符,留空时使用';

    /**
     * {@inheritDoc}
     *
     * @param  mixed $value
     * @return mixed
     */
    public function serialize($value)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @param  mixed $value
     * @return mixed|null
     */
    public function parseValue($value)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     * 
     * @param Node $valueNode
     * @param array|null $variables
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        throw new Error('Query error: Cannot use nil type');
    }
}
