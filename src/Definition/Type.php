<?php

declare(strict_types=1);

namespace QT\GraphQL\Definition;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type as BaseType;

/**
 * Type
 *
 * @package QT\GraphQL\Definition
 */
abstract class Type extends BaseType
{
    public const NIL          = 'nil';
    public const JSON         = 'json';
    public const MIXED        = 'mixed';
    public const BIGINT       = 'bigint';
    public const TIMESTAMP    = 'timestamp';
    public const DIRECTION    = 'direction';
    public const UNSIGNED_INT = 'unsignedInt';

    /**
     * @var array<ScalarType|DirectionType>
     * */
    protected static $globalTypes = [];

    /**
     * @return NilType
     */
    public static function nil(): NilType
    {
        if (!isset(static::$globalTypes[self::NIL])) {
            static::$globalTypes[self::NIL] = new NilType();
        }

        return static::$globalTypes[self::NIL];
    }

    /**
     * @return JsonType
     */
    public static function json(): JsonType
    {
        if (!isset(static::$globalTypes[self::JSON])) {
            static::$globalTypes[self::JSON] = new JsonType();
        }

        return static::$globalTypes[self::JSON];
    }

    /**
     * @return MixedType
     */
    public static function mixed(): MixedType
    {
        if (!isset(static::$globalTypes[self::MIXED])) {
            static::$globalTypes[self::MIXED] = new MixedType();
        }

        return static::$globalTypes[self::MIXED];
    }

    /**
     * @return TimestampType
     */
    public static function timestamp(): TimestampType
    {
        if (!isset(static::$globalTypes[self::TIMESTAMP])) {
            static::$globalTypes[self::TIMESTAMP] = new TimestampType();
        }

        return static::$globalTypes[self::TIMESTAMP];
    }

    /**
     * @return BigIntType
     */
    public static function bigint(): BigIntType
    {
        if (!isset(static::$globalTypes[self::BIGINT])) {
            static::$globalTypes[self::BIGINT] = new BigIntType();
        }

        return static::$globalTypes[self::BIGINT];
    }

    /**
     * 排序用的方向类型
     *
     * @return DirectionType
     */
    public static function direction(): DirectionType
    {
        if (!isset(static::$globalTypes[self::DIRECTION])) {
            static::$globalTypes[self::DIRECTION] = new DirectionType();
        }

        return static::$globalTypes[self::DIRECTION];
    }

    /**
     * 无符号的32位整数
     *
     * @return UnsignedIntType
     */
    public static function uint(): UnsignedIntType
    {
        if (!isset(static::$globalTypes[self::UNSIGNED_INT])) {
            static::$globalTypes[self::UNSIGNED_INT] = new UnsignedIntType();
        }

        return static::$globalTypes[self::UNSIGNED_INT];
    }
}
