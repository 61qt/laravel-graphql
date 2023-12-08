<?php

declare(strict_types=1);

namespace QT\GraphQL\Definition;

use GraphQL\Error\Error;
use GraphQL\Utils\Utils;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Type\Definition\ScalarType;

/**
 * BigIntType
 *
 * @package QT\GraphQL\Definition
 */
class UnsignedIntType extends ScalarType
{
    public const MAX_INT = 4294967295;
    public const MIN_INT = 0;

    /**
     * @var string
     */
    public $name = Type::UNSIGNED_INT;

    /**
     * @var string
     */
    public $description = '无符号的32位int类型,大小在0d到4294967295之间';

    /**
     * @param mixed $value
     * @throws Error
     * @return int|null
     */
    public function serialize($value)
    {
        // Fast path for 90+% of cases:
        if (is_int($value) && $value <= self::MAX_INT && $value >= self::MIN_INT) {
            return $value;
        }

        $float = is_numeric($value) || is_bool($value)
            ? (float) $value
            : null;

        if ($float === null || floor($float) !== $float) {
            throw new Error(
                'Int cannot represent non-integer value: ' .
                Utils::printSafe($value)
            );
        }

        if ($float > self::MAX_INT || $float < self::MIN_INT) {
            throw new Error(
                'Int cannot represent non 32-bit unsigned integer value: ' .
                Utils::printSafe($value)
            );
        }

        return (int) $float;
    }

    /**
     * @param mixed $value
     * @throws Error
     */
    public function parseValue($value): int
    {
        $isInt = is_int($value) || (is_float($value) && floor($value) === $value);

        if (!$isInt) {
            throw new Error(
                'Int cannot represent non-integer value: ' .
                Utils::printSafe($value)
            );
        }

        if ($value > self::MAX_INT || $value < self::MIN_INT) {
            throw new Error(
                'Int cannot represent non 32-bit unsigned integer value: ' .
                Utils::printSafe($value)
            );
        }

        return (int) $value;
    }

    /**
     * @param Node $valueNode
     * @param ?array $variables
     * @throws Exception
     * @return int
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        if ($valueNode instanceof IntValueNode) {
            $val = (int) $valueNode->value;
            if ($valueNode->value === (string) $val && self::MIN_INT <= $val && $val <= self::MAX_INT) {
                return $val;
            }
        }

        // Intentionally without message, as all information already in wrapped Exception
        throw new Error();
    }
}
