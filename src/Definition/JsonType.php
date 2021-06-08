<?php
namespace QT\GraphQL\Definition;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\BooleanValueNode;

/**
 * Class JsonType
 *
 * @package QT\GraphQL\Definition
 */
class JsonType extends ScalarType
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

    /**
     * {@inheritDoc}
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        switch ($valueNode) {
            case ($valueNode instanceof StringValueNode):
            case ($valueNode instanceof BooleanValueNode):
                return $valueNode->value;
            case ($valueNode instanceof IntValueNode):
            case ($valueNode instanceof FloatValueNode):
                return floatval($valueNode->value);
            case ($valueNode instanceof ListValueNode):
                return array_map([$this, 'parseLiteral'], $valueNode->values);
            case ($valueNode instanceof ObjectValueNode):
                $value = [];

                foreach ($valueNode->fields as $field) {
                    $value[$field->name->value] = $this->parseLiteral($field->value);
                }

                return $value;
            default:
                return null;
        }
    }
}
