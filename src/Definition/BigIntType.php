<?php

namespace QT\GraphQL\Definition;

use GraphQL\Utils\Utils;
use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Type\Definition\IntType as IntType;

/**
 * Class BigIntType
 * 
 * @package QT\GraphQL\Definition
 */
class BigIntType extends IntType
{
/**
     * @var string
     */
    public $name = 'bigint';

    /**
     * @var string
     */
    public $description = 'bigint类型,兼容溢出的整形类型';

    /**
     * @param mixed $value
     * @return mixed
     */
    public function serialize($value)
    {
        if ($value === '') {
            return 0;
        }
        if (false === $value || true === $value) {
            return (int) $value;
        }

        $num = (float) $value;

        // The GraphQL specification does not allow serializing non-integer values
        // as Int to avoid accidental data loss.
        // Examples: 1.0 == 1; 1.1 != 1, etc
        if ($num != (int) $value) {
            // Additionally account for scientific notation (i.e. 1e3), because (float)'1e3' is 1000, but (int)'1e3' is 1
            $trimmed = floor($num);
            if ($trimmed !== $num) {
                throw new InvariantViolation(sprintf(
                    'Int cannot represent non-integer value: %s',
                    Utils::printSafe($value)
                ));
            }
        }
        return (int) $value;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function parseValue($value): int
    {
        return is_int($value) ? $value : null;
    }

    /**
     * @param \GraphQL\Language\AST\Node $ast
     * @return string
     * @throws Error
     */
    public function parseLiteral(Node $ast, ?array $variables = null)
    {
        if ($ast instanceof IntValueNode) {
            $val = (int) $ast->value;
            if ($ast->value === (string) $val) {
                return $val;
            }
        }
        return null;
    }
}
