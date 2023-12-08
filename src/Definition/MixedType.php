<?php

declare(strict_types=1);

namespace QT\GraphQL\Definition;

use GraphQL\Error\Error;
use GraphQL\Utils\Utils;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\BooleanValueNode;

/**
 * MixedType
 *
 * @package QT\GraphQL\Definition
 */
class MixedType extends ScalarType
{
    /**
     * @var string
     */
    public $name = Type::MIXED;

    /**
     * @var string
     */
    public $description = '混合类型,以支持[strings,bool,int,float,array]';

    /**
     * {@inheritDoc}
     *
     * @param mixed $value
     * @return mixed
     */
    public function serialize($value)
    {
        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $value
     * @return mixed|null
     */
    public function parseValue($value)
    {
        if (!is_scalar($value) && !is_array($value)) {
            throw new Error("Can't Invalid value: " . Utils::printSafe($value));
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @param Node $valueNode
     * @param array|null $variables
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        if ($valueNode instanceof StringValueNode) {
            return (string) $valueNode->value;
        }
        if ($valueNode instanceof IntValueNode) {
            return (int) $valueNode->value;
        }
        if ($valueNode instanceof BooleanValueNode) {
            return (bool) $valueNode->value;
        }
        if ($valueNode instanceof FloatValueNode) {
            return (float) $valueNode->value;
        }
        if ($valueNode instanceof ListValueNode) {
            $value = [];
            foreach ($valueNode->values as $val) {
                $value[] = $this->parseLiteral($val);
            }

            return $value;
        }
        if ($valueNode instanceof ObjectValueNode) {
            $value = [];

            foreach ($valueNode->fields as $field) {
                $value[$field->name->value] = $this->parseLiteral($field->value);
            }

            return $value;
        }

        throw new Error('Query error: Can only parse [strings,bool,int,float,array,object] got: ' . $valueNode->kind, [$valueNode]);
    }
}
