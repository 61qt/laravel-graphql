<?php
namespace QT\GraphQL\Type\Scalar;

use Carbon\Carbon;
use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST\StringValueNode;

class TimestampType extends ScalarType
{
    /**
     * @var string
     */
    public $name = 'timestamp';

    /**
     * @var string
     */
    public $description = '时间戳格式';

    public function serialize($value)
    {
        if ($value instanceof Carbon) {
            $value = (string) $value;
        }

        if (is_int($value)) {
            $value = date("Y-m-d H:i:s", $value);
        }

        return $value;
    }

    public function parseValue($value)
    {
        if (is_int($value)) {
            $value = date("Y-m-d H:i:s", $value);
        }

        return $value;
    }

    public function parseLiteral(Node $ast, ?array $variables = null)
    {
        if ($ast instanceof StringValueNode) {
            return $ast->value;
        }
        if ($ast instanceof IntValueNode) {
            return $ast->value;
        }

        throw new Error('Query error: timestamp must be [strings,int] got : ' . $ast->kind, [$ast]);
    }
}