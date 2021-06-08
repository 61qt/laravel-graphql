<?php
namespace QT\GraphQL\Definition;

use GraphQL\Error\Error;
use GraphQL\Utils\Utils;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Language\AST\StringValueNode;

/**
 * Class MixedType
 * 
 * @package QT\GraphQL\Definition
 */
class MixedType extends ScalarType
{
    /**
     * @var string
     */
    public $name = 'mixed';

    /**
     * @var string
     */
    public $description = '混合类型,以支持[strings,bool,int,float,array]';

    /**
     * @param mixed $value
     * @return mixed
     */
    public function serialize($value)
    {
        return $value;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function parseValue($value)
    {
        if (!is_scalar($value) && !is_array($value)) {
            throw new \UnexpectedValueException("CannInvalid value: " . Utils::printSafe($value));
        }

        return $value;
    }

    /**
     * @param \GraphQL\Language\AST\Node $ast
     * @return string
     * @throws Error
     */
    public function parseLiteral(Node $ast, ?array $variables = null)
    {
        if ($ast instanceof StringValueNode) {
            return (string) $ast->value;
        }
        if ($ast instanceof IntValueNode) {
            return (int) $ast->value;
        }
        if ($ast instanceof BooleanType) {
            return (bool) $ast->value;
        }
        if ($ast instanceof FloatValueNode) {
            return (float) $ast->value;
        }
        if ($ast instanceof ListValueNode) {
            $result = [];

            foreach ($ast->values as $node) {
                $result[] = $this->parseLiteral($node);
            }

            return $result;
        }

        throw new Error('Query error: Can only parse [strings,bool,int,float] got: ' . $ast->kind, [$ast]);
    }
}