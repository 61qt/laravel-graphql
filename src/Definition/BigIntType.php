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
class BigIntType extends ScalarType
{
    public const MAX_INT = 9223372036854775807;
    public const MIN_INT = -9223372036854775807;

    /**
     * @var string
     */
    public $name = Type::BIGINT;

    /**
     * @var string
     */
    public $description = 'bigint类型,兼容64位int';

    /**
     * @param mixed $value
     *
     * @return int|null
     *
     * @throws Error
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
                'Int cannot represent non 64-bit signed integer value: ' .
                Utils::printSafe($value)
            );
        }

        return (int) $float;
    }

    /**
     * @param mixed $value
     *
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
                'Int cannot represent non 64-bit signed integer value: ' .
                Utils::printSafe($value)
            );
        }

        return (int) $value;
    }

    /**
     * @param mixed[]|null $variables
     *
     * @return int
     *
     * @throws Exception
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
