<?php

declare (strict_types = 1);

namespace QT\GraphQL\Definition;

use QT\GraphQL\Definition\JsonType;
use QT\GraphQL\Definition\MixedType;
use QT\GraphQL\Definition\BigIntType;
use GraphQL\Type\Definition\ScalarType;
use QT\GraphQL\Definition\TimestampType;
use QT\GraphQL\Definition\DirectionType;
use GraphQL\Type\Definition\Type as BaseType;

abstract class Type extends BaseType
{
    public const JSON      = 'json';
    public const MIXED     = 'mixed';
    public const BIGINT    = 'bigint';
    public const TIMESTAMP = 'timestamp';
    public const DIRECTION = 'direction';

    /** 
     * @var array<ScalarType|DirectionType> 
     * */
    protected static $standardTypes;

    /**
     * @api
     */
    public static function json() : JsonType
    {
        if (! isset(static::$standardTypes[self::JSON])) {
            static::$standardTypes[self::JSON] = new JsonType;
        }

        return static::$standardTypes[self::JSON];
    }

    /**
     * @api
     */
    public static function mixed() : MixedType
    {
        if (! isset(static::$standardTypes[self::MIXED])) {
            static::$standardTypes[self::MIXED] = new MixedType;
        }

        return static::$standardTypes[self::MIXED];
    }

    /**
     * @api
     */
    public static function timestamp() : TimestampType
    {
        if (! isset(static::$standardTypes[self::TIMESTAMP])) {
            static::$standardTypes[self::TIMESTAMP] = new TimestampType;
        }

        return static::$standardTypes[self::TIMESTAMP];
    }

    /**
     * @api
     */
    public static function bigint() : BigIntType
    {
        if (! isset(static::$standardTypes[self::BIGINT])) {
            static::$standardTypes[self::BIGINT] = new BigIntType;
        }

        return static::$standardTypes[self::BIGINT];
    }

    /**
     * @api
     */
    public static function direction() : DirectionType
    {
        if (! isset(static::$standardTypes[self::DIRECTION])) {
            static::$standardTypes[self::DIRECTION] = new DirectionType;
        }

        return static::$standardTypes[self::DIRECTION];
    }
}
